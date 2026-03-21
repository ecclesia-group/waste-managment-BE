<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class ProviderPaymentController extends Controller
{
    public function listPayments(Request $request)
    {
        $user = $request->user();
        $providerSlug = $user->provider_slug;

        $status = $request->query('status');
        $query = Payment::query()
            ->where('provider_slug', $providerSlug)
            ->orderByDesc('created_at');

        if (! empty($status)) {
            $query->where('status', (string) $status);
        }

        $payments = $query->paginate(20);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Provider payments retrieved successfully",
            status_code: self::API_SUCCESS,
            data: [
                'items' => $payments->items(),
                'pagination' => [
                    'total' => $payments->total(),
                    'per_page' => $payments->perPage(),
                    'current_page' => $payments->currentPage(),
                    'last_page' => $payments->lastPage(),
                ],
            ]
        );
    }

    public function getPayment(Request $request, Payment $payment)
    {
        $user = $request->user();
        $providerSlug = $user->provider_slug;

        if ((string) $payment->provider_slug !== (string) $providerSlug) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Unauthorized",
                status_code: self::API_FAIL,
                data: []
            );
        }

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Payment details retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $payment->toArray()
        );
    }
}

