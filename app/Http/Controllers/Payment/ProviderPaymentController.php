<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Payment;
use App\Models\WeighbridgeRecord;
use Illuminate\Http\Request;

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
        $providerSlug = $this->providerSlugFromUser($request->user());

        $status = $request->query('status');
        $query = Payment::query()
            ->with('purchase', 'pickup')
            ->where('provider_slug', $providerSlug)
            ->orderByDesc('created_at');

        if (! empty($status)) {
            $query->where('status', (string) $status);
        }

        return $this->paginatedApiResponse(
            $query->paginate($this->perPage($request)),
            'Provider payments retrieved successfully'
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

        return $this->paginatedApiResponse(
            Payment::query()
                ->where('provider_slug', $providerSlug)
                ->where('payment_type', 'pickup')
                ->latest()
                ->paginate($this->perPage($request)),
            'Bin payments retrieved successfully'
        );
    }

    public function wasteHandoverRequestPayments(Request $request)
    {
        $providerSlug = $this->providerSlugFromUser($request->user());

        return $this->paginatedApiResponse(
            Payment::query()
                ->where('provider_slug', $providerSlug)
                ->where('pickup_id', 'like', 'handover:%')
                ->latest()
                ->paginate($this->perPage($request)),
            'Waste handover payments retrieved successfully'
        );
    }

    public function weighbridgeRecords(Request $request)
    {
        $providerSlug = $this->providerSlugFromUser($request->user());

        return $this->paginatedApiResponse(
            WeighbridgeRecord::query()
                ->where('provider_slug', $providerSlug)
                ->latest()
                ->paginate($this->perPage($request)),
            'Weighbridge records retrieved successfully'
        );
    }

    public function clientPayments(Request $request, Client $client)
    {
        $providerSlug = $this->providerSlugFromUser($request->user());

        return $this->paginatedApiResponse(
            Payment::query()
                ->where('client_slug', $client->client_slug)
                ->where('provider_slug', $providerSlug)
                ->latest()
                ->paginate($this->perPage($request)),
            'Client payments retrieved successfully'
        );
    }

    public function getClientPayment(Request $request, Client $client, Payment $payment)
    {
        $providerSlug = $this->providerSlugFromUser($request->user());
        $payment = Payment::query()
            ->where('client_slug', $client->client_slug)
            ->where('provider_slug', $providerSlug)
            ->where('transaction_id', $payment->transaction_id)
            ->first();

        return self::apiResponse(false, "Action Successful", "Client payment retrieved successfully", self::API_SUCCESS, $payment->toArray());
    }
}
