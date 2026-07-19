<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    public function dashboard(): View
    {
        $customer = Auth::guard('customer')->user();

        return view('storefront.account.dashboard', [
            'customer' => $customer,
            'orders' => $customer->orders()->with('lines')->get(),
        ]);
    }
}
