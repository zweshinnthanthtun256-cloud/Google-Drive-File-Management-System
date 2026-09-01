<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')
            ->scopes([
                'https://www.googleapis.com/auth/drive',
            ])
            ->with([
                'access_type' => 'offline',
                'prompt' => 'consent',
            ])
            ->redirect();
    }

    public function callback(Request $request)
    {
        // 1. Prevent 400 ClientException if user cancelled or Google returned an error
        if ($request->has('error') || !$request->has('code')) {
            return redirect('/')
                ->with('error', 'Google authentication was cancelled or failed.');
        }

        try {
            $googleUser = Socialite::driver('google')->user();

            // 2. Prepare update data
            $userData = [
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'google_token' => $googleUser->token,
                'google_token_expires_at' => now()->addSeconds(
                    $googleUser->expiresIn ?? 3600
                ),
            ];

            // 3. Preserve existing refresh token if Google doesn't send a new one
            if (!empty($googleUser->refreshToken)) {
                $userData['google_refresh_token'] = $googleUser->refreshToken;
            }

            $user = User::updateOrCreate(
                [
                    'google_id' => $googleUser->getId(),
                ],
                $userData
            );

            Auth::login($user);

            $request->session()->regenerate();

            return redirect()->route('drive.index');

        } catch (\Exception $e) {
            // Catch any unexpected Socialite or network exceptions gracefully
            return redirect('/')
                ->with('error', 'Failed to authenticate with Google. Please try again.');
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}