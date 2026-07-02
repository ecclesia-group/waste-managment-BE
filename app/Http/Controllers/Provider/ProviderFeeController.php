<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\StoreProviderFeeRequest;
use App\Http\Requests\Provider\UpdateProviderFeeRequest;
use App\Models\ProviderFee;
use Illuminate\Http\Request;

class ProviderFeeController extends Controller
{
    public function index(Request $request)
    {
        $ownerSlug = (string) self::providerSlug($request->user());

        return $this->paginatedApiResponse(
            ProviderFee::query()
                ->forProvider($ownerSlug)
                ->orderBy('name')
                ->paginate($this->perPage($request)),
            'Provider fees retrieved successfully'
        );
    }

    public function store(StoreProviderFeeRequest $request)
    {
        $fee = ProviderFee::create([
            'provider_slug' => (string) self::providerSlug($request->user()),
            'name' => $request->validated('name'),
            'amount' => $request->validated('amount'),
        ]);

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Provider fee created successfully',
            status_code: self::API_CREATED,
            data: $fee->toArray()
        );
    }

    public function update(UpdateProviderFeeRequest $request, ProviderFee $fee)
    {
        if ((string) $fee->provider_slug !== (string) self::providerSlug($request->user())) {
            return self::apiResponse(
                in_error: true,
                message: 'Action Failed',
                reason: 'Unauthorized to update this fee',
                status_code: self::API_FAIL,
                data: []
            );
        }

        $fee->update($request->validated());

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Provider fee updated successfully',
            status_code: self::API_SUCCESS,
            data: $fee->fresh()->toArray()
        );
    }

    public function destroy(Request $request, ProviderFee $fee)
    {
        if ((string) $fee->provider_slug !== (string) self::providerSlug($request->user())) {
            return self::apiResponse(
                in_error: true,
                message: 'Action Failed',
                reason: 'Unauthorized to delete this fee',
                status_code: self::API_FAIL,
                data: []
            );
        }

        $fee->delete();

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Provider fee deleted successfully',
            status_code: self::API_SUCCESS,
            data: []
        );
    }
}
