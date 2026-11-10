<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Cart;
use App\Models\Auction;
use App\Models\AuctionBid;
use Carbon\Carbon;
use App\Models\Car;
use App\Models\BuilderTemporary;
use App\Models\UserBuild;
use App\Models\BuildItem;
use App\Models\Wishlist;
use App\Services\CarBuilderHelper;
use App\Models\Order;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Review;
use App\Models\CarRepair;
use Illuminate\Support\Facades\Storage;
use App\Models\RepairPartsSelected;
use App\Models\Message;
use App\Models\WarrantyClaim;
use App\Models\Category;
use App\Models\Setting;
use App\Models\Tag;




class CustomerController extends Controller
{
    public function deleteAccount(Request $request)
    {
        $user = Auth::user();

        // Check if user is authenticated and is a Customer
        if (!$user || $user->user_type !== 'Customer') {
            return redirect()->route('login');
        }

        try {
            DB::beginTransaction();

            // 1. Delete from customer table using user's id
            Customer::where('user_id', $user->uid)->delete();

            // 2. Delete from users table using 'uid' column
            $deleted = $user->delete();

            if ($deleted) {
                DB::commit();

                // Clear session and log out
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('success', 'Goodbye!');
            } else {
                DB::rollBack();
                return redirect()->route('customer.profile')->with('error', 'Error');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('customer.profile')->with('error', 'Error');
        }
    }

    public function addToCart(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'qty' => 'required|integer|min:1',
        ]);

        $productId = $request->input('id');
        $qtyRequested = (int) $request->input('qty');
        $customerId = session('user_id'); // Matches your session configuration

        // 1. Fetch current stock and product name
        $product = Product::find($productId);

        if (!$product) {
            return redirect()->route('store')->with('error', 'Product not found.');
        }

        $currentInventory = $product->quantity;
        $pName = $product->product_name;

        // 2. Check if the product is already in the cart for this user
        $cartItem = Cart::where('customer_id', $customerId)
                        ->where('product_id', $productId)
                        ->first();

        $existingQty = $cartItem ? $cartItem->quantity : 0;
        $newTotalQty = $existingQty + $qtyRequested;

        // 3. STOCK VALIDATION: Prevent adding more than available stock
        if ($newTotalQty > $currentInventory) {
            if ($existingQty > 0) {
                $msg = "Cannot add $qtyRequested more. You already have $existingQty in your cart, and only $currentInventory are available in total.";
            } else {
                $msg = "Cannot add $qtyRequested. Only $currentInventory items are in stock.";
            }

            // Redirect back to the product view page with the specific error
            return redirect()->route('product.view', ['id' => $productId])->with('error', $msg);
        }

        // 4. Update or Insert into Cart
        if ($cartItem) {
            $cartItem->update([
                'quantity' => $newTotalQty
            ]);
        } else {
            Cart::create([
                'customer_id' => $customerId,
                'product_id' => $productId,
                'quantity' => $qtyRequested,
            ]);
        }

        return redirect()->route('store')->with('success', "$pName added to cart!");
    }

    public function placeBid(Request $request)
    {
        $request->validate([
            'auction_id' => 'required|integer',
            'bid_amount' => 'required|numeric|min:0',
        ]);

        $auctionId = $request->input('auction_id');
        $bidAmount = $request->input('bid_amount');
        $customerId = session('user_id'); // Matches session storage

        try {
            return DB::transaction(function () use ($auctionId, $bidAmount, $customerId) {
                // 1. Fetch current auction data with pessimistic locking (FOR UPDATE equivalent)
                $auction = Auction::where('id', $auctionId)->lockForUpdate()->first();

                if (!$auction) {
                    throw new \Exception("Auction not found.");
                }

                // 2. Logic Checks
                $now = Carbon::now();

                // First bidder logic calculation
                if (empty($auction->highest_bidder_id) || $auction->highest_bidder_id == 0) {
                    $minRequired = $auction->current_bid;
                } else {
                    $minRequired = $auction->current_bid + $auction->min_increment;
                }

                if ($auction->status != 'Active' || Carbon::parse($auction->end_time)->lt($now)) {
                    return redirect()->route('auction.view', ['id' => $auctionId])
                        ->with('error', 'This auction is no longer active.');
                }

                if ($bidAmount < $minRequired) {
                    return redirect()->route('auction.view', ['id' => $auctionId])
                        ->with('error', "Bid too low. Minimum is $$minRequired.");
                }

                if ($auction->highest_bidder_id == $customerId) {
                    return redirect()->route('auction.view', ['id' => $auctionId])
                        ->with('error', 'You are already the highest bidder.');
                }

                // 3. Process the Bid: Update auction and insert history record
                $auction->update([
                    'current_bid' => $bidAmount,
                    'highest_bidder_id' => $customerId,
                ]);

                AuctionBid::create([
                    'auction_id' => $auctionId,
                    'customer_id' => $customerId,
                    'bid_amount' => $bidAmount,
                ]);

                return redirect()->route('auction.view', ['id' => $auctionId])
                    ->with('success', 'Bid placed successfully!');
            });

        } catch (\Exception $e) {
            return redirect()->route('auction.view', ['id' => $auctionId])
                ->with('error', $e->getMessage());
        }
    }


    public function auctionPurchase(Request $request)
    {
        $request->validate([
            'car_id' => 'required|integer',
            'auction_id' => 'required|integer',
            'price' => 'required|numeric',
        ]);

        $customerId = session('user_id'); // Matches session storage
        $carId = $request->input('car_id');
        $auctionId = $request->input('auction_id');
        $price = $request->input('price');

        try {
            return DB::transaction(function () use ($customerId, $carId, $auctionId, $price) {
                // 1. Fetch current auction and lock row to prevent double-purchasing
                $auction = Auction::where('id', $auctionId)->lockForUpdate()->first();

                // Check if auction is active
                if ($auction && $auction->status === 'Active') {
                    
                    // 2. Reduce Car Quantity if available (> 0)
                    $car = Car::find($carId);
                    if ($car && $car->quantity > 0) {
                        $car->update([
                            'quantity' => $car->quantity - 1
                        ]);
                    }

                    // 3. Mark Auction as 'Closed' and update bidding/winner fields
                    $auction->update([
                        'status' => 'Closed',
                        'current_bid' => $price,
                        'highest_bidder_id' => $customerId,
                        'winner_paid' => 0,
                    ]);

                    // 4. Record the winning bid in customer history
                    AuctionBid::create([
                        'auction_id' => $auctionId,
                        'customer_id' => $customerId,
                        'bid_amount' => $price,
                    ]);

                    return redirect()->route('auction.lobby')
                        ->with('success', 'Order placed! Please proceed with payment. Approval is pending manager confirmation.');

                } else {
                    // Auction is not active
                    return redirect()->route('auction.lobby')
                        ->with('error', 'Sorry, this auction is no longer available.');
                }
            });

        } catch (\Exception $e) {
            return redirect()->route('auction.lobby')
                ->with('error', 'An error occurred during the transaction.');
        }
    }


    public function builderAction(Request $request)
    {
        $userId = session('user_id');
        $action = $request->input('action');

        if (!$userId || !$action) {
            return redirect()->route('car.builder');
        }

        // Determine redirection fallback
        $redirect = $request->header('referer', route('customer.builder'));
        $urlParts = parse_url($redirect);
        $path = $urlParts['path'] ?? route('car.builder');
        $queryParams = [];

        if (isset($urlParts['query'])) {
            parse_str($urlParts['query'], $queryParams);
        }

        // Remove status messages so they don't stack up
        unset($queryParams['success'], $queryParams['error']);
        
        $newQuery = http_build_query($queryParams);
        $finalRedirect = $path;
        if (!empty($newQuery)) {
            $finalRedirect .= "?" . $newQuery;
        }
        $separator = (strpos($finalRedirect, '?') !== false) ? "&" : "?";

        try {
            // --- ACTIONS ---

            // Add or Update a specific part
            if ($action == "add_part" || $action == "select_part") {
                $catId = $request->input('cat_id');
                $pId = $request->input('p_id');

                BuilderTemporary::updateOrCreate(
                    ['user_id' => $userId, 'category_id' => $catId],
                    ['product_id' => $pId]
                );

                return redirect()->route('car.builder')->with('success', 'Part selected');
            } 
            
            // Remove a single part
            else if ($action == "remove_part") {
                $catId = $request->input('cat_id');
                
                BuilderTemporary::where('user_id', $userId)
                    ->where('category_id', $catId)
                    ->delete();
                
                return redirect($finalRedirect . $separator . "success=Part removed");
            }

            // Clear the entire temporary build
            else if ($action == "clear_all") {
                BuilderTemporary::where('user_id', $userId)->delete();
                
                return redirect($finalRedirect . $separator . "success=Build cleared");
            }

            // Toggle wishlist for a single product
            else if ($action == "wishlist_item") {
                $pId = $request->input('p_id');
                
                $wishlistItem = Wishlist::where('user_id', $userId)
                    ->where('product_id', $pId)
                    ->first();
                
                if ($wishlistItem) {
                    $wishlistItem->delete();
                    return redirect($finalRedirect . $separator . "success=Part removed from wishlist");
                } else {
                    Wishlist::create([
                        'user_id' => $userId,
                        'product_id' => $pId,
                    ]);
                    return redirect($finalRedirect . $separator . "success=Part added to wishlist");
                }
            }

            // Add a single product to the cart
            else if ($action == "cart_item") {
                $pId = $request->input('p_id');
                
                $cartItem = Cart::where('customer_id', $userId)
                    ->where('product_id', $pId)
                    ->first();
                
                if ($cartItem) {
                    $cartItem->increment('quantity');
                } else {
                    Cart::create([
                        'customer_id' => $userId,
                        'product_id' => $pId,
                        'quantity' => 1,
                    ]);
                }
                
                return redirect($finalRedirect . $separator . "success=Part added to cart");
            }

            // Save the entire current build
            else if ($action == "save_build") {
                $buildName = $request->input('build_name');

                $parts = BuilderTemporary::where('user_id', $userId)->get();

                if (count($parts) > 0) {
                    return DB::transaction(function () use ($userId, $buildName, $parts, $finalRedirect, $separator) {
                        $userBuild = UserBuild::create([
                            'user_id' => $userId,
                            'build_name' => $buildName,
                        ]);

                        for ($i = 0; $i < count($parts); $i = $i + 1)
                            {

                             BuildItem::create
                             ([
                                'build_id' => $userBuild->id,
                                'product_id' => $parts[$i]->product_id,
                            ]);
                        

                            }

                        return redirect()->route('saved.builds')->with('success', 'Build saved successfully');
                    });
                } else {
                    return redirect($finalRedirect . $separator . "error=Cannot save an empty build");
                }
            }

            // Add the entire build to the cart
            else if ($action == "add_build_to_cart") {
                $bId = $request->input('build_id');

                if ($bId) {
                    $parts = BuildItem::where('build_id', $bId)->get();
                } else {
                    $parts = BuilderTemporary::where('user_id', $userId)->get();
                }
                
                for ($i = 0; $i < count($parts); $i = $i + 1) {
                    $currPId = $parts[$i]->product_id;

                    $cartItem = Cart::where('customer_id', $userId)
                        ->where('product_id', $currPId)
                        ->first();

                    if ($cartItem) {
                        $cartItem->increment('quantity');
                    } else {
                        Cart::create([
                            'customer_id' => $userId,
                            'product_id' => $currPId,
                            'quantity' => 1,
                        ]);
                    }
                }
                
                return redirect($finalRedirect . $separator . "success=Entire build added to cart");
            }

            // Toggle wishlist for the ENTIRE build
            else if ($action == "wishlist_build") {
                $bId = $request->input('build_id');

                if ($bId) {
                    $parts = BuildItem::where('build_id', $bId)->get();
                } else {
                    $parts = BuilderTemporary::where('user_id', $userId)->get();
                }
                
                $totalParts = count($parts);

                if ($totalParts > 0) {
                    $wishCount = 0;
                    for ($i = 0; $i < $totalParts; $i = $i + 1) {
                        $currPId = $parts[$i]->product_id;
                        $exists = Wishlist::where('user_id', $userId)
                            ->where('product_id', $currPId)
                            ->exists();
                        
                        if ($exists) {
                            $wishCount = $wishCount + 1;
                        }
                    }

                    if ($wishCount == $totalParts) {
                        for ($i = 0; $i < $totalParts; $i = $i + 1) {
                            $currPId = $parts[$i]->product_id;
                            Wishlist::where('user_id', $userId)
                                ->where('product_id', $currPId)
                                ->delete();
                        }
                        return redirect($finalRedirect . $separator . "success=Build removed from wishlist");
                    } else {
                        for ($i = 0; $i < $totalParts; $i = $i + 1) {
                            $currPId = $parts[$i]->product_id;
                            Wishlist::firstOrCreate([
                                'user_id' => $userId,
                                'product_id' => $currPId,
                            ]);
                        }
                        return redirect($finalRedirect . $separator . "success=Entire build added to wishlist");
                    }
                } else {
                    return redirect($finalRedirect . $separator . "error=Empty build");
                }
            }

        } catch (\Exception $e) {
            return redirect()->route('car.builder')->with('error', 'An error occurred during the action.');
        }

        return redirect()->route('car.builder');
    }


    public function carPurchase(Request $request)
    {
        $request->validate([
            'car_id' => 'required|integer',
            'price' => 'required|numeric|min:0',
        ]);

        $userId = session('user_id');
        $carId = $request->input('car_id');
        $price = $request->input('price');

        try {
            return DB::transaction(function () use ($userId, $carId, $price) {
                // 1. Check if car is still available with pessimistic locking
                $car = Car::where('id', $carId)->lockForUpdate()->first();

                if ($car && $car->quantity > 0) {
                    // 2. Insert into orders table
                    Order::create([
                        'customer_id' => $userId,
                        'car_id' => $carId,
                        'quantity' => 1,
                        'total_price' => $price,
                        // date_ordered defaults or handles automatically via database/timestamp
                    ]);

                    // 3. Update Car Stock and Status
                    $newQty = $car->quantity - 1;
                    
                    // Note: Your legacy status enum supports 'In Stock', 'Upcoming', 'Out of Stock' based on schema. 
                    // Let's use 'Out of Stock' or 'In Stock' accordingly.
                    $newStatus = ($newQty <= 0) ? 'Out of Stock' : 'In Stock';

                    $car->update([
                        'quantity' => $newQty,
                        'status' => $newStatus,
                    ]);

                    return redirect()->route('showroom')->with('success', 'Congratulations! Your vehicle purchase is complete.');
                } else {
                    return redirect()->route('showroom')->with('error', 'Sorry, this vehicle just sold out.');
                }
            });

        } catch (\Exception $e) {
            return redirect()->route('showroom')->with('error', 'An error occurred during the purchase.');
        }
    }


    public function changePassword(Request $request)
    {
        $request->validate([
            'old_pass' => 'required',
            'new_pass' => 'required|string|min:6',
            'c_new_pass' => 'required|same:new_pass',
        ], [
            'old_pass.required' => 'Old password is required',
            'new_pass.required' => 'New password is required',
            'c_new_pass.required' => 'Please confirm your new password',
            'c_new_pass.same' => 'New password and confirmation do not match',
        ]);

        $userId = session('user_id'); // Matches session storage
        $oldPass = $request->input('old_pass');
        $newPass = $request->input('new_pass');

        // 1. Fetch user by 'uid' column matching the legacy schema
        $user = User::where('uid', $userId)->first();

        if (!$user) {
            return redirect()->route('customer.pass')->with('error', 'User not found.');
        }

        // 2. Verify the old password using Hash facade
        if (Hash::check($oldPass, $user->password)) {
            
            // 3 & 4. Hash the new password and update record using 'uid'
            $user->update([
                'password' => Hash::make($newPass),
            ]);

            return redirect()->route('customer.pass')
                ->with('success', 'The password has been changed successfully!');
        } else {
            return redirect()->route('customer.pass')
                ->with('error', 'Incorrect old password');
        }
    }

    public function checkout(Request $request)
    {
        $userId = session('user_id');

        if (!$userId) {
            return redirect()->route('login');
        }

        try {
            return DB::transaction(function () use ($userId) {
                // 1. Get all items in user's cart joined with product prices
                $items = Cart::where('customer_id', $userId)
                    ->join('products', 'cart.product_id', '=', 'products.id')
                    ->select('cart.*', 'products.price')
                    ->get();

                if ($items->isEmpty()) {
                    return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
                }

                for ($i = 0; $i < count($items); $i = $i + 1) {
                    $item = $items[$i];
                    $total = $item->price * $item->quantity;

                    // 2. Insert into orders table
                    Order::create([
                        'customer_id' => $userId,
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'total_price' => $total,
                    ]);

                    // 3. Update stock on products
                    $product = Product::find($item->product_id);
                    if ($product) {
                        $product->decrement('quantity', $item->quantity);
                    }
                }

                // 4. Clear the cart
                Cart::where('customer_id', $userId)->delete();

                return redirect()->route('history')->with('success', 'Thank you for your purchase!');
            });

        } catch (\Exception $e) {
            return redirect()->route('cart.index')->with('error', 'An error occurred during checkout.');
        }
    }

    public function deleteBuild(Request $request, $id)
    {
        $userId = session('user_id');

        if (!$userId) {
            return redirect()->route('login');
        }

        try {
            return DB::transaction(function () use ($userId, $id) {
                // 1. Security Check: Verify this build actually belongs to this user
                $userBuild = UserBuild::where('id', $id)
                    ->where('user_id', $userId)
                    ->first();

                if (!$userBuild) {
                    return redirect()->route('saved.builds')
                        ->with('error', 'You do not have permission to delete this build.');
                }

                // 2. Delete items associated with the build first
                BuildItem::where('build_id', $id)->delete();

                // 3. Delete the build itself
                $userBuild->delete();

                return redirect()->route('saved.builds')
                    ->with('success', 'Build deleted successfully!');
            });

        } catch (\Exception $e) {
            return redirect()->route('saved.builds')
                ->with('error', 'Error occurred while deleting.');
        }
    }

    public function editReview(Request $request)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string',
            'review_id' => 'required|integer',
            'product_id' => 'required|integer',
        ]);

        $userId = session('user_id');
        $rating = $request->input('rating');
        $comment = $request->input('comment');
        $reviewId = $request->input('review_id');
        $productId = $request->input('product_id');

        if (!$userId) {
            return redirect()->route('login');
        }

        // 1. Security check: Ensure this review actually belongs to this user
        $review = Review::where('id', $reviewId)
            ->where('user_id', $userId)
            ->first();

        if (!$review) {
            return redirect()->route('product.view', ['id' => $productId])
                ->with('error', 'Unauthorized action');
        }

        // 2. Update the review
        $updated = $review->update([
            'rating' => $rating,
            'comment' => $comment,
        ]);

        if ($updated) {
            return redirect()->route('product.view', ['id' => $productId])
                ->with('success', 'Review updated successfully!');
        } else {
            return redirect()->route('product.view', ['id' => $productId])
                ->with('error', 'Failed to update review');
        }
    }

    public function placeOrder(Request $request)
    {
        $userId = session('user_id');

        if (!$userId) {
            return redirect()->route('login');
        }

        try {
            return DB::transaction(function () use ($userId) {
                // 1. Fetch all cart items joined with products, locking the product rows for concurrency safety
                $cartItems = Cart::where('customer_id', $userId)
                    ->join('products', 'cart.product_id', '=', 'products.id')
                    ->select('cart.*', 'products.price', 'products.quantity as stock', 'products.product_name')
                    ->get();

                // Check if cart has items
                if ($cartItems->isEmpty()) {
                    return redirect()->route('store')->with('error', 'Order failed: Your cart was empty.');
                }

                // 2. Loop through for validation first (Safety Check)
                for ( $i = 0; $i < count($cartItems); $i = $i + 1) {
                    $item = $cartItems[$i];
                    
                    if ($item->quantity > $item->stock) {
                        $name = $item->product_name;
                        return redirect()->route('cart.index')->with('error', "Not enough stock for $name");
                    }
                }

                // 3. Loop through to process the purchase
                $dateOrdered = Carbon::now()->toDateString();

                for ($i = 0; $i < count($cartItems); $i = $i + 1) {
                    $item = $cartItems[$i];
                    $pId = $item->product_id;
                    $qty = $item->quantity;
                    $price = $item->price;
                    $total = $price * $qty;

                    // A. Create the Order record
                    Order::create([
                        'customer_id' => $userId,
                        'product_id' => $pId,
                        'quantity' => $qty,
                        'total_price' => $total,
                        'date_ordered' => $dateOrdered,
                    ]);

                    // B. Reduce the Stock from products table using pessimistic lock / decrement
                    $product = Product::where('id', $pId)->lockForUpdate()->first();
                    if ($product) {
                        $product->decrement('quantity', $qty);
                    }
                }

                // 4. Clear the cart for this user after success
                Cart::where('customer_id', $userId)->delete();

                return redirect()->route('history')->with('success', 'Order placed successfully!');
            });

        } catch (\Exception $e) {
            return redirect()->route('cart.index')->with('error', 'An error occurred while placing your order.');
        }
    }

    public function postReview(Request $request)
    {
        $userId = session('user_id');

        if (!$userId) {
            return redirect()->route('login');
        }

        $action = $request->input('action', $request->query('action', ''));
        $productId = $request->input('product_id', $request->query('product_id'));
        $carId = $request->input('car_id', $request->query('car_id'));

        // Determine default fallback redirect
        $redirectUrl = route('showroom');
        if (!empty($productId)) {
            $redirectUrl = route('product.view', ['id' => $productId]);
        } elseif (!empty($carId)) {
            $redirectUrl = route('car.view', ['id' => $carId]);
        }

        try {
            // --- ACTION: DELETE ---
            if ($action === 'delete' && $request->has('review_id')) {
                $reviewId = $request->query('review_id');

                $review = Review::where('id', $reviewId)
                    ->where('user_id', $userId)
                    ->first();

                if ($review && $review->delete()) {
                    return redirect($redirectUrl)->with('success', 'Review deleted successfully');
                } else {
                    return redirect($redirectUrl)->with('error', 'Could not delete review');
                }
            }

            // --- ACTION: ADD OR UPDATE (Requires rating and comment) ---
            if ($request->has(['rating', 'comment'])) {
                $request->validate([
                    'rating' => 'required|integer|min:1|max:5',
                    'comment' => 'required|string',
                ]);

                $rating = $request->input('rating');
                $comment = $request->input('comment');

                if ($action === 'add') {
                    Review::create([
                        'product_id' => $productId,
                        'car_id' => $carId,
                        'user_id' => $userId,
                        'rating' => $rating,
                        'comment' => $comment,
                        'date_posted' => Carbon::now()->toDateString(),
                    ]);

                    return redirect($redirectUrl)->with('success', 'Review posted!');
                } 
                
                else if ($action === 'update' && $request->has('review_id')) {
                    $reviewId = $request->input('review_id');

                    $review = Review::where('id', $reviewId)
                        ->where('user_id', $userId)
                        ->first();

                    if ($review) {
                        $review->update([
                            'rating' => $rating,
                            'comment' => $comment,
                        ]);

                        return redirect($redirectUrl)->with('success', 'Review updated!');
                    } else {
                        return redirect($redirectUrl)->with('error', 'Unauthorized action');
                    }
                }
            }

        } catch (\Exception $e) {
            return redirect($redirectUrl)->with('error', 'An error occurred while processing your review.');
        }

        return redirect($redirectUrl);
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'email_address' => 'required|email|max:255',
            'old_password' => 'required',
            'phone_number' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'new_password' => 'nullable|string|min:6',
            'confirm_password' => 'nullable|same:new_password',
        ], [
            'fname.required' => 'Required fields are missing',
            'lname.required' => 'Required fields are missing',
            'email_address.required' => 'Required fields are missing',
            'old_password.required' => 'Required fields are missing',
            'confirm_password.same' => 'New passwords did not match',
        ]);

        $userId = session('user_id'); // Matches session storage

        if (!$userId) {
            return redirect()->route('login');
        }

        $oldPass = $request->input('old_password');
        $newPass = $request->input('new_password');
        $confirmPass = $request->input('confirm_password');

        try {
            return DB::transaction(function () use ($request, $userId, $oldPass, $newPass, $confirmPass) {
                // 1. Verify the current password from the users table
                $user = User::where('uid', $userId)->first();

                if (!$user || !Hash::check($oldPass, $user->password)) {
                    return redirect()->route('customer.profile.edit')
                        ->with('error', 'Incorrect current password');
                }

                // 2. Update Personal Info in the customer table
                $customer = Customer::where('user_id', $userId)->first();

                if ($customer) {
                    $customer->update([
                        'fname' => $request->input('fname'),
                        'lname' => $request->input('lname'),
                        'email_address' => $request->input('email_address'),
                        'phone_number' => $request->input('phone_number'),
                        'address' => $request->input('address'),
                    ]);
                }

                // 3. Update Password if a new one was provided
                if (!empty($newPass)) {
                    if ($newPass === $confirmPass) {
                        $user->update([
                            'password' => Hash::make($newPass),
                        ]);
                    } else {
                        return redirect()->route('customer.profile.edit')
                            ->with('error', 'Profile updated, but new passwords did not match');
                    }
                }

                return redirect()->route('customer.profile.edit')
                    ->with('success', 'Profile updated successfully!');
            });

        } catch (\Exception $e) {
            return redirect()->route('customer.profile.edit')
                ->with('error', 'An error occurred while updating your profile.');
        }
    }

    public function reduceQuantity(Request $request, $cartId)
    {
        $userId = session('user_id');

        if (!$userId) {
            return redirect()->route('login');
        }

        // 1. Fetch the cart item ensuring it belongs to the authenticated customer
        $cartItem = Cart::where('id', $cartId)
            ->where('customer_id', $userId)
            ->first();

        if (!$cartItem) {
            return redirect()->route('cart.index');
        }

        $currentQty = $cartItem->quantity;

        // 2. Reduce quantity if greater than 1
        if ($currentQty > 1) {
            $updated = $cartItem->update([
                'quantity' => $currentQty - 1,
            ]);

            if ($updated) {
                return redirect()->route('cart.index')->with('success', 'Quantity updated successfully');
            } else {
                return redirect()->route('cart.index')->with('error', 'Unknown error occurred');
            }
        } else {
            // If quantity is already 1, do not reduce further
            return redirect()->route('cart.index');
        }
    }

    public function removeFromCart(Request $request, $cartId)
    {
        $userId = session('user_id');

        if (!$userId) {
            return redirect()->route('login');
        }

        // Delete only if it belongs to the logged-in user for security
        $cartItem = Cart::where('id', $cartId)
            ->where('customer_id', $userId)
            ->first();

        if ($cartItem) {
            $deleted = $cartItem->delete();

            if ($deleted) {
                return redirect()->route('cart.index')->with('success', 'Item removed from cart');
            } else {
                return redirect()->route('cart.index')->with('error', 'Error occurred');
            }
        } else {
            return redirect()->route('cart.index');
        }
    }

    public function storeRepairRequest(Request $request)
    {
        $request->validate([
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'year' => 'required|integer',
            'mileage' => 'required|integer',
            'service_type' => 'required|string',
            'car_image' => 'required|image|mimes:jpg,jpeg,png',
        ], [
            'car_image.required' => 'Unknown error occurred while uploading image',
            'car_image.image' => 'You cannot upload files of this type',
            'car_image.mimes' => 'You cannot upload files of this type',
        ]);

        $userId = session('user_id');

        if (!$userId) {
            return redirect()->route('login');
        }

        $brand = $request->input('brand');
        $model = $request->input('model');
        $year = $request->input('year');
        $mileage = $request->input('mileage');
        $serviceType = $request->input('service_type');
        $description = $request->input('description');

        // Logic for Labor Fee and Repair Type
        $laborFee = 0;
        $repairType = "Service"; // Default

        if ($serviceType == "Regular Service") {
            $laborFee = 500;
            $repairType = "Service";
        } else if ($serviceType == "Premium Service") {
            $laborFee = 1000;
            $repairType = "Service";
        } else if ($serviceType == "Repair Car") {
            $laborFee = 300;
            $repairType = "Repair";
        }

        // Allowed extensions check using standard for loop as per style preference
        $file = $request->file('car_image');
        if ($file && $file->isValid()) {
            $imgExLc = strtolower($file->getClientOriginalExtension());
            $allowedExs = ["jpg", "jpeg", "png"];
            
            $isAllowed = false;
            for ( $i = 0; $i < count($allowedExs); $i = $i + 1) {
                if ($imgExLc == $allowedExs[$i]) {
                    $isAllowed = true;
                }
            }

            if (!$isAllowed) {
                return redirect()->route('repair.add')->with('error', 'You cannot upload files of this type');
            }

            // Generate unique image name and store in public/uploads directory
            $newImgName = "CAR-" . uniqid('', true) . '.' . $imgExLc;
            $file->move(public_path('uploads'), $newImgName);

            // SQL Insertion via Eloquent Model
            $repair = CarRepair::create([
                'customer_id' => $userId,
                'repair_type' => $repairType,
                'brand' => $brand,
                'model' => $model,
                'year' => $year,
                'mileage' => $mileage,
                'issue_description' => $description,
                'car_image' => $newImgName,
                'status' => 'Pending',
                'labor_fee' => $laborFee,
            ]);

            if ($repair) {
                return redirect()->route('customer.repair.dashboard')->with('success', 'Request submitted successfully');
            } else {
                return redirect()->route('repair.add')->with('error', 'An error occurred during submission');
            }
        } else {
            return redirect()->route('repair.add')->with('error', 'Unknown error occurred while uploading image');
        }
    }


    public function cancelRepairRequest(Request $request, $id)
    {
        $userId = session('user_id');

        if (!$userId) {
            return redirect()->route('login');
        }

        // 1. Verify the repair exists and belongs to this customer
        $repair = CarRepair::where('repair_id', $id)
            ->where('customer_id', $userId)
            ->first();

        if (!$repair) {
            return redirect()->route('customer.repair.dashboard');
        }

        $currentStatus = $repair->status;

        // 2. Only allow deletion if status is Pending
        if ($currentStatus === 'Pending') {
            $deleted = $repair->delete();

            if ($deleted) {
                return redirect()->route('customer.repair.dashboard')->with('success', 'Request deleted successfully!');
            } else {
                return redirect()->route('customer.repair.dashboard')->with('error', 'Unknown error occurred');
            }
        } else {
            return redirect()->route('customer.repair.dashboard')->with('error', 'In-progress repairs cannot be deleted');
        }
    }

    public function declineRepairParts(Request $request, $id)
    {
        $userId = session('user_id');

        if (!$userId) {
            return redirect()->route('login');
        }

        try {
            return DB::transaction(function () use ($userId, $id) {
                // 1. Verify ownership and lock the row to prevent race conditions
                $repair = CarRepair::where('repair_id', $id)
                    ->where('customer_id', $userId)
                    ->lockForUpdate()
                    ->first();

                if (!$repair) {
                    return redirect()->route('customer.repair.dashboard');
                }

                if ($repair->status === 'Awaiting Payment') {
                    
                    // 2. Set fixed diagnostics fee (300), clear parts total, and set status to 'Ready'
                    $repair->update([
                        'labor_fee' => 300,
                        'parts_total' => 0,
                        'status' => 'Ready',
                    ]);

                    // 4. Get the snapshot parts before deleting them to return them to stock
                    $partsToRemove = RepairPartsSelected::where('repair_id', $id)->get();
                    $partsCount = count($partsToRemove);

                    for ( $i = 0; $i < $partsCount; $i = $i + 1) {
                        $pId = $partsToRemove[$i]->product_id;

                        // Check and restore stock safely
                        $product = Product::where('id', $pId)->lockForUpdate()->first();
                        if ($product) {
                            $product->increment('quantity', 1);
                        }
                    }

                    // 5. Delete the snapshots because customer declined the repair parts
                    RepairPartsSelected::where('repair_id', $id)->delete();

                    $sm = "Parts declined. Please pay the $300 diagnostics fee upon pickup.";
                    return redirect()->route('repair.view', ['id' => $id])->with('success', $sm);

                } else {
                    return redirect()->route('repair.view', ['id' => $id])->with('error', 'This repair is not in a payable state.');
                }
            });

        } catch (\Exception $e) {
            return redirect()->route('repair.view', ['id' => $id])->with('error', 'Error updating repair record.');
        }
    }

    public function repairTransaction(Request $request, $id)
    {
        $userId = session('user_id');

        if (!$userId) {
            return redirect()->route('login');
        }

        // 1. Verify the repair exists and belongs to the user
        $repair = CarRepair::where('repair_id', $id)
            ->where('customer_id', $userId)
            ->first();

        if (!$repair) {
            return redirect()->route('customer.repair.dashboard');
        }

        // 2. Update the status to 'Repairing'
        $updated = $repair->update([
            'status' => 'Repairing',
        ]);

        if ($updated) {
            $sm = "Payment successful! Your car status is now 'Repairing'.";
            return redirect()->route('customer.repair.view', ['id' => $id])->with('success', $sm);
        } else {
            $em = "An error occurred during status update.";
            return redirect()->route('customer.repair.view', ['id' => $id])->with('error', $em);
        }
    }
    public function deleteMyReview(Request $request, $id)
    {
        $userId = session('user_id');

        if (!$userId) {
            return redirect()->route('login');
        }

        // Find the review ensuring it belongs to the logged-in customer for security
        $review = Review::where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if ($review) {
            $deleted = $review->delete();

            if ($deleted) {
                return redirect()->route('my.reviews')->with('success', 'Review deleted successfully');
            } else {
                return redirect()->route('my.reviews')->with('error', 'Delete failed');
            }
        } else {
            return redirect()->route('my.reviews')->with('error', 'Review not found or unauthorized');
        }
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'message_text' => 'required|string',
            'full_name' => 'required|string|max:255',
            'email_address' => 'required|email|max:255',
        ], [
            'message_text.required' => 'Message is required',
        ]);

        $userId = session('user_id');

        if (!$userId) {
            return redirect()->route('login');
        }

        $message = $request->input('message_text');
        $fullName = $request->input('full_name');
        $email = $request->input('email_address');

        // Create the message record with 'Unread' status
        $sent = Message::create([
            'sender_full_name' => $fullName,
            'sender_email' => $email,
            'message' => $message,
            'status' => 'Unread',
        ]);

        if ($sent) {
            return redirect()->route('contact')->with('success', 'Message sent successfully!');
        } else {
            return redirect()->route('contact')->with('error', 'An error occurred during insertion');
        }
    }

    public function submitWarrantyClaim(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer',
            'claim_type' => 'required|string',
            'reason' => 'required|string',
        ], [
            'reason.required' => 'Reason is required',
        ]);

        $userId = session('user_id');

        if (!$userId) {
            return redirect()->route('login');
        }

        $orderId = $request->input('order_id');
        $claimType = $request->input('claim_type');
        $reason = $request->input('reason');

        // Check if a claim already exists for this order to prevent duplicates
        $existingClaim = WarrantyClaim::where('order_id', $orderId)->first();

        if ($existingClaim) {
            return redirect()->route('history')->with('error', 'A claim has already been submitted for this order.');
        }

        // Insert into warranty_claims with status set to 'Pending'
        $claim = WarrantyClaim::create([
            'order_id' => $orderId,
            'customer_id' => $userId,
            'claim_type' => $claimType,
            'reason' => $reason,
            'status' => 'Pending',
        ]);

        if ($claim) {
            return redirect()->route('history')->with('success', 'Warranty claim submitted successfully!');
        } else {
            return redirect()->route('history')->with('error', 'Database error occurred');
        }
    }


    public function wishlistHandle(Request $request, $id)
    {
        $userId = session('user_id');

        if (!$userId) {
            return redirect()->route('login');
        }

        $type = $request->query('type', 'product');
        $action = $request->query('action', 'add');
        $source = $request->query('source');

        // Determine where the user should go back to
        $redirectTo = route('store');

        if ($source === 'view') {
            $redirectTo = route('product.view', ['id' => $id]);
        } elseif ($source === 'wishlist') {
            $redirectTo = route('wishlist.index');
        } elseif ($source === 'showroom') {
            $redirectTo = route('showroom');
        } elseif ($source === 'car-view') {
            $redirectTo = route('car.view', ['id' => $id]);
        }

        // --- REMOVE ACTION ---
        if ($action === 'remove') {
            $query = Wishlist::where('user_id', $userId);

            if ($type === 'car') {
                $query->where('car_id', $id);
            } else {
                $query->where('product_id', $id);
            }

            $deleted = $query->delete();

            if ($deleted) {
                return redirect($redirectTo)->with('success', 'Removed from wishlist!');
            }

            return redirect($redirectTo)->with('error', 'Could not remove from wishlist.');
        }

        // --- ADD ACTION ---
        $query = Wishlist::where('user_id', $userId);

        if ($type === 'car') {
            $query->where('car_id', $id);
        } else {
            $query->where('product_id', $id);
        }

        $existing = $query->first();

        if ($existing) {
            return redirect($redirectTo)->with('info', 'Item is already in your wishlist!');
        }

        // Insert into database
        $created = Wishlist::create([
            'user_id' => $userId,
            'car_id' => ($type === 'car') ? $id : null,
            'product_id' => ($type === 'product') ? $id : null,
        ]);

        if ($created) {
            return redirect($redirectTo)->with('success', 'Added to wishlist!');
        } else {
            return redirect($redirectTo)->with('error', 'Could not add to wishlist.');
        }
    }

    public function auctionHistory()
    {
        $userId = session('user_id');
        $role = session('role');

        if (!$userId || $role !== 'Customer') {
            return redirect()->route('login');
        }

        // Fetch distinct auctions the customer has placed bids on, along with car details
        $bids = AuctionBid::where('customer_id', $userId)
            ->with(['auction.car'])
            ->get();

        // Group by auction to get unique auctions and compute max bid per auction for this customer
        $myAuctions = $bids->groupBy('auction_id')->map(function ($auctionBids) use ($userId) {
            $auction = $auctionBids->first()->auction;
            $maxBid = $auctionBids->max('bid_amount');

            return [
                'id' => $auction ? $auction->id : null,
                'end_time' => $auction ? $auction->end_time : null,
                'status' => $auction ? $auction->status : null,
                'current_bid' => $auction ? $auction->current_bid : 0,
                'highest_bidder_id' => $auction ? $auction->highest_bidder_id : null,
                'brand' => $auction && $auction->car ? $auction->car->brand : '',
                'model' => $auction && $auction->car ? $auction->car->model : '',
                'year' => $auction && $auction->car ? $auction->car->year : '',
                'image' => $auction && $auction->car ? $auction->car->image : '',
                'my_max' => $maxBid,
            ];
        })->sortByDesc('end_time')->values();

        return view('customer.auction-history', compact('myAuctions'));
    }

    public function auctionLobby(Request $request)
    {
        $userId = session('user_id');
        $role = session('role');

        if (!$userId || $role !== 'Customer') {
            return redirect()->route('login');
        }

        // Fetch current system year
        $setting = Setting::first();
        $currentSystemYear = $setting ? $setting->current_year : date('Y');

        // Fetch categories for filter dropdown
        $categories = Category::where('type', 'Car')->orderBy('category_name', 'ASC')->get();
        $transmissions = ["Automatic", "Manual", "CVT", "DCT", "AMT"];

        // Build query for active auctions with car and category relations
        $query = Auction::where('status', 'Active')
            ->with(['car.category']);

        // Apply Filters
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('car', function ($q) use ($search) {
                $q->where('model', 'like', "%{$search}%")
                ->orWhere('brand', 'like', "%{$search}%");
            });
        }

        if ($request->filled('cat_id')) {
            $catId = $request->input('cat_id');
            $query->whereHas('car', function ($q) use ($catId) {
                $q->where('category_id', $catId);
            });
        }

        if ($request->filled('min_price')) {
            $query->where('current_bid', '>=', $request->input('min_price'));
        }

        if ($request->filled('max_price')) {
            $query->where('current_bid', '<=', $request->input('max_price'));
        }

        if ($request->filled('min_year')) {
            $minYear = $request->input('min_year');
            $query->whereHas('car', function ($q) use ($minYear) {
                $q->where('year', '>=', $minYear);
            });
        }

        if ($request->filled('max_year')) {
            $maxYear = $request->input('max_year');
            $query->whereHas('car', function ($q) use ($maxYear) {
                $q->where('year', '<=', $maxYear);
            });
        }

        if ($request->filled('max_mileage')) {
            $maxMileage = $request->input('max_mileage');
            $query->whereHas('car', function ($q) use ($maxMileage) {
                $q->where('mileage', '<=', $maxMileage);
            });
        }

        if ($request->filled('trans')) {
            $trans = $request->input('trans');
            $query->whereHas('car', function ($q) use ($trans) {
                $q->where('transmission', $trans);
            });
        }

        $auctions = $query->orderBy('end_time', 'ASC')->get();

        return view('customer.auction-lobby', compact('auctions', 'categories', 'transmissions', 'currentSystemYear'));
    }

    public function auctionView($id)
    {
        $userId = session('user_id');
        $role = session('role');

        if (!$userId || $role !== 'Customer') {
            return redirect()->route('login');
        }

        // Fetch Auction with car and category relations
        $auction = Auction::with(['car.category', 'bids.customer'])
            ->where('id', $id)
            ->first();

        if (!$auction) {
            return redirect()->route('customer.auction.lobby')->with('error', 'This vehicle is currently unavailable');
        }

        // Fetch Bid History ordered by time descending
        $bidHistory = AuctionBid::with('customer')
            ->where('auction_id', $id)
            ->orderBy('bid_time', 'DESC')
            ->get();

        // Calculate minimum next bid
        if (empty($auction->highest_bidder_id) || $auction->highest_bidder_id == 0) {
            $minNextBid = $auction->start_price;
        } else {
            $minNextBid = $auction->current_bid + $auction->min_increment;
        }

        $isHighestBidder = ($auction->highest_bidder_id == $userId);

        return view('customer.auction-view', compact('auction', 'bidHistory', 'minNextBid', 'isHighestBidder'));
    }

    public function builderSelectPart(Request $request)
    {
        $userId = session('user_id');
        $role = session('role');

        if (!$userId || $role !== 'Customer' || !$request->has('cat_id')) {
            return redirect()->route('customer.builder');
        }

        $catId = $request->input('cat_id');

        // 1. Fetch the category name
        $category = Category::find($catId);
        if (!$category) {
            return redirect()->route('customer.builder');
        }

        // 2. Fetch tags from the database for the dropdown
        $dbTags = Tag::all();

        // 3. Determine Required Tags for compatibility (exclude current category)
        $currentPartsTags = BuilderTemporary::where('user_id', $userId)
            ->where('category_id', '!=', $catId)
            ->join('products', 'builder_temporary.product_id', '=', 'products.id')
            ->pluck('products.tags');

        $requiredTags = [];

        foreach ($currentPartsTags as $rawTags) {
            if (empty($rawTags)) continue;
            $tagsArray = explode(',', $rawTags);
            $tempSpecific = [];
            $hasBrand = false;

            foreach ($tagsArray as $t) {
                $t = trim($t);
                if ($t !== "" && strtolower($t) !== "null" && strtolower($t) !== "universal") {
                    $tempSpecific[] = $t;
                    $hasBrand = true;
                }
            }

            if ($hasBrand) {
                if (empty($requiredTags)) {
                    $requiredTags = $tempSpecific;
                } else {
                    $requiredTags = array_values(array_intersect($requiredTags, $tempSpecific));
                }
            }
        }

        // 4. Filtering and Sorting inputs
        $filter = $request->input('filter', 'all');
        $search = $request->input('search', '');
        $tagFilter = $request->input('tag_filter', '');
        $sort = $request->input('sort', 'name_asc');

        // 5. Build Main Query
        $query = Product::where('category_id', $catId);

        if (!empty($requiredTags)) {
            $query->where(function ($q) use ($requiredTags) {
                $q->where('tags', 'like', '%Universal%')
                ->orWhereNull('tags')
                ->orWhere('tags', '');

                foreach ($requiredTags as $reqTag) {
                    $q->orWhere('tags', 'like', '%' . trim($reqTag) . '%');
                }
            });
        }

        if ($filter == 'available') {
            $query->where('status', 'In Stock')->where('quantity', '>', 0);
        } elseif ($filter == 'upcoming') {
            $query->where('status', 'Upcoming');
        } elseif ($filter == 'out_of_stock') {
            $query->where('status', 'Out of Stock');
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                ->orWhere('tags', 'like', "%{$search}%");
            });
        }

        if (!empty($tagFilter)) {
            $query->where('tags', 'like', "%{$tagFilter}%");
        }

        if ($sort == 'price_low') {
            $query->orderBy('price', 'ASC');
        } elseif ($sort == 'price_high') {
            $query->orderBy('price', 'DESC');
        } elseif ($sort == 'name_desc') {
            $query->orderBy('product_name', 'DESC');
        } else {
            $query->orderBy('product_name', 'ASC');
        }

        $products = $query->get();

        return view('customer.builder-select-part', compact('category', 'catId', 'dbTags', 'products', 'filter', 'search', 'tagFilter', 'sort'));
    }

    public function carBuilder(Request $request)
    {
        $userId = session('user_id');
        $role = session('role');

        if (!$userId || $role !== 'Customer') {
            return redirect()->route('login');
        }

        // 1. Save new selection from GET to Database if present
        if ($request->has('cat_id') && $request->has('p_id')) {
            $cId = $request->input('cat_id');
            $pId = $request->input('p_id');

            BuilderTemporary::updateOrCreate(
                ['user_id' => $userId, 'category_id' => $cId],
                ['product_id' => $pId]
            );

            return redirect()->route('customer.builder');
        }

        // 2. Fetch current progress
        $dbSelections = BuilderTemporary::where('user_id', $userId)
            ->pluck('product_id', 'category_id')
            ->toArray();

        // Fetch current cart items to check against stock levels
        $currentCart = Cart::where('customer_id', $userId)
            ->pluck('quantity', 'product_id')
            ->toArray();

        // 3. Fetch Categories (Mandatory and Optional Parts)
        $mandatoryCats = Category::where('is_mandatory', 1)
            ->where('type', 'Part')
            ->orderBy('category_name', 'ASC')
            ->get();

        $optionalCats = Category::where('is_mandatory', 0)
            ->where('type', 'Part')
            ->orderBy('category_name', 'ASC')
            ->get();

        // 4. Logic to check if the ENTIRE build is already in the wishlist
        $isBuildWishlisted = Wishlist::where('user_id', $userId)
            ->whereNull('product_id')
            ->exists();

        $totalPrice = 0;
        $totalHp = 0;
        $hasUnavailableParts = false;
        $buildStockExceeded = false;

        // 5. --- COMPATIBILITY LOGIC ---
        $allSelectedProductIds = array_values($dbSelections);
        $isCompatible = true;
        $contradictionError = "";
        $commonTags = null;

        if (count($allSelectedProductIds) > 1) {
            // --- PART A: Common Tag Check ---
            foreach ($allSelectedProductIds as $currentPid) {
                $product = Product::find($currentPid);

                if (!$product) {
                    $isCompatible = false;
                    continue;
                }

                $tagsString = $product->tags;
                $currentProductTags = [];

                if (empty($tagsString)) {
                    $currentProductTags[] = 'universal';
                } else {
                    $tempTags = explode(',', $tagsString);
                    foreach ($tempTags as $t) {
                        $cleanedTag = strtolower(trim($t));
                        if ($cleanedTag !== "") {
                            $currentProductTags[] = $cleanedTag;
                        }
                    }
                }

                $currentIsUniversal = in_array('universal', $currentProductTags);

                if ($commonTags === null) {
                    $commonTags = $currentProductTags;
                } else {
                    $newCommon = [];
                    $commonIsUniversal = in_array('universal', $commonTags);

                    if ($currentIsUniversal) {
                        $newCommon = $commonTags;
                    } elseif ($commonIsUniversal) {
                        $newCommon = $currentProductTags;
                    } else {
                        foreach ($commonTags as $ct) {
                            if (in_array($ct, $currentProductTags)) {
                                $newCommon[] = $ct;
                            }
                        }
                    }
                    $commonTags = $newCommon;
                }
            }

            if (count($commonTags) === 0) {
                $isCompatible = false;
            }

            // --- PART B: Contradiction Check (The "No-" Tag Logic) ---
            foreach ($dbSelections as $catIdKey => $prodIdVal) {
                $product = Product::find($prodIdVal);

                if ($product && !empty($product->tags)) {
                    $pTagsArr = explode(',', $product->tags);
                    foreach ($pTagsArr as $tagStr) {
                        $tag = strtolower(trim($tagStr));
                        if (str_starts_with($tag, 'no-')) {
                            $restrictedCatName = substr($tag, 3);

                            foreach ($dbSelections as $checkCatId => $checkProdId) {
                                $categoryModel = Category::find($checkCatId);
                                if ($categoryModel && strtolower($categoryModel->category_name) == $restrictedCatName) {
                                    $isCompatible = false;
                                    $contradictionError = "One of your parts restricts the use of: " . ucfirst($restrictedCatName);
                                }
                            }
                        }
                    }
                }
            }
        }

        return view('customer.car-builder', compact(
            'dbSelections', 'currentCart', 'mandatoryCats', 'optionalCats', 
            'isBuildWishlisted', 'totalPrice', 'totalHp', 'hasUnavailableParts', 
            'buildStockExceeded', 'isCompatible', 'contradictionError', 'userId'
        ));
    }

    public function viewCart()
    {
        $userId = session('user_id');
        $role = session('role');

        if (!$userId || $role !== 'Customer') {
            return redirect()->route('login');
        }

        // Fetch items from the cart table joined with products using Eloquent
        $items = Cart::with('product')
            ->where('customer_id', $userId)
            ->get();

        $canPlaceOrder = true;

        // Transform items to match view expectations smoothly
        $cartItems = [];
        $grandTotal = 0;

        foreach ($items as $item) {
            if (!$item->product) continue;

            $subtotal = $item->product->price * $item->quantity;
            $grandTotal += $subtotal;

            $isAvailable = true;
            $insufficientStock = false;

            // Check general status/stock existence
            if ($item->product->quantity <= 0 || $item->product->status != 'In Stock') {
                $isAvailable = false;
                $canPlaceOrder = false;
            }

            // Check if cart quantity exceeds available stock
            if ($item->quantity > $item->product->quantity) {
                $insufficientStock = true;
                $canPlaceOrder = false;
            }

            $cartItems[] = [
                'id' => $item->id,
                'product_name' => $item->product->product_name,
                'price' => $item->product->price,
                'image' => $item->product->image,
                'stock_qty' => $item->product->quantity,
                'stock_status' => $item->product->status,
                'quantity' => $item->quantity,
                'subtotal' => $subtotal,
                'is_available' => $isAvailable,
                'insufficient_stock' => $insufficientStock,
            ];
        }

        return view('customer.cart', compact('cartItems', 'grandTotal', 'canPlaceOrder'));
    }

    public function carTransaction(Request $request, $id)
    {
        $userId = session('user_id');
        $role = session('role');

        if (!$userId || $role !== 'Customer') {
            return redirect()->route('login');
        }

        $carId = $id;
        $auctionId = $request->query('auction_id');
        $isAuction = !empty($auctionId);

        // Fetch the car with its category relation
        $carQuery = Car::with('category')->where('id', $carId);

        if (!$isAuction) {
            $carQuery->where('quantity', '>', 0)->where('status', 'In Stock');
        }

        $car = $carQuery->first();

        if (!$car) {
            $redirectRoute = $isAuction ? route('customer.auction.lobby') : route('customer.showroom');
            return redirect()->to($redirectRoute)->with('error', 'Vehicle not available');
        }

        // Determine the price and purchase target route/action
        $finalPrice = $car->price;
        $purchaseAction = route('customer.car.purchase.submit'); // Adjust to your Laravel purchase submit route name
        $headingText = "Checkout Summary";

        if ($isAuction) {
            $auctionData = Auction::where('id', $auctionId)->where('status', 'Active')->first();

            if ($auctionData) {
                $finalPrice = $auctionData->reserve_price;
                $purchaseAction = route('customer.auction.purchase.submit'); // Adjust to your Laravel auction purchase route name
                $headingText = "Auction Settlement";
            } else {
                return redirect()->route('customer.auction.view', $auctionId)->with('error', 'Auction is no longer active');
            }
        }

        return view('customer.car-transaction', compact(
            'car', 'finalPrice', 'isAuction', 'auctionId', 'purchaseAction', 'headingText'
        ));
    }

    public function carView($id)
    {
        $userId = session('user_id');
        $role = session('role');

        if (!$userId || $role !== 'Customer') {
            return redirect()->route('login');
        }

        $carId = $id;

        // 1. Fetch car details with category
        $car = Car::with('category')->where('id', $carId)->first();

        if (!$car) {
            return redirect()->route('customer.showroom')->with('error', 'Car not found');
        }

        // 2. Check purchase status
        $hasPurchased = Order::where('customer_id', $userId)->where('car_id', $carId)->exists();

        // 3. Check for existing review
        $myReview = Review::where('user_id', $userId)->where('car_id', $carId)->first();

        // 4. Check wishlist status
        $isWishlisted = Wishlist::where('user_id', $userId)->where('car_id', $carId)->exists();

        // 5. Fetch all reviews with customer details
        $reviews = Review::with('customer')
            ->where('car_id', $carId)
            ->orderBy('date_posted', 'DESC')
            ->get();

        return view('customer.car-view', compact(
            'car', 'carId', 'userId', 'hasPurchased', 'myReview', 'isWishlisted', 'reviews'
        ));
    }

    public function checkoutReview()
    {
        $userId = session('user_id');
        $role = session('role');

        if (!$userId || $role !== 'Customer') {
            return redirect()->route('login');
        }

        // Fetch user details for the "Delivery Info" section using Eloquent
        $user = Customer::where('user_id', $userId)->first();

        // Fetch cart items for the "Order Summary" section with products relation
        $items = Cart::with('product')
            ->where('customer_id', $userId)
            ->get();

        return view('customer.checkout-review', compact('user', 'items'));
    }

    public function claimWarrantyForm(Request $request)
    {
        $userId = session('user_id');
        $role = session('role');

        if (!$userId || $role !== 'Customer') {
            return redirect()->route('login');
        }

        if (!$request->has('order_id') || (!$request->has('product_id') && !$request->has('car_id'))) {
            return redirect()->route('customer.history')->with('error', 'Invalid warranty request');
        }

        $orderId = $request->query('order_id');
        $itemName = "";
        $hasRegular = 0;
        $hasReplacement = 0;

        // Check if we are claiming for a CAR
        if ($request->has('car_id')) {
            $carId = $request->query('car_id');
            $car = Car::find($carId);

            if ($car) {
                $itemName = $car->brand . " " . $car->model;
                $hasRegular = $car->regular_warranty;
                $hasReplacement = $car->replacement_warranty;
            }
        } 
        // Otherwise, check if we are claiming for a PRODUCT
        elseif ($request->has('product_id')) {
            $productId = $request->query('product_id');
            $product = Product::find($productId);

            if ($product) {
                $itemName = $product->product_name;
                $hasRegular = $product->regular_warranty;
                $hasReplacement = $product->replacement_warranty;
            }
        }

        if (empty($itemName)) {
            return redirect()->route('customer.history')->with('error', 'Item not found');
        }

        return view('customer.claim-warranty', compact(
            'orderId', 'itemName', 'hasRegular', 'hasReplacement'
        ));
    }

    public function contactSupport()
    {
        $userId = session('user_id');
        $role = session('role');

        if (!$userId || $role !== 'Customer') {
            return redirect()->route('login');
        }

        // Fetch user info using Eloquent
        $userInfo = Customer::where('user_id', $userId)->first();
        
        $fullName = $userInfo ? $userInfo->fname . " " . $userInfo->lname : '';
        $email = $userInfo ? $userInfo->email_address : '';

        return view('customer.contact', compact('fullName', 'email'));
    }

    public function store(Request $request)
    {
        $userId = session('user_id');
        $role = session('role');

        if (!$userId || $role !== 'Customer') {
            return redirect()->route('login');
        }

        $search = $request->query('search', '');
        $catFilter = $request->query('category_id', '');
        $statusFilter = $request->query('status', 'All');
        $tagFilter = $request->query('tag_filter', '');

        // 1. Fetch User's current wishlist item IDs
        $wishlistIds = Wishlist::where('user_id', $userId)
            ->whereNotNull('product_id')
            ->pluck('product_id')
            ->toArray();

        // 2. Fetch ONLY 'Part' Categories
        $categories = Category::where('type', 'Part')
            ->orderBy('category_name', 'ASC')
            ->get();

        // 3. Fetch all available tags
        $allAvailableTags = Tag::orderBy('tag_name', 'ASC')->get();

        // 4. Build Product Query
        $query = Product::with('category')
            ->whereHas('category', function ($q) {
                $q->where('type', 'Part');
            });

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'LIKE', "%{$search}%")
                ->orWhere('description', 'LIKE', "%{$search}%")
                ->orWhere('tags', 'LIKE', "%{$search}%");
            });
        }

        if (!empty($catFilter)) {
            $query->where('category_id', $catFilter);
        }

        if ($statusFilter !== 'All') {
            $query->where('status', $statusFilter);
        }

        if (!empty($tagFilter)) {
            $query->where('tags', 'LIKE', "%{$tagFilter}%");
        }

        $products = $query->orderBy('id', 'DESC')->get();

        // Fetch current cart quantities for the user to compute stock limits
        $cartQuantities = Cart::where('customer_id', $userId)
            ->select('product_id', \DB::raw('SUM(quantity) as in_cart'))
            ->groupBy('product_id')
            ->pluck('in_cart', 'product_id')
            ->toArray();

        return view('customer.store', compact(
            'products', 'categories', 'allAvailableTags', 'wishlistIds', 
            'cartQuantities', 'search', 'catFilter', 'statusFilter', 'tagFilter', 'userId'
        ));
    }

    public function history()
    {
        $userId = session('user_id');
        $role = session('role');

        if (!$userId || $role !== 'Customer') {
            return redirect()->route('login');
        }

        // Fetch orders with product, car, and warranty claim details using Eloquent joins/relations
        $orders = Order::with(['product', 'car', 'warrantyClaim'])
            ->where('customer_id', $userId)
            ->orderBy('id', 'DESC')
            ->get();

        return view('customer.history', compact('orders'));
    }

    public function dashboard()
    {
        $userId = session('user_id');
        $role = session('role');

        if (!$userId || $role !== 'Customer') {
            return redirect()->route('login');
        }

        // Fetch Customer Profile Info with User relationship (username)
        $customer = Customer::with('user')->where('user_id', $userId)->first();

        // Fetch 5 most recent orders including Products and Cars relations
        $recentOrders = Order::with(['product', 'car'])
            ->where('customer_id', $userId)
            ->orderBy('id', 'DESC')
            ->limit(5)
            ->get();

        // Fetch 5 most recent repairs
        $recentRepairs = CarRepair::where('customer_id', $userId)
            ->orderBy('repair_id', 'DESC')
            ->limit(5)
            ->get();

        // Calculate cart count from Database
        $cartCount = Cart::where('customer_id', $userId)->sum('quantity');

        // Calculate wishlist count (products or cars present)
        $wishCount = Wishlist::where('user_id', $userId)
            ->where(function($query) {
                $query->whereHas('product')
                    ->orWhereHas('car');
            })
            ->count();

        return view('customer.index', compact(
            'customer', 'recentOrders', 'recentRepairs', 'cartCount', 'wishCount'
        ));
    }

    public function myReviews()
    {
        $userId = session('user_id');
        $role = session('role');

        if (!$userId || $role !== 'Customer') {
            return redirect()->route('login');
        }

        $reviews = Review::with(['product', 'car'])
            ->where('user_id', $userId)
            ->orderBy('date_posted', 'DESC')
            ->get();

        return view('customer.my-reviews', compact('reviews'));
    }

    public function orderItem($id)
    {
        $userId = session('user_id');
        $role = session('role');

        if (!$userId || $role !== 'Customer') {
            return redirect()->route('login');
        }

        $product = Product::find($id);

        if (!$product) {
            return redirect()->route('customer.store')->with('error', 'Product not found.');
        }

        return view('customer.order-item', compact('product'));
    }

    public function viewProduct($id)
    {
        $userId = session('user_id');
        $role = session('role');

        if ($role === 'Manager') {
            return redirect()->route('manager.inventory');
        }

        // 1. Fetch Product with Category
        $product = Product::with('category')->find($id);

        if (!$product) {
            return view('customer.product-unavailable');
        }

        // 2. Fetch Reviews with Customer relation
        $reviews = Review::with('customer')
            ->where('product_id', $id)
            ->orderBy('id', 'DESC')
            ->get();

        $canReview = false;
        $myExistingReview = null;
        $isWishlisted = false;

        if ($userId && $role === 'Customer') {
            // Check if user bought this product
            $orderFound = Order::where('customer_id', $userId)
                ->where('product_id', $id)
                ->first();

            if ($orderFound) {
                $canReview = true;
                $myExistingReview = Review::where('product_id', $id)
                    ->where('user_id', $userId)
                    ->first();
            }

            // Check Wishlist
            $isWishlisted = Wishlist::where('user_id', $userId)
                ->where('product_id', $id)
                ->exists();
        }

        return view('customer.view-product', compact(
            'product', 'reviews', 'canReview', 'myExistingReview', 'isWishlisted'
        ));
    }

    public function checkProductStock(Request $request, $id)
    {
        $userId = session('user_id');
        $qtyToAdd = $request->input('qty', 1);

        $product = Product::find($id);
        if (!$product) {
            return redirect()->route('customer.store');
        }

        $existingCartItem = Cart::where('customer_id', $userId)
            ->where('product_id', $id)
            ->first();

        $currentInCart = $existingCartItem ? $existingCartItem->quantity : 0;

        if (($currentInCart + $qtyToAdd) > $product->quantity) {
            return redirect()->route('customer.product.view', $id)
                ->with('error', "Cannot add more. You already have {$currentInCart} in cart and total stock is {$product->quantity}.");
        }

        // Proceed to add to cart action
        return redirect()->route('customer.cart.add', ['id' => $id, 'qty' => $qtyToAdd]);
    }

    

    public function profile()
    {
        $userId = session('user_id');
        $role = session('role');

        if (!$userId || $role !== 'Customer') {
            return redirect()->route('login');
        }

        // Fetch Customer with User relation using Eloquent
        $customer = Customer::with('user')->where('user_id', $userId)->first();

        if (!$customer) {
            return redirect()->route('customer.dashboard')->with('error', 'Profile not found.');
        }

        return view('customer.profile', compact('customer'));
    }


    public function createRepairForm()
    {
        $userId = session('user_id');
        $role = session('role');

        if (!$userId || $role !== 'Customer') {
            return redirect()->route('login');
        }

        // Fetch the current year constraint from the setting table
        $setting = DB::table('setting')->select('current_year')->first();
        $max_year = $setting ? $setting->current_year : date("Y");

        return view('customer.repair-add', compact('max_year'));
    }

    

    public function repairDashboard()
    {
        $userId = session('user_id');
        $role = session('role');

        if (!$userId || $role !== 'Customer') {
            return redirect()->route('login');
        }

        // Fetch repairs ordered by ID descending
        $repairs = CarRepair::where('customer_id', $userId)
            ->orderBy('repair_id', 'DESC')
            ->get();

        $activeCount = 0;
        $completedCount = 0;
        $rejectedCount = 0;
        $totalInvestment = 0;
        
        // Complete for loop matching your requested style
        for ($i = 0; $i < count($repairs); $i = $i + 1) {
            $r = $repairs[$i];
            $status = $r->status;

            // If rejected, the cost investment for the customer is 0
            if ($status != 'Rejected') {
                $totalInvestment = $totalInvestment + ($r->labor_fee + $r->parts_total);
            }

            // Count rejected separately
            if ($status == 'Rejected') {
                $rejectedCount = $rejectedCount + 1;
            }

            // Count completed
            if ($status == 'Completed') {
                $completedCount = $completedCount + 1;
            }
            
            // Active Jobs are those not Completed and not Rejected
            if ($status != 'Completed' && $status != 'Rejected') {
                $activeCount = $activeCount + 1;
            }
        }

        return view('customer.repair-dashboard', compact(
            'repairs', 'activeCount', 'completedCount', 'rejectedCount', 'totalInvestment'
        ));
    }

    public function showRepairTransaction($id)
    {
        $userId = session('user_id');

        if (!$userId) {
            return redirect()->route('login');
        }

        $repair = CarRepair::where('repair_id', $id)
            ->where('customer_id', $userId)
            ->first();

        if (!$repair) {
            return redirect()->route('customer.repair.dashboard');
        }

        $labor = $repair->labor_fee;
        $parts = $repair->parts_total;
        $totalBill = $labor + $parts;

        return view('customer.repair-transaction', compact('repair', 'labor', 'parts', 'totalBill'));
    }

    public function repairView($id)
    {
        $userId = session('user_id');
        $role = session('role');

        if (!$userId || $role !== 'Customer') {
            return redirect()->route('login');
        }

        // 1. Fetch repair details and verify ownership
        $repair = CarRepair::where('repair_id', $id)
            ->where('customer_id', $userId)
            ->first();

        if (!$repair) {
            return redirect()->route('customer.repair.dashboard');
        }

        // 2. Fetch selected parts joined with product details
        $partsList = DB::table('products')
            ->join('repair_parts_selected', 'products.id', '=', 'repair_parts_selected.product_id')
            ->where('repair_parts_selected.repair_id', $id)
            ->select('products.*', 'repair_parts_selected.price_at_time')
            ->get();

        // 3. Financial Calculation
        if ($repair->status === 'Rejected') {
            $totalBill = 0;
            $displayLabor = 0;
            $displayParts = 0;
        } else {
            $totalBill = $repair->labor_fee + $repair->parts_total;
            $displayLabor = $repair->labor_fee;
            $displayParts = $repair->parts_total;
        }

        return view('customer.repair-view', compact(
            'repair', 'partsList', 'totalBill', 'displayLabor', 'displayParts'
        ));
    }

    public function savedBuilds()
    {
        $userId = session('user_id');
        $role = session('role');

        if (!$userId || $role !== 'Customer') {
            return redirect()->route('login');
        }

        // 1. Fetch builds belonging to the user
        $builds = UserBuild::where('user_id', $userId)
            ->orderBy('id', 'DESC')
            ->get();

        $totalBuildCount = count($builds);

        // 2. Fetch IDs of mandatory categories to check build completeness
        $mandatoryCategories = Category::where('is_mandatory', 1)
            ->pluck('id')
            ->toArray();
        $mandatoryCount = count($mandatoryCategories);

        // Process each build's statistics, completeness, and compatibility
        $processedBuilds = [];

        for ($i = 0; $i < $totalBuildCount; $i = $i + 1) {
            $build = $builds[$i];
            $currentBuildId = $build->id;

            // Calculate Total Build Cost and HP
            $statsResult = DB::table('build_items')
                ->join('products', 'build_items.product_id', '=', 'products.id')
                ->where('build_items.build_id', $currentBuildId)
                ->selectRaw('SUM(products.price) as total_sum, SUM(products.hp) as total_hp')
                ->first();

            $totalCost = $statsResult->total_sum ?? 0;
            $totalHp = $statsResult->total_hp ?? 0;

            // Fetch tags and categories for validation
            $buildItemDetails = DB::table('build_items')
                ->join('products', 'build_items.product_id', '=', 'products.id')
                ->where('build_items.build_id', $currentBuildId)
                ->select('products.tags', 'products.category_id')
                ->get()
                ->toArray();

            $isCompatible = true;
            $isComplete = true;
            $commonTags = null;
            $totalItems = count($buildItemDetails);

            // Completeness Check: Ensure all mandatory categories are present
            for ($m = 0; $m < $mandatoryCount; $m = $m + 1) {
                $categoryFound = false;
                for ($it = 0; $it < $totalItems; $it = $it + 1) {
                    if ($buildItemDetails[$it]->category_id == $mandatoryCategories[$m]) {
                        $categoryFound = true;
                    }
                }
                if ($categoryFound == false) {
                    $isComplete = false;
                }
            }

            // Compatibility Check
            for ($tIdx = 0; $tIdx < $totalItems; $tIdx = $tIdx + 1) {
                $rawTags = $buildItemDetails[$tIdx]->tags;
                $currentProductTags = [];
                
                if (empty($rawTags)) {
                    $currentProductTags[] = 'universal';
                } else {
                    $tempTags = explode(',', $rawTags);
                    for ($k = 0; $k < count($tempTags); $k = $k + 1) {
                        $cleaned = strtolower(trim($tempTags[$k]));
                        if ($cleaned != "") { $currentProductTags[] = $cleaned; }
                    }
                }

                if ($commonTags === null) {
                    $commonTags = $currentProductTags;
                } else {
                    $newCommon = [];
                    $hasUniversal = false;
                    for ($u = 0; $u < count($commonTags); $u = $u + 1) {
                        if ($commonTags[$u] == 'universal') { $hasUniversal = true; }
                    }

                    $itemIsUni = false;
                    for ($u = 0; $u < count($currentProductTags); $u = $u + 1) {
                        if ($currentProductTags[$u] == 'universal') { $itemIsUni = true; }
                    }

                    if ($itemIsUni) {
                        $newCommon = $commonTags;
                    } else if ($hasUniversal) {
                        $newCommon = $currentProductTags;
                    } else {
                        for ($j = 0; $j < count($commonTags); $j = $j + 1) {
                            for ($k = 0; $k < count($currentProductTags); $k = $k + 1) {
                                if ($commonTags[$j] == $currentProductTags[$k]) {
                                    $newCommon[] = $commonTags[$j];
                                }
                            }
                        }
                    }
                    $commonTags = $newCommon;
                }
            }

            if ($totalItems > 0) {
                if (is_array($commonTags)) {
                    if (count($commonTags) == 0) {
                        $isCompatible = false;
                    }
                } else {
                    $isCompatible = false;
                }
            }

            $displayName = !empty($build->build_name) ? $build->build_name : "Unnamed Build #" . $currentBuildId;

            $processedBuilds[] = [
                'id' => $currentBuildId,
                'name' => $displayName,
                'created_at' => $build->created_at,
                'total_cost' => $totalCost,
                'total_hp' => $totalHp,
                'is_complete' => $isComplete,
                'is_compatible' => $isCompatible,
            ];
        }

        return view('customer.saved-builds', compact('totalBuildCount', 'processedBuilds'));
    }

    public function selectOrder(Request $request)
    {
        $userId = session('user_id');
        $role = session('role');

        if (!$userId || $role !== 'Customer') {
            return redirect()->route('login');
        }

        $isCar = $request->has('car_id');
        $itemId = $isCar ? $request->input('car_id') : ($request->input('product_id') ?? null);

        if ($itemId == null) {
            return redirect()->route('customer.store');
        }

        // 1. Get Item Details for the header
        if ($isCar) {
            $item = Car::where('id', $itemId)->select('brand as name', 'model as subname')->first();
            $backLink = route('customer.car.view', $itemId);
        } else {
            $item = Product::where('id', $itemId)->select('product_name as name')->first();
            $item->subname = '';
            $backLink = route('customer.product.view', $itemId);
        }

        if (!$item) {
            return redirect()->route('customer.store');
        }

        // 2. Fetch orders for this item joined with warranty_claims
        $query = Order::leftJoin('warranty_claims', 'orders.id', '=', 'warranty_claims.order_id')
            ->where('orders.customer_id', $userId)
            ->select('orders.id', 'orders.date_ordered', 'warranty_claims.id as claim_id')
            ->orderBy('orders.date_ordered', 'DESC');

        if ($isCar) {
            $query->where('orders.car_id', $itemId);
        } else {
            $query->where('orders.product_id', $itemId);
        }

        $userOrders = $query->get();

        if ($userOrders->isEmpty()) {
            return redirect()->route('customer.store')->with('error', 'No purchase record found');
        }

        return view('customer.select-order', compact('item', 'userOrders', 'isCar', 'itemId', 'backLink'));
    }


    public function settings()
    {
        $userId = session('user_id');
        $role = session('role');

        if (!$userId || $role !== 'Customer') {
            return redirect()->route('login');
        }

        $customer = Customer::where('user_id', $userId)->first();

        if (!$customer) {
            return redirect()->route('customer.dashboard');
        }

        return view('customer.settings', compact('customer'));
    }

    public function showroom(Request $request)
    {
        $userId = session('user_id');
        $role = session('role');

        if (!$userId || $role !== 'Customer') {
            return redirect()->route('login');
        }

        // Fetch Current Year from setting table
        $setting = Setting::first();
        $currentSystemYear = $setting ? $setting->current_year : date('Y');

        // Fetch User's current wishlist for CARS
        $wishlistIds = Wishlist::where('user_id', $userId)
            ->whereNotNull('car_id')
            ->pluck('car_id')
            ->toArray();

        // Fetch Categories for Cars
        $categories = Category::where('type', 'Car')
            ->orderBy('category_name', 'ASC')
            ->get();

        $transmissions = ["Automatic", "Manual", "CVT", "DCT", "AMT"];

        // Advanced Filtering Logic using Eloquent Query Builder
        $query = Car::with('category');

        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('model', 'like', "%{$searchTerm}%")
                ->orWhere('brand', 'like', "%{$searchTerm}%");
            });
        }

        if ($request->filled('cat_id')) {
            $query->where('category_id', $request->input('cat_id'));
        }

        if ($request->filled('min_price')) {
            $minP = $request->input('min_price');
            if ($minP >= 0 && $minP <= 1000000000) {
                $query->where('price', '>=', $minP);
            }
        }

        if ($request->filled('max_price')) {
            $maxP = $request->input('max_price');
            if ($maxP >= 0 && $maxP <= 1000000000) {
                $query->where('price', '<=', $maxP);
            }
        }

        if ($request->filled('max_mileage')) {
            $maxM = $request->input('max_mileage');
            if ($maxM >= 0 && $maxM <= 1000000000) {
                $query->where('mileage', '<=', $maxM);
            }
        }

        if ($request->filled('min_year')) {
            $minY = $request->input('min_year');
            if ($minY >= 1900 && $minY <= $currentSystemYear) {
                $query->where('year', '>=', $minY);
            }
        }

        if ($request->filled('max_year')) {
            $maxY = $request->input('max_year');
            if ($maxY >= 1900 && $maxY <= $currentSystemYear) {
                $query->where('year', '<=', $maxY);
            }
        }

        if ($request->filled('trans')) {
            $query->where('transmission', $request->input('trans'));
        }

        if ($request->filled('status_filter')) {
            $sFilter = $request->input('status_filter');
            if ($sFilter == 'Upcoming') {
                $query->where('status', 'Upcoming');
            } elseif ($sFilter == 'Out of Stock') {
                $query->where(function ($q) {
                    $q->where('quantity', '<=', 0)
                    ->orWhere('status', 'Sold Out');
                });
            } elseif ($sFilter == 'In Stock') {
                $query->where('quantity', '>', 0)
                    ->where('status', 'In Stock');
            }
        }

        // Fetch Cars ordered by latest ID
        $cars = $query->orderBy('id', 'DESC')->get();

        // Fetch list of car IDs this specific user has purchased
        $purchasedCars = Order::where('customer_id', $userId)
            ->whereNotNull('car_id')
            ->pluck('car_id')
            ->toArray();

        return view('customer.showroom', compact(
            'currentSystemYear',
            'wishlistIds',
            'categories',
            'transmissions',
            'cars',
            'purchasedCars'
        ));
    }


    public function viewBuildDetails($id)
    {
        $userId = session('user_id');
        $role = session('role');

        if (!$userId || $role !== 'Customer') {
            return redirect()->route('login');
        }

        // 1. Fetch Build Header Info
        $buildInfo = UserBuild::where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$buildInfo) {
            return redirect()->route('customer.saved.builds')->with('error', 'Build not found');
        }

        // 2. Fetch all parts associated with this build
        $items = DB::table('build_items')
            ->join('products', 'build_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('build_items.build_id', $id)
            ->select(
                'products.id as product_id',
                'products.product_name',
                'products.price',
                'products.image',
                'products.tags',
                'products.quantity',
                'products.status',
                'products.hp',
                'categories.category_name',
                'categories.id as category_id'
            )
            ->get();

        // 3. Fetch User's Wishlist Product IDs
        $wishlistData = Wishlist::where('user_id', $userId)
            ->whereNotNull('product_id')
            ->pluck('product_id')
            ->toArray();

        // 4. Fetch Mandatory Categories IDs
        $mandatoryCategories = Category::where('is_mandatory', 1)
            ->pluck('id')
            ->toArray();

        // 5. LOGIC: Check Real-time Compatibility, Stock, and Completeness
        $isCompatible = true;
        $allInStock = true;
        $isComplete = true;
        $commonTags = null;
        $totalItems = count($items);
        $wishlistedPartsCount = 0;

        // Check for completeness
        for ($m = 0; $m < count($mandatoryCategories); $m = $m + 1) {
            $foundMandatory = false;
            for ($i = 0; $i < $totalItems; $i = $i + 1) {
                if ($items[$i]->category_id == $mandatoryCategories[$m]) {
                    $foundMandatory = true;
                }
            }
            if ($foundMandatory == false) {
                $isComplete = false;
            }
        }

        for ($i = 0; $i < $totalItems; $i = $i + 1) {
            $item = $items[$i];

            for ($w = 0; $w < count($wishlistData); $w = $w + 1) {
                if ($wishlistData[$w] == $item->product_id) {
                    $wishlistedPartsCount = $wishlistedPartsCount + 1;
                }
            }

            if ($item->quantity <= 0 || $item->status != 'In Stock') {
                $allInStock = false;
            }

            $rawTags = $item->tags;
            $currentProductTags = [];

            if (empty($rawTags)) {
                $currentProductTags[] = 'universal';
            } else {
                $tempTags = explode(',', $rawTags);
                for ($t = 0; $t < count($tempTags); $t = $t + 1) {
                    $cleaned = strtolower(trim($tempTags[$t]));
                    if ($cleaned != "") {
                        $currentProductTags[] = $cleaned;
                    }
                }
            }

            if ($commonTags === null) {
                $commonTags = $currentProductTags;
            } else {
                $newCommon = [];
                $hasUniversal = false;

                for ($u = 0; $u < count($commonTags); $u = $u + 1) {
                    if ($commonTags[$u] == 'universal') { $hasUniversal = true; }
                }

                $itemIsUni = false;
                for ($u = 0; $u < count($currentProductTags); $u = $u + 1) {
                    if ($currentProductTags[$u] == 'universal') { $itemIsUni = true; }
                }

                if ($itemIsUni) {
                    $newCommon = $commonTags;
                } else if ($hasUniversal) {
                    $newCommon = $currentProductTags;
                } else {
                    for ($j = 0; $j < count($commonTags); $j = $j + 1) {
                        for ($k = 0; $k < count($currentProductTags); $k = $k + 1) {
                            if ($commonTags[$j] == $currentProductTags[$k]) {
                                $newCommon[] = $commonTags[$j];
                            }
                        }
                    }
                }
                $commonTags = $newCommon;
            }
        }

        if ($totalItems > 0) {
            if (is_array($commonTags)) {
                if (count($commonTags) == 0) {
                    $isCompatible = false;
                }
            } else {
                $isCompatible = false;
            }
        }

        $allPartsWishlisted = ($totalItems > 0 && $wishlistedPartsCount == $totalItems);

        return view('customer.view-build-details', compact(
            'buildInfo',
            'items',
            'wishlistData',
            'isCompatible',
            'allInStock',
            'isComplete',
            'allPartsWishlisted',
            'totalItems',
            'id'
        ));
    }


        
    public function wishlist()
    {
        $userId = session('user_id');
        $role = session('role');

        if (!$userId || $role !== 'Customer') {
            return redirect()->route('login');
        }

        // Join wishlist with both products and cars using DB facade for complex left joins
        $wishlistItems = DB::table('wishlist')
            ->leftJoin('products', 'wishlist.product_id', '=', 'products.id')
            ->leftJoin('cars', 'wishlist.car_id', '=', 'cars.id')
            ->where('wishlist.user_id', $userId)
            ->select(
                'wishlist.id as wish_id',
                'wishlist.product_id',
                'wishlist.car_id',
                'products.product_name',
                'products.image as p_img',
                'products.price as p_price',
                'products.id as p_id',
                'cars.model as car_model',
                'cars.brand as car_brand',
                'cars.image as c_img',
                'cars.price as c_price',
                'cars.id as c_id'
            )
            ->orderBy('wishlist.id', 'DESC')
            ->get();

        return view('customer.wishlist', compact('wishlistItems'));
    }

    public function orders()
    {
        $userId = session('user_id');
        $role = session('role');

        if (!$userId || $role !== 'Customer') {
            return redirect()->route('login');
        }

        $orders = Order::with(['product', 'car'])
            ->where('customer_id', $userId)
            ->orderBy('id', 'DESC')
            ->get();

        return view('customer.orders', compact('orders'));
    }

    public function reviews()
    {
        $userId = session('user_id');
        $role = session('role');

        if (!$userId || $role !== 'Customer') {
            return redirect()->route('login');
        }

        // Fetch reviews written by or relevant to this customer
        $reviews = Review::where('user_id', $userId)->get(); // Adjust query as needed for your schema

        return view('customer.my-reviews', compact('reviews'));
    }

    public function warrantyManage()
    {
        $userId = session('user_id');
        $role = session('role');

        if (!$userId || $role !== 'Customer') {
            return redirect()->route('login');
        }

        // Fetch orders / purchased items for the order history page
        $orders = Order::with(['product', 'car'])
            ->where('customer_id', $userId)
            ->orderBy('id', 'DESC')
            ->get();

        // Fetch existing warranty claims if needed
        $warrantyClaims = WarrantyClaim::where('customer_id', $userId)
            ->orderBy('id', 'DESC')
            ->get();

        // Render the order history / warranty management view
        return view('customer.history', compact('orders', 'warrantyClaims'));
    }

    public function Builder()
    {
        $userId = session('user_id');
        $role = session('role');

        if (!$userId || $role !== 'Customer') {
            return redirect()->route('login');
        }

        // Add any necessary data retrieval for your car/part builder here
        return view('customer.builder');
    }
}