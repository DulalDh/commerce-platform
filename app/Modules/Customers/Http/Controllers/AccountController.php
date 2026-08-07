<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Modules\Bookings\Http\Resources\BookingResource;
use App\Modules\Bookings\Models\Booking;
use App\Modules\Customers\Http\Resources\LoyaltyResource;
use App\Modules\Customers\Http\Resources\WalletResource;
use App\Modules\Customers\Models\LoyaltyAccount;
use App\Modules\Customers\Models\WalletAccount;
use App\Modules\Orders\Http\Resources\OrderResource;
use App\Modules\Orders\Models\Order;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * "Central panel": one unified read model across Orders + Bookings (the
 * two domains a platform customer transacts in) plus their wallet/loyalty
 * accounts, all under a single /account/* namespace rather than making the
 * frontend stitch together the tenant-scoped Orders and Bookings endpoints
 * itself.
 */
class AccountController
{
    public function orders(Request $request): JsonResponse
    {
        $orders = Order::withoutGlobalScopes()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return ApiResponse::success(OrderResource::collection($orders));
    }

    public function bookings(Request $request): JsonResponse
    {
        $bookings = Booking::withoutGlobalScopes()
            ->where('customer_id', $request->user()->id)
            ->with('service')
            ->orderByDesc('created_at')
            ->paginate(20);

        return ApiResponse::success(BookingResource::collection($bookings));
    }

    public function wallet(Request $request): JsonResponse
    {
        $wallet = WalletAccount::firstOrCreate(['customer_id' => $request->user()->id]);

        return ApiResponse::success(new WalletResource($wallet->load('transactions')));
    }

    public function loyalty(Request $request): JsonResponse
    {
        $loyalty = LoyaltyAccount::firstOrCreate(['customer_id' => $request->user()->id]);

        return ApiResponse::success(new LoyaltyResource($loyalty->load('transactions')));
    }
}
