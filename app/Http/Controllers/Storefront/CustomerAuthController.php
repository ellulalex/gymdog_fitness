<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Support\Tenancy\TenantManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CustomerAuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        return Auth::guard('customer')->check()
            ? redirect()->route('account')
            : view('storefront.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (! Auth::guard('customer')->attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Those credentials do not match our records.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('account'));
    }

    public function showRegister(): View|RedirectResponse
    {
        return Auth::guard('customer')->check()
            ? redirect()->route('account')
            : view('storefront.auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $tenantId = app(TenantManager::class)->id();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('customers', 'email')->where('tenant_id', $tenantId)],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $customer = Customer::create($data);
        Auth::guard('customer')->login($customer);

        return redirect()->route('account');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
