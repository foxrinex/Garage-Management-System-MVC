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



}