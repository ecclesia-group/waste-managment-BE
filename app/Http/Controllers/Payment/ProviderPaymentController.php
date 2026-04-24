<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use App\Models\WeighbridgeRecord;

class ProviderPaymentController extends Controller
{
    private function providerSlugFromUser(object $user): string
    {
        return (bool) ($user->is_main ?? true)
            ? (string) $user->provider_slug
            : (string) ($user->parent_slug ?: $user->provider_slug);
    }

    public function listPayments(Request $request)
    {
        $user = $request->user();
        $providerSlug = $this->providerSlugFromUser($user);

        $status = $request->query('status');
        $query = Payment::query()
            ->with('purchase', 'pickup')
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
        $providerSlug = $this->providerSlugFromUser($user);

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
            data: [
                'payment' => $payment->toArray(),
                'purchase' => $payment->purchase?->toArray(),
                'pickup' => $payment->pickup?->toArray(),
            ]
        );
    }

    public function binsPayments(Request $request)
    {
        $providerSlug = $this->providerSlugFromUser($request->user());
        $items = Payment::query()
            ->where('provider_slug', $providerSlug)
            // ->where('purchase_id', '!=', '0')
            ->where('payment_type', 'pickup')
            ->latest()
            ->get();

        return self::apiResponse(false, "Action Successful", "Bin payments retrieved successfully", self::API_SUCCESS, $items->toArray());
    }

    public function wasteHandoverRequestPayments(Request $request)
    {
        $providerSlug = $this->providerSlugFromUser($request->user());
        $items = Payment::query()
            ->where('provider_slug', $providerSlug)
            ->where('pickup_id', 'like', 'handover:%')
            ->latest()
            ->get();

        return self::apiResponse(false, "Action Successful", "Waste handover payments retrieved successfully", self::API_SUCCESS, $items->toArray());
    }

    public function weighbridgeRecords(Request $request)
    {
        $providerSlug = $this->providerSlugFromUser($request->user());
        $records = WeighbridgeRecord::query()
            ->where('provider_slug', $providerSlug)
            ->latest()
            ->get();

        return self::apiResponse(false, "Action Successful", "Weighbridge records retrieved successfully", self::API_SUCCESS, $records->toArray());
    }
}

