<?php

namespace App\Http\Controllers\WeighBridge;

use App\Http\Controllers\Controller;
use App\Http\Requests\Weighbridge\CreateTicket;
use App\Models\WeighbridgeRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WeighBridgeController extends Controller
{
    public function registerEntry(CreateTicket $request)
    {
        $facility = $request->user();
        $effectiveFacilitySlug = $facility->facility_slug;
        $effectiveDistrictSlug = $facility->district_assembly;
        $data = $request->validated();

        // Tenant isolation: facility can only scan providers within its district assembly.
        $providerDistrict = \App\Models\Provider::query()
            ->where('provider_slug', $data['provider_slug'])
            ->value('district_assembly');

        if ($providerDistrict !== null && (string) $providerDistrict !== (string) $effectiveDistrictSlug) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Provider is not in this facility's district assembly",
                status_code: self::API_FAIL,
                data: []
            );
        }

        $record = WeighbridgeRecord::create([
            'code' => Str::upper(Str::random(10)),
            'facility_slug' => $effectiveFacilitySlug ?? null,
            'provider_slug' => $data['provider_slug'],
            'fleet_slug' => $data['fleet_slug'] ?? null,
            'fleet_code' => $data['fleet_code'] ?? null,
            'gross_weight' => $data['gross_weight'] ?? null,
            'amount' => $data['amount'],
            'group_id' => $effectiveDistrictSlug ?? 'unknown',
            'payment_status' => $data['payment_status'],
            'scan_status' => $data['scan_status'] ?? 'scanned',
            'notes' => $data['notes'] ?? null,
        ]);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Weighbridge entry recorded successfully",
            status_code: self::API_CREATED,
            data: $record->toArray()
        );
    }

    public function allEntries(Request $request)
    {
        $facility = $request->user();
        $effectiveFacilitySlug = $facility->facility_slug;

        $query = WeighbridgeRecord::query()
            ->where('facility_slug', $effectiveFacilitySlug ?? null)
            ->latest();

        if ($request->filled('provider_slug')) {
            $query->where('provider_slug', $request->string('provider_slug'));
        }
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->string('payment_status'));
        }
        if ($request->filled('scan_status')) {
            $query->where('scan_status', $request->string('scan_status'));
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->date('to'));
        }

        $entries = $query->get();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Weighbridge entries retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $entries->toArray()
        );
    }

    public function show(WeighbridgeRecord $entry, Request $request)
    {
        $facility = $request->user();
        if (($entry->facility_slug ?? null) !== ($facility->facility_slug ?? null)) {
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
            reason: "Weighbridge entry retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $entry->toArray()
        );
    }

    public function updateStatus(Request $request)
    {
        $data = $request->validate([
            'id' => ['required', 'integer', 'exists:weighbridge_records,id'],
            'payment_status' => ['nullable', 'string', 'in:paid,credit'],
            'scan_status' => ['nullable', 'string', 'in:scanned,unscanned,handover'],
        ]);

        $facility = $request->user();
        $effectiveFacilitySlug = $facility->facility_slug;
        $entry = WeighbridgeRecord::where('id', $data['id'])
            ->where('facility_slug', $effectiveFacilitySlug ?? null)
            ->first();

        if (! $entry) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Entry not found",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }

        $entry->fill(array_filter([
            'payment_status' => $data['payment_status'] ?? null,
            'scan_status' => $data['scan_status'] ?? null,
        ], fn ($v) => $v !== null));
        $entry->save();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Weighbridge entry status updated successfully",
            status_code: self::API_SUCCESS,
            data: $entry->toArray()
        );
    }

    public function updateEntry(Request $request, WeighbridgeRecord $entry)
    {
        $facility = $request->user();
        if (($entry->facility_slug ?? null) !== ($facility->facility_slug ?? null)) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Unauthorized",
                status_code: self::API_FAIL,
                data: []
            );
        }

        $data = $request->validate([
            'fleet_slug' => ['nullable', 'string', 'exists:fleets,fleet_slug'],
            'fleet_code' => ['nullable', 'string'],
            'gross_weight' => ['nullable', 'numeric', 'min:0'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'payment_status' => ['nullable', 'string', 'in:paid,credit'],
            'scan_status' => ['nullable', 'string', 'in:scanned,unscanned,handover'],
            'notes' => ['nullable', 'string'],
        ]);

        $entry->update($data);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Weighbridge entry updated successfully",
            status_code: self::API_SUCCESS,
            data: $entry->fresh()->toArray()
        );
    }

    public function deleteEntry(Request $request, WeighbridgeRecord $entry)
    {
        $facility = $request->user();
        if (($entry->facility_slug ?? null) !== ($facility->facility_slug ?? null)) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Unauthorized",
                status_code: self::API_FAIL,
                data: []
            );
        }

        $entry->delete();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Weighbridge entry deleted successfully",
            status_code: self::API_SUCCESS,
            data: []
        );
    }
}
