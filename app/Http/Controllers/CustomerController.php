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
        $redirect = $request->header('referer', route('car.builder'));
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

                        for (int $i = 0; $i < count($parts); $i = $i + 1) {
                            BuildItem::create([
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
                
                for (int $i = 0; $i < count($parts); $i = $i + 1) {
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
                    for (int $i = 0; $i < $totalParts; $i = $i + 1) {
                        $currPId = $parts[$i]->product_id;
                        $exists = Wishlist::where('user_id', $userId)
                            ->where('product_id', $currPId)
                            ->exists();
                        
                        if ($exists) {
                            $wishCount = $wishCount + 1;
                        }
                    }

                    if ($wishCount == $totalParts) {
                        for (int $i = 0; $i < $totalParts; $i = $i + 1) {
                            $currPId = $parts[$i]->product_id;
                            Wishlist::where('user_id', $userId)
                                ->where('product_id', $currPId)
                                ->delete();
                        }
                        return redirect($finalRedirect . $separator . "success=Build removed from wishlist");
                    } else {
                        for (int $i = 0; $i < $totalParts; $i = $i + 1) {
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

                for (int $i = 0; $i < count($items); $i = $i + 1) {
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
                for (int $i = 0; $i < count($cartItems); $i = $i + 1) {
                    $item = $cartItems[$i];
                    
                    if ($item->quantity > $item->stock) {
                        $name = $item->product_name;
                        return redirect()->route('cart.index')->with('error', "Not enough stock for $name");
                    }
                }

                // 3. Loop through to process the purchase
                $dateOrdered = Carbon::now()->toDateString();

                for (int $i = 0; $i < count($cartItems); $i = $i + 1) {
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
            for (int $i = 0; $i < count($allowedExs); $i = $i + 1) {
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
                return redirect()->route('repair.dashboard')->with('success', 'Request submitted successfully');
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
            return redirect()->route('repair.dashboard');
        }

        $currentStatus = $repair->status;

        // 2. Only allow deletion if status is Pending
        if ($currentStatus === 'Pending') {
            $deleted = $repair->delete();

            if ($deleted) {
                return redirect()->route('repair.dashboard')->with('success', 'Request deleted successfully!');
            } else {
                return redirect()->route('repair.dashboard')->with('error', 'Unknown error occurred');
            }
        } else {
            return redirect()->route('repair.dashboard')->with('error', 'In-progress repairs cannot be deleted');
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
                    return redirect()->route('repair.dashboard');
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

                    for (int $i = 0; $i < $partsCount; $i = $i + 1) {
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
            return redirect()->route('repair.dashboard');
        }

        // 2. Update the status to 'Repairing'
        $updated = $repair->update([
            'status' => 'Repairing',
        ]);

        if ($updated) {
            $sm = "Payment successful! Your car status is now 'Repairing'.";
            return redirect()->route('repair.view', ['id' => $id])->with('success', $sm);
        } else {
            $em = "An error occurred during status update.";
            return redirect()->route('repair.view', ['id' => $id])->with('error', $em);
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

}