<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;

class TranslationController extends Controller
{
    public function translate(Request $request)
    {
       $request->validate([
            'locale' => 'required|in:en,km'
        ]);

        session()->put('locale', $request->locale);
        
        return response()->json([
            'success' => true,
            'message' => __('messages.language_switched')
        ]);
    }
}
