<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Customer;
use App\Models\Wishlist;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Automatically share navigation data across all customer views
        View::composer('customer.*', function ($view) {
            $userId = session('user_id');
            $navDisplayName = "Customer";
            $navWishCount = 0;
            $navCartCount = 0;

            if ($userId) {
                $customer = Customer::where('user_id', $userId)->first();
                if ($customer) {
                    $navDisplayName = $customer->fname;
                }

                $navWishCount = Wishlist::where('user_id', $userId)
                    ->whereHas('product')
                    ->orWhereHas('car')
                    ->count();
            }

            if (session()->has('cart')) {
                foreach (session('cart') as $item) {
                    $navCartCount += $item['quantity'] ?? 0;
                }
            }

            $view->with(compact('navDisplayName', 'navWishCount', 'navCartCount'));
        });
    }
}