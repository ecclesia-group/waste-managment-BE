<?php

namespace App\Http\Controllers\Handover;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Provider;
use App\Models\WasteHandoverRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WasteHandoverController extends Controller
{
    public function create(Request $request)
    {
        $provider = $request->user();

        $data = $request->validate([
            'title' => ['required', 'string'],
            'waste_types' => ['nullable', 'array'],
            'waste_types.*' => ['string'],
            'description' => ['nullable', 'string'],
            'pickup_location' => ['nullable', 'string'],
            'images' => ['nullable', 'array'],
            'images.*' => ['nullable'],
            'fee_amount' => ['nullable', 'numeric', 'min:0'],
            'target_provider_slug' => ['nullable', 'string', 'exists:providers,provider_slug'],
        ]);

        if (isset($data['target_provider_slug'])) {
            $target = Provider::where('provider_slug', $data['target_provider_slug'])->first();
            if ($target && $target->zone_slug !== $provider->zone_slug) {
                return self::apiResponse(
                    in_error: true,
                    message: "Action Failed",
                    reason: "Target provider is not in your assigned zone",
                    status_code: self::API_FAIL,
                    data: []
                );
            }
        }

        $data = static::processImage(['images'], $data);

        $handover = WasteHandoverRequest::create([
            'code' => Str::upper(Str::random(10)),
            'requester_provider_slug' => $provider->provider_slug,
            'target_provider_slug' => $data['target_provider_slug'] ?? null,
            'title' => $data['title'],
            'waste_types' => $data['waste_types'] ?? [],
            'description' => $data['description'] ?? null,
            'pickup_location' => $data['pickup_location'] ?? null,
            'images' => $data['images'] ?? [],
            'fee_amount' => $data['fee_amount'] ?? 0,
            'status' => 'pending',
        ]);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Waste handover request created successfully",
            status_code: self::API_CREATED,
            data: $handover->toArray()
        );
    }

    public function list(Request $request)
    {
        $provider = $request->user();

        $query = WasteHandoverRequest::query()
            ->where(function ($q) use ($provider) {
                $q->where('requester_provider_slug', $provider->provider_slug)
                    ->orWhere('target_provider_slug', $provider->provider_slug);
            })
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Waste handover requests retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $query->get()->toArray()
        );
    }

    public function show(WasteHandoverRequest $handover, Request $request)
    {
        $provider = $request->user();
        if ($handover->requester_provider_slug !== $provider->provider_slug && $handover->target_provider_slug !== $provider->provider_slug) {
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
            reason: "Waste handover request retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $handover->toArray()
        );
    }

    public function accept(WasteHandoverRequest $handover, Request $request)
    {
        $provider = $request->user();

        if ($handover->status !== 'pending') {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Request is not pending",
                status_code: self::API_FAIL,
                data: []
            );
        }

        if ($handover->target_provider_slug && $handover->target_provider_slug !== $provider->provider_slug) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "This request is assigned to another provider",
                status_code: self::API_FAIL,
                data: []
            );
        }

        $handover->target_provider_slug = $provider->provider_slug;
        $handover->status = 'accepted';
        $handover->accepted_at = now();
        $handover->save();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Waste handover request accepted successfully",
            status_code: self::API_SUCCESS,
            data: $handover->fresh()->toArray()
        );
    }

    public function decline(WasteHandoverRequest $handover, Request $request)
    {
        $provider = $request->user();

        if ($handover->status !== 'pending') {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Request is not pending",
                status_code: self::API_FAIL,
                data: []
            );
        }

        if ($handover->target_provider_slug && $handover->target_provider_slug !== $provider->provider_slug) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "This request is assigned to another provider",
                status_code: self::API_FAIL,
                data: []
            );
        }

        $handover->target_provider_slug = $provider->provider_slug;
        $handover->status = 'declined';
        $handover->save();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Waste handover request declined successfully",
            status_code: self::API_SUCCESS,
            data: $handover->fresh()->toArray()
        );
    }

    public function complete(WasteHandoverRequest $handover, Request $request)
    {
        $provider = $request->user();

        if ($handover->status !== 'accepted') {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Request must be accepted before completion",
                status_code: self::API_FAIL,
                data: []
            );
        }

        if ($handover->target_provider_slug !== $provider->provider_slug) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Only the accepting provider can complete this request",
                status_code: self::API_FAIL,
                data: []
            );
        }

        $paymentData = $request->validate([
            'transaction_id' => ['nullable', 'string', 'unique:payments,transaction_id'],
            'payment_method' => ['nullable', 'string', 'in:momo,card,cash'],
            'network' => ['nullable', 'string'],
            'phone_number' => ['nullable', 'string'],
            'name' => ['nullable', 'string'],
        ]);

        // Optional: record payment for the handover fee
        if (($handover->fee_amount ?? 0) > 0 && ! empty($paymentData['transaction_id'])) {
            Payment::create([
                'client_slug' => 'handover',
                'provider_slug' => $provider->provider_slug,
                'transaction_id' => $paymentData['transaction_id'],
                'payment_method' => $paymentData['payment_method'] ?? 'cash',
                'network' => $paymentData['network'] ?? 'unknown',
                'phone_number' => $paymentData['phone_number'] ?? null,
                'name' => $paymentData['name'] ?? 'handover',
                'client_email' => null,
                'card_name' => null,
                'card_number' => null,
                'card_expiry' => null,
                'card_cvv' => null,
                'amount' => $handover->fee_amount,
                'currency' => 'GHS',
                'status' => 'success',
                'purchase_id' => '0',
                'pickup_id' => 'handover:' . $handover->code,
            ]);
        }

        $handover->status = 'completed';
        $handover->completed_at = now();
        $handover->save();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Waste handover request completed successfully",
            status_code: self::API_SUCCESS,
            data: $handover->fresh()->toArray()
        );
    }
}

