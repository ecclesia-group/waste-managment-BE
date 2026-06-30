<?php

namespace App\Http\Controllers\WeighBridge;

use App\Http\Controllers\Controller;
use App\Http\Requests\Weighbridge\CreateTicket;
use App\Models\Facility;
use App\Models\WeighbridgeRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Traits\Helpers;

class WeighBridgeController extends Controller
{
    use Helpers;

    public function createRecord(Request $request)
    {
        // $providerSlug = self::resolveProviderScopeSlug($request->user());
        $providerSlug = self::actorProviderSlug($request->user());
        $data = $request->validate([
            'facility_slug' => ['required', 'string', 'exists:facilities,facility_slug'],
            'fleet_slug' => ['nullable', 'string', 'exists:fleets,fleet_slug'],
            'fleet_code' => ['nullable', 'string'],
            'zone_slug' => ['nullable', 'string', 'exists:zones,zone_slug'],
            'gross_weight' => ['nullable', 'numeric', 'min:0'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $facilityDistrict = Facility::query()
            ->where('facility_slug', $data['facility_slug'])
            ->value('district_assembly');
        $providerDistrict = \App\Models\Provider::query()
            ->where('provider_slug', $providerSlug)
            ->value('district_assembly');

        if ($facilityDistrict !== null && $providerDistrict !== null && (string) $facilityDistrict !== (string) $providerDistrict) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Facility is not in this provider's district assembly",
                status_code: self::API_FAIL,
                data: []
            );
        }

        $record = WeighbridgeRecord::create([
            'code' => 'WB-' . Str::upper(Str::random(8)),
            'facility_slug' => $data['facility_slug'],
            'provider_slug' => $providerSlug,
            'fleet_slug' => $data['fleet_slug'] ?? null,
            'fleet_code' => $data['fleet_code'] ?? null,
            'zone_slug' => $data['zone_slug'] ?? null,
            'gross_weight' => $data['gross_weight'] ?? null,
            'amount' => $data['amount'] ?? null,
            'payment_status' => 'pending_payment',
            'scan_status' => 'handover',
            'notes' => $data['notes'] ?? null,
        ]);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Weighbridge record submitted to facility with pending payment",
            status_code: self::API_CREATED,
            data: $record->toArray()
        );
    }

    public function allRecords(Request $request)
    {
        $providerSlug = self::ownerProviderSlug($request->user());
        $query = WeighbridgeRecord::query()
            ->forProviderOrganisation((string) $providerSlug)
            ->latest();

        if ($request->filled('facility_slug')) {
            $query->where('facility_slug', $request->string('facility_slug'));
        }
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->string('payment_status'));
        }
        if ($request->filled('scan_status')) {
            $query->where('scan_status', $request->string('scan_status'));
        }

        return $this->paginatedApiResponse(
            $query->paginate($this->perPage($request)),
            'Weighbridge records retrieved successfully'
        );
    }

    public function showRecord(Request $request, string $record)
    {
        $providerSlug = self::ownerProviderSlug($request->user());
        $entry = WeighbridgeRecord::query()
            ->forProviderOrganisation((string) $providerSlug)
            ->where('code', $record)
            ->first();

        if (! $entry) {
            return self::apiResponse(true, "Action Failed", "Record not found", self::API_NOT_FOUND, []);
        }

        return self::apiResponse(false, "Action Successful", "Weighbridge record retrieved successfully", self::API_SUCCESS, $entry->toArray());
    }

    public function updateRecordStatus(Request $request)
    {
        $providerSlug = self::ownerProviderSlug($request->user());
        $data = $request->validate([
            'code' => ['required', 'string', 'exists:weighbridge_records,code'],
            'scan_status' => ['required', 'string', 'in:handover,unscanned,scanned'],
        ]);

        $entry = WeighbridgeRecord::query()
            ->forProviderOrganisation((string) $providerSlug)
            ->where('code', $data['code'])
            ->first();

        if (! $entry) {
            return self::apiResponse(true, "Action Failed", "Record not found", self::API_NOT_FOUND, []);
        }

        $entry->scan_status = $data['scan_status'];
        $entry->save();

        return self::apiResponse(false, "Action Successful", "Weighbridge record status updated successfully", self::API_SUCCESS, $entry->toArray());
    }

    public function updateRecord(Request $request, string $record)
    {
        $providerSlug = self::ownerProviderSlug($request->user());
        $entry = WeighbridgeRecord::query()
            ->forProviderOrganisation((string) $providerSlug)
            ->where('code', $record)
            ->first();

        if (! $entry) {
            return self::apiResponse(true, "Action Failed", "Record not found", self::API_NOT_FOUND, []);
        }

        if ($entry->payment_status !== 'pending_payment') {
            return self::apiResponse(true, "Action Failed", "Only pending payment records can be edited by provider", self::API_FAIL, []);
        }

        $data = $request->validate([
            'fleet_slug' => ['nullable', 'string', 'exists:fleets,fleet_slug'],
            'fleet_code' => ['nullable', 'string'],
            'zone_slug' => ['nullable', 'string', 'exists:zones,zone_slug'],
            'gross_weight' => ['nullable', 'numeric', 'min:0'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $entry->update($data);

        return self::apiResponse(false, "Action Successful", "Weighbridge record updated successfully", self::API_SUCCESS, $entry->fresh()->toArray());
    }

    public function deleteRecord(Request $request, string $record)
    {
        $providerSlug = self::ownerProviderSlug($request->user());
        $entry = WeighbridgeRecord::query()
            ->forProviderOrganisation((string) $providerSlug)
            ->where('code', $record)
            ->first();

        if (! $entry) {
            return self::apiResponse(true, "Action Failed", "Record not found", self::API_NOT_FOUND, []);
        }

        $entry->delete();

        return self::apiResponse(false, "Action Successful", "Weighbridge record deleted successfully", self::API_SUCCESS, []);
    }

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
            'zone_slug' => $data['zone_slug'] ?? null,
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

        return $this->paginatedApiResponse(
            $query->paginate($this->perPage($request)),
            'Weighbridge entries retrieved successfully'
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
            // 'id' => ['required', 'integer', 'exists:weighbridge_records,id'],
            'code' => ['required', 'string', 'exists:weighbridge_records,code'],
            'payment_status' => ['nullable', 'string', 'in:pending_payment,paid,credit'],
            'scan_status' => ['nullable', 'string', 'in:handover,unscanned,scanned'],
        ]);

        $facility = $request->user();
        $effectiveFacilitySlug = $facility->facility_slug;
        $entry = WeighbridgeRecord::where('code', $data['code'])
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
        ], fn($v) => $v !== null));
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
            'payment_status' => ['nullable', 'string', 'in:pending_payment,paid,credit'],
            'scan_status' => ['nullable', 'string', 'in:handover,unscanned,scanned'],
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

    public function verifyByTicketCode(Request $request)
    {
        $facility = $request->user();
        $data = $request->validate([
            'code' => ['required', 'string', 'exists:weighbridge_records,code'],
            'payment_status' => ['required', 'string', 'in:paid,credit'],
            'notes' => ['nullable', 'string'],
        ]);

        $entry = WeighbridgeRecord::query()
            ->where('code', $data['code'])
            ->where('facility_slug', $facility->facility_slug)
            ->first();

        if (! $entry) {
            return self::apiResponse(true, "Action Failed", "Record not found for this facility", self::API_NOT_FOUND, []);
        }

        $entry->payment_status = $data['payment_status'];
        $entry->scan_status = 'scanned';
        if (! empty($data['notes'])) {
            $entry->notes = $data['notes'];
        }
        $entry->save();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Ticket verified and payment mode recorded successfully",
            status_code: self::API_SUCCESS,
            data: $entry->toArray()
        );
    }
}
