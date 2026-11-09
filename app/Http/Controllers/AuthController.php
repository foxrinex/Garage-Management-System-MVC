<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\Customer;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'uname' => 'required|string',
            'pass'  => 'required|string',
            'role'  => 'required|in:1,2,3',
        ], [
            'uname.required' => 'Username is required',
            'pass.required'  => 'Password is required',
            'role.required'  => 'An error Occurred',
            'role.in'        => 'Invalid Role selection',
        ]);

        $roleMap = [
            '1' => 'Manager',
            '2' => 'Employee',
            '3' => 'Customer',
        ];

        $uname = strtolower($request->input('uname'));
        $expectedRole = $roleMap[$request->input('role')];

        // Find user by username (matching legacy query flexibility)
        $user = User::where('username', $uname)->first();

        if ($user && Hash::check($request->input('pass'), $user->password)) {
            // Normalize database user_type check case-insensitively
            $dbUserType = ucfirst(strtolower($user->user_type));

            if ($dbUserType !== $expectedRole) {
                return redirect()->route('login')->with('error', 'Incorrect Username or Password for this role');
            }

            Auth::login($user);

            session([
                'role'    => $dbUserType,
                'user_id' => $user->uid,
            ]);

            return match ($dbUserType) {
                'Manager'  => redirect()->route('manager.dashboard'),
                'Employee' => redirect()->route('employee.dashboard'),
                'Customer' => redirect()->route('customer.dashboard'),
                default    => redirect()->route('login')->with('error', 'Invalid role context'),
            };
        }

        return redirect()->route('login')->with('error', 'Incorrect Username or Password');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function showSignup()
    {
        return view('signup');
    }

    public function signup(Request $request)
    {
        // Validate incoming fields matching legacy requirements
        $request->validate([
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'uname' => 'required|string|max:255|unique:users,username',
            'pass' => 'required|string|min:3',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:50',
            'address' => 'required|string|max:500',
            'gender' => 'required|string|in:Male,Female',
        ]);

        try {
            DB::beginTransaction();

            // 1. Insert into users table matching legacy columns
            $user = User::create([
                'username' => strtolower($request->uname),
                'password' => Hash::make($request->pass),
                'user_type' => 'customer', 
            ]);

            // 2. Insert into customer table matching legacy columns exactly
            Customer::create([
                'user_id' => $user->uid, // Assuming primary key on users is 'uid'
                'fname' => $request->fname,
                'lname' => $request->lname,
                'phone_number' => $request->phone,
                'email_address' => $request->email,
                'address' => $request->address,
                'gender' => $request->gender,
            ]);

            DB::commit();

            return redirect()->route('signup')->with('success', 'Account created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'An error occurred while creating your account. Please try again.');
        }
    }
}


