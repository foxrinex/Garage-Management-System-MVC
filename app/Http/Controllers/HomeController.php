<?php
namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function sendContact(Request $request)
    {
        $request->validate([
            'email'     => 'required|email|max:255',
            'full_name' => 'required|string|max:255',
            'message'   => 'required|string',
        ], [
            'email.required'     => 'Email is required',
            'full_name.required' => 'Full name is required',
            'message.required'   => 'Massage is required', // Preserving your exact legacy error spelling or standard
        ]);

        // Eloquent replacement for raw INSERT query
        Message::create([
            'sender_full_name' => $request->input('full_name'),
            'sender_email'     => $request->input('email'),
            'message'          => $request->input('message'),
        ]);

        return redirect()->to(route('home') . '#contact')
                         ->with('success', 'Message sent successfully');
    }
}