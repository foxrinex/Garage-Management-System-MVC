<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // ==========================================
    // HANDLE LOGIN LOGIC
    // ==========================================
    public function login(Request $request)
    {
        $uname = strtolower($request->input('uname'));
        $pass = $request->input('pass');
        $role_num = $request->input('role');

        if (empty($uname)) {
            $em = "Username is required";
            return redirect()->to("login?error=" . urlencode($em));
        } 
        else if (empty($pass)) {
            $em = "Password is required";
            return redirect()->to("login?error=" . urlencode($em));
        } 
        else if (empty($role_num)) {
            $em = "An error Occurred";
            return redirect()->to("login?error=" . urlencode($em));
        } 
        else {
            
            // Match numbers to the user_type in your DB
            if ($role_num == '1') {
                $sql = "SELECT * FROM users WHERE username = ? AND user_type = 'Manager'";
                $role = "Manager";
            } 
            else if ($role_num == '2') {
                $sql = "SELECT * FROM users WHERE username = ? AND user_type = 'Employee'";
                $role = "Employee";
            } 
            else if ($role_num == '3') {
                $sql = "SELECT * FROM users WHERE username = ? AND user_type = 'Customer'";
                $role = "Customer";
            } 
            else {
                $em = "Invalid Role selection";
                return redirect()->to("login?error=" . urlencode($em));
            }

            $users = DB::select($sql, [$uname]);

            if (count($users) == 1) {
                $user = (array) $users[0];
                $username = $user['username'];
                $password = $user['password'];
                $uid = $user['uid'];
                
                if ($username === $uname) {
                    if (password_verify($pass, $password)) {
                        
                        // Storing the variables for use in session
                        session(['role' => $role, 'user_id' => $uid]);

                        if ($role == 'Manager') {
                            return redirect()->to("Manager/index");
                        } 
                        else if ($role == 'Employee') {
                            return redirect()->to("Employee/index");
                        } 
                        else if ($role == 'Customer') {
                            return redirect()->to("Customer/index");
                        }
                    } 
                    else {
                        $em = "Incorrect Username or Password";
                        return redirect()->to("login?error=" . urlencode($em));
                    }
                } 
                else {
                    $em = "Incorrect Username or Password";
                    return redirect()->to("login?error=" . urlencode($em));
                }
            } 
            else {
                $em = "Incorrect Username or Password";
                return redirect()->to("login?error=" . urlencode($em));
            }
        }
    }

    // ==========================================
    // HANDLE SIGNUP LOGIC
    // ==========================================
    public function signup(Request $request)
    {
        $fname = $request->input('fname');
        $lname = $request->input('lname');
        $uname = strtolower($request->input('uname'));
        $pass  = $request->input('pass');
        $email = $request->input('email');
        $phone = $request->input('phone');
        $address = $request->input('address');
        $gender = $request->input('gender');

        $data = "fname=$fname&lname=$lname&uname=$uname&email=$email&phone=$phone&address=$address";

        if (empty($fname)) {
            return redirect()->to("signup?error=" . urlencode("First name is required&" . $data));
        } 
        else if (empty($lname)) {
            return redirect()->to("signup?error=" . urlencode("Last name is required&" . $data));
        } 
        else if (empty($uname)) {
            return redirect()->to("signup?error=" . urlencode("Username is required&" . $data));
        } 
        else if (empty($pass)) {
            return redirect()->to("signup?error=" . urlencode("Password is required&" . $data));
        } 
        else {
            $users_found = DB::select("SELECT username FROM users WHERE username = ?", [$uname]);

            $exists = false;
            
            // Explicitly written for loop as requested
            for ($i = 0; $i < count($users_found); $i++) {
                $currentUser = (array) $users_found[$i];
                if ($currentUser['username'] == $uname) {
                    $exists = true;
                }
            }

            if ($exists) {
                $em = "The username ($uname) is already taken.";
                return redirect()->to("signup?error=" . urlencode($em . "&" . $data));
            } 
            else {
                $hashed_pass = password_hash($pass, PASSWORD_DEFAULT);
                $user_type = 'customer';

                DB::insert("INSERT INTO users(username, password, user_type) VALUES(?,?,?)", [
                    $uname, $hashed_pass, $user_type
                ]);

                $user_id = DB::getPdo()->lastInsertId();

                DB::insert("INSERT INTO customer(user_id, fname, lname, phone_number, email_address, address, gender) VALUES(?,?,?,?,?,?,?)", [
                    $user_id, $fname, $lname, $phone, $email, $address, $gender
                ]);

                $sm = "Account created successfully!";
                return redirect()->to("signup?success=" . urlencode($sm));
            }
        }
    }

    // Contact us logic

    public function contact(Request $request)
    {
        $email     = $request->input('email');
        $full_name = $request->input('full_name');
        $message   = $request->input('message');

        if (empty($email)) {
            return redirect()->to('/#contact')->with('error', 'Email is required');
        } else if (empty($full_name)) {
            return redirect()->to('/#contact')->with('error', 'Full name is required');
        } else if (empty($message)) {
            return redirect()->to('/#contact')->with('error', 'Message is required');
        } else {
            $sql  = "INSERT INTO message (sender_full_name, sender_email, message) VALUES (?, ?, ?)";
            DB::insert($sql, [$full_name, $email, $message]);
            
            return redirect()->to('/#contact')->with('success', 'Message sent successfully');
        }
    }
}