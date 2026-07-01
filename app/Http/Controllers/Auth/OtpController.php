<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Users\Actions\SendOtpAction;
use App\Domain\Users\Actions\VerifyOtpAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class OtpController extends Controller
{
    /**
     * Show the OTP verification page.
     */
    public function show(string $purpose = 'verification'): View
    {
        return view('pages::auth.verify-otp', ['purpose' => $purpose]);
    }

    /**
     * Send a new OTP code.
     */
    public function send(Request $request, SendOtpAction $sendOtp): RedirectResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'regex:/^6\d{8}$/'],
            'purpose' => ['required', 'string', 'in:verification,login,password_reset'],
        ]);

        $key = 'otp-send:'.$request->phone;

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);

            return back()->withErrors([
                'phone' => __('auth.throttle_otp', ['seconds' => $seconds]),
            ]);
        }

        RateLimiter::hit($key, 60);

        $sendOtp->execute($request->phone, $request->purpose);

        session(['otp_phone' => $request->phone, 'otp_purpose' => $request->purpose]);

        return redirect()->route('otp.verify', ['purpose' => $request->purpose]);
    }

    /**
     * Verify an OTP code.
     */
    public function verify(Request $request, VerifyOtpAction $verifyOtp): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $phone = session('otp_phone');
        $purpose = session('otp_purpose', 'verification');

        if (! $phone) {
            return redirect()->route('login')->withErrors([
                'phone' => __('auth.session_expired'),
            ]);
        }

        $result = $verifyOtp->execute($phone, $request->code, $purpose);

        if (! $result['success']) {
            return back()->withErrors(['code' => $result['message']]);
        }

        // Clear OTP session
        session()->forget(['otp_phone', 'otp_purpose']);

        // Redirect based on purpose
        return match ($purpose) {
            'login' => redirect()->intended(route('dashboard')),
            'password_reset' => redirect()->route('password.reset', ['phone' => $phone]),
            default => redirect()->route('login')->with('status', __('auth.phone_verified')),
        };
    }
}
