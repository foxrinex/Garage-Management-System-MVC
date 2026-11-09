<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Or use a Setting Eloquent model if you have one

class HomeController extends Controller
{
    public function index()
    {
        // Fetch the first row of settings (matching ID 1 from your seed data)
        $setting = DB::table('setting')->first();

        // If settings don't exist, fallback or handle gracefully
        if (!$setting) {
            return redirect()->route('login');
        }

        return view('index', compact('setting'));
    }
}