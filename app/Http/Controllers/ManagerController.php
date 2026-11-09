<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManagerController extends Controller
{
    public function index()
    {
        if (session()->has('user_id') && session('role') == 'Manager') {
            $user_id = session('user_id');

            // Fetch Manager Profile Info
            $sql = "SELECT users.username, manager.* FROM manager 
                    INNER JOIN users ON manager.user_id = users.uid 
                    WHERE manager.user_id = ?";
            $manager_res = DB::select($sql, [$user_id]);
            
            if (empty($manager_res)) {
                return redirect()->route('login');
            }
            $manager = (array) $manager_res[0];

            // -----------------------------------------------------------------
            // TODO: Uncomment when the orders table and module are ported
            // -----------------------------------------------------------------
            // $sql_recent = "SELECT o.*, p.product_name, p.image AS p_image, c.brand, c.model, c.image AS c_image, cust.fname as cust_name
            //                 FROM orders o 
            //                 LEFT JOIN products p ON o.product_id = p.id
            //                 LEFT JOIN cars c ON o.car_id = c.id
            //                 LEFT JOIN customer cust ON o.customer_id = cust.user_id
            //                 ORDER BY o.id DESC LIMIT 5";
            // $recent_orders = DB::select($sql_recent);
            $recent_orders = [];

            // -----------------------------------------------------------------
            // TODO: Uncomment when the car_repairs table and module are ported
            // -----------------------------------------------------------------
            // $sql_repairs = "SELECT r.*, cust.fname as cust_name 
            //                 FROM car_repairs r
            //                 LEFT JOIN customer cust ON r.customer_id = cust.user_id
            //                 ORDER BY r.repair_id DESC LIMIT 5";
            // $recent_repairs = DB::select($sql_repairs);
            $recent_repairs = [];

            // Count inquiries for stats (message table is active)
            $sql_msg = "SELECT COUNT(*) AS unread FROM message WHERE status = 'Unread'";
            $unread_data = DB::select($sql_msg);
            
            $unread_count = 0;
            if (!empty($unread_data)) {
                $unread_count = $unread_data[0]->unread;
            }

            return view('Manager.index', compact('manager', 'recent_orders', 'recent_repairs', 'unread_count'));
        } else {
            return redirect()->route('login');
        }
    }
}