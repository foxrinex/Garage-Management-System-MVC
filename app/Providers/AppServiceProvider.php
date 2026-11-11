<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Customer;
use App\Models\Wishlist;
use App\Models\Message;
use App\Models\Manager;

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

        // Automatically share navigation data across all manager views
        View::composer(['manager.*', 'manager.inc.*'], function ($view) {
            $userId = session('user_id');
            $role = session('role');
            $unread_count = 0;
            $nav_display_name = 'Manager';

            if ($userId && $role === 'Manager') {
                // Fetch unread inquiries count for the navbar badge
                $unread_count = Message::where('status', 'Unread')->count();
                
                // Optional: If you store manager names in a specific table, you can fetch it here:
                // $manager = Employee::where('user_id', $userId)->first();
                // if ($manager) { $nav_display_name = $manager->name; }
            }

            $view->with(compact('unread_count', 'nav_display_name'));
        });
    }
}