<?php
namespace App\Http\Controllers\Facility;

use App\Http\Controllers\Controller;
use App\Http\Requests\Facility\FacilityAccountStatusRequest;
use App\Http\Requests\Facility\FacilityOnboardingRequest;
use App\Http\Requests\Facility\UpdateFacilityProfileRequest;
use App\Models\Facility;
use App\Models\Notification;
use App\Models\WeighbridgeRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class FacilityController extends Controller
{
    public function register(FacilityOnboardingRequest $request)
    {
        $password              = Str::random(8);
        $data                  = $request->validated();
        $data['facility_slug'] = Str::uuid();
        $data['password']      = $password;

        // get all images and check for bases 64 or url business_certificate_image, district_assembly_contract_image, tax_certificate_image, epa_permit_image, profile_image
        $image_fields = [
            'business_certificate_image',
            'district_assembly_contract_image',
            'tax_certificate_image',
            'epa_permit_image',
            'profile_image',
        ];

        $data     = static::processImage($image_fields, $data);
        $facility = Facility::create($data);

        self::sendEmail(
            $facility->email,
            email_class: "App\Mail\ActorAccountCreationMail",
            parameters: [
                $facility->email,
                $password,
                $facility->phone_number,
                $login_url = "https://wastefacility.tripsecuregh.com/login",
            ]
        );

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Facility registered successfully",
            status_code: self::API_SUCCESS,
            data: $facility->toArray()
        );
    }

    public function index(Request $request)
    {
        $query = Facility::query();

        // Super Admin can filter facilities by MMDA slug.
        if ($request->filled('district_assembly')) {
            $query->where('district_assembly', (string) $request->string('district_assembly'));
        }

        $facilities = $query->get();

        if (! $facilities) {
            return self::apiResponse(in_error: true, message: "Action Failed", reason: "No facilities found", status_code: self::API_FAIL);
        }
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Facilities retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $facilities->toArray()
        );
    }

    public function show(Facility $facility)
    {
        if (Auth::guard('admin')->check()) {
            $facilitySlug = $facility->facility_slug;

            $payload = array_merge($facility->toArray(), [
                'weighbridge_records' => WeighbridgeRecord::query()
                    ->where('facility_slug', $facilitySlug)
                    ->orderByDesc('created_at')
                    ->get()
                    ->toArray(),
            ]);

            return self::apiResponse(
                in_error: false,
                message: "Action Successful",
                reason: "Facility details retrieved successfully",
                status_code: self::API_SUCCESS,
                data: $payload
            );
        }

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Facility details retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $facility->toArray()
        );
    }

    public function updateStatus(FacilityAccountStatusRequest $request)
    {
        $data             = $request->validated();
        $facility         = Facility::where('facility_slug', $data['facility_slug'])->first();
        $facility->status = $data['status'];

        if (($data['status'] ?? 'active') !== 'active') {
            $facility->suspension_reason = $data['suspension_reason'] ?? $facility->suspension_reason;
            $facility->corrective_action = $data['corrective_action'] ?? $facility->corrective_action;
            $facility->suspended_at = now();

            Notification::create([
                'actor' => 'facility',
                'actor_id' => (string) $facility->id,
                'actor_slug' => $facility->facility_slug,
                'title' => 'Account suspended',
                'message' => trim(
                    'Your facility account has been suspended.'
                    . ($facility->suspension_reason ? ' Reason: ' . $facility->suspension_reason . '.' : '')
                    . ($facility->corrective_action ? ' Corrective action: ' . $facility->corrective_action . '.' : '')
                ),
                'type' => 'account_suspension',
            ]);
        } else {
            $facility->suspension_reason = null;
            $facility->corrective_action = null;
            $facility->suspended_at = null;

            Notification::create([
                'actor' => 'facility',
                'actor_id' => (string) $facility->id,
                'actor_slug' => $facility->facility_slug,
                'title' => 'Account reactivated',
                'message' => 'Your facility account is active again.',
                'type' => 'account_reactivation',
            ]);
        }
        $facility->save();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Facility status updated successfully",
            status_code: self::API_SUCCESS,
            data: $facility->toArray()
        );
    }

    public function updateProfile(UpdateFacilityProfileRequest $request)
    {
        $data = $request->validated();

        $image_fields = [
            'business_certificate_image',
            'district_assembly_contract_image',
            'tax_certificate_image',
            'epa_permit_image',
            'profile_image',
        ];

        $data = static::processImage($image_fields, $data);
        request()->user()->update($data);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Facility details updated successfully",
            status_code: self::API_SUCCESS,
            data: request()->user()->toArray()
        );
    }

    public function updateFacilityProfile(UpdateFacilityProfileRequest $request, Facility $facility)
    {
        $data         = $request->validated();
        $image_fields = [
            'business_certificate_image',
            'district_assembly_contract_image',
            'tax_certificate_image',
            'epa_permit_image',
            'profile_image',
        ];

        $data = static::processImage($image_fields, $data);

        $facility->update($data);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Facility details updated successfully",
            status_code: self::API_SUCCESS,
            data: $facility->toArray()
        );
    }

    public function deleteFacility(Facility $facility)
    {
        $facility->delete();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Facility deleted successfully",
            status_code: self::API_SUCCESS,
            data: null
        );
    }
}
