<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\GoogleAuthenticationService;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(GoogleAuthenticationService $authenticationService)
    {
        $googleUser = Socialite::driver('google')->user();

        $user = $authenticationService->authenticate($googleUser);

        return response()->json([
            'authenticated' => Auth::check(),
            'user' => $user,
        ]);
    }
}
