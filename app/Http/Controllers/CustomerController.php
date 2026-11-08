<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index()
    {
        // Session and role check mirroring legacy authentication
        if (session()->has('user_id') && session('role') == 'Customer') {
            
            $user_id = session('user_id');

            // Fetch Customer Profile Info
            $sql = "SELECT users.username, customer.* FROM customer 
                    INNER JOIN users ON customer.user_id = users.uid 
                    WHERE customer.user_id = ?";
            
            $customer_res = DB::select($sql, [$user_id]);
            $customer = (array) $customer_res[0];

            // --- TEMPORARILY COMMENTED OUT UNTIL MODULES ARE PORTED ---
            // Fetch 5 most recent orders including Cars
            /*
            $sql_recent = "SELECT o.*, p.product_name, p.image AS p_image, c.brand, c.model, c.image AS c_image
                            FROM orders o 
                            LEFT JOIN products p ON o.product_id = p.id
                            LEFT JOIN cars c ON o.car_id = c.id
                            WHERE o.customer_id = ? 
                            ORDER BY o.id DESC LIMIT 5";
            $recent_orders = DB::select($sql_recent, [$user_id]);
            */
            $recent_orders = [];

            // Fetch 5 most recent repairs
            /*
            $sql_repairs = "SELECT * FROM car_repairs 
                            WHERE customer_id = ? 
                            ORDER BY repair_id DESC LIMIT 5";
            $recent_repairs = DB::select($sql_repairs, [$user_id]);
            */
            $recent_repairs = [];

            // Logic to calculate cart count from Database
            /*
            $sql_cart_count = "SELECT SUM(quantity) as total FROM cart WHERE customer_id = ?";
            $cart_res = DB::select($sql_cart_count, [$user_id]);
            $cart_count = (!empty($cart_res) && $cart_res[0]->total > 0) ? $cart_res[0]->total : 0;
            */
            $cart_count = 0;

            // Logic to calculate wishlist count
            /*
            $sql_wish_count = "SELECT w.id FROM wishlist w 
                               LEFT JOIN products p ON w.product_id = p.id 
                               LEFT JOIN cars c ON w.car_id = c.id
                               WHERE w.user_id = ? AND (p.id IS NOT NULL OR c.id IS NOT NULL)";
            $wish_res = DB::select($sql_wish_count, [$user_id]);
            $wish_count = count($wish_res);
            */
            $wish_count = 0;
            // ----------------------------------------------------------

            return view('Customer.index', compact(
                'customer', 
                'recent_orders', 
                'recent_repairs', 
                'cart_count', 
                'wish_count'
            ));

        } else {
            return redirect()->route('login');
        }
    }
    public function profile()
    {
        if (session()->has('user_id') && session('role') == 'Customer') {
            $user_id = session('user_id');

            $sql = "SELECT users.username, customer.* FROM customer 
                    INNER JOIN users ON customer.user_id = users.uid 
                    WHERE customer.user_id = ?";
            
            $customer_res = DB::select($sql, [$user_id]);
            $customer = (array) $customer_res[0];

            return view('Customer.profile', compact('customer'));
        } else {
            return redirect()->route('login');
        }
    }

    public function editProfile()
    {
        if (session()->has('user_id') && session('role') == 'Customer') {
            $user_id = session('user_id');

            $sql = "SELECT * FROM customer WHERE user_id = ?";
            $customer_res = DB::select($sql, [$user_id]);
            $customer = (array) $customer_res[0];

            return view('Customer.profile-edit', compact('customer'));
        } else {
            return redirect()->route('login');
        }
    }
    

    public function updateProfile(Request $request)
    {
        if (session()->has('user_id') && session('role') == 'Customer') {
            $user_id = session('user_id');

            $fname         = $request->input('fname');
            $lname         = $request->input('lname');
            $email_address = $request->input('email_address');
            $phone_number  = $request->input('phone_number');
            $address       = $request->input('address');
            $old_password  = $request->input('old_password');
            $new_password  = $request->input('new_password');
            $confirm_password = $request->input('confirm_password');

            // Verify old password against users table
            $user_sql = "SELECT password FROM users WHERE uid = ?";
            $user_res = DB::select($user_sql, [$user_id]);
            
            if (empty($user_res)) {
                return redirect()->to('/Customer/profile/edit')->with('error', 'User not found.');
            }

            $current_hashed_password = $user_res[0]->password;

            if (!password_verify($old_password, $current_hashed_password)) {
                return redirect()->to('/Customer/profile/edit')->with('error', 'Incorrect current password.');
            }

            // Update customer details
            $update_cust = "UPDATE customer SET fname = ?, lname = ?, email_address = ?, phone_number = ?, address = ? WHERE user_id = ?";
            DB::update($update_cust, [$fname, $lname, $email_address, $phone_number, $address, $user_id]);

            // Handle password change if provided
            if (!empty($new_password)) {
                if ($new_password !== $confirm_password) {
                    return redirect()->to('/Customer/profile/edit')->with('error', 'New passwords do not match.');
                }
                $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $update_pass = "UPDATE users SET password = ? WHERE uid = ?";
                DB::update($update_pass, [$new_hashed, $user_id]);
            }

            return redirect()->to('/Customer/profile')->with('success', 'Profile updated successfully.');
        } else {
            return redirect()->route('login');
        }
    }
}