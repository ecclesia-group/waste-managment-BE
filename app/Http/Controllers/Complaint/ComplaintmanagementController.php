<?php
namespace App\Http\Controllers\Complaint;

use App\Http\Controllers\Controller;
use App\Http\Requests\Complaint\ComplaintCreationRequest;
use App\Http\Requests\Complaint\ComplaintUpdateRequest;
use App\Models\Complaint;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ComplaintmanagementController extends Controller
{
    // Lists all complaints
    public function listClientComplaints()
    {
        $user       = request()->user();
        $complaints = Complaint::where('provider_slug', $user->provider_slug)->get();
        if ($complaints->isEmpty()) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "No complaints found",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Complaints retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $complaints?->toArray()
        );
    }

    public function listComplaints()
    {
        $user       = request()->user();

        // Platform-wide listing for Super Admin.
        if (isset($user->admin_slug)) {
            $complaints = Complaint::latest()->get();
            $stats = Complaint::query()
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->get()
                ->keyBy('status')
                ->toArray();

            return self::apiResponse(
                in_error: false,
                message: "Action Successful",
                reason: "Complaints retrieved successfully",
                status_code: self::API_SUCCESS,
                data: [
                    'complaints' => $complaints->toArray(),
                    'stats' => $stats,
                ]
            );
        }

        // Client listing.
        $complaints = Complaint::where([
            'client_slug' => $user->client_slug,
            'provider_slug' => $user->provider_slug,
        ])->get();

        if ($complaints->isEmpty()) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "No complaints found",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Complaints retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $complaints?->toArray()
        );
    }

    // Gets details of a single complaint
    public function getComplaintDetails(Complaint $complaint)
    {
        $user = request()->user();

        // Prevent cross-tenant leakage: ensure the complaint belongs to the current actor.
        // This method is reused for both client and provider routes.
        if (isset($user->client_slug)) {
            // Client can only view their own complaints.
            if ((string) $complaint->client_slug !== (string) $user->client_slug) {
                return self::apiResponse(
                    in_error: true,
                    message: "Action Failed",
                    reason: "Unauthorized to view this complaint",
                    status_code: self::API_FAIL,
                    data: []
                );
            }

            if (isset($user->provider_slug) && (string) $complaint->provider_slug !== (string) $user->provider_slug) {
                return self::apiResponse(
                    in_error: true,
                    message: "Action Failed",
                    reason: "Unauthorized to view this complaint",
                    status_code: self::API_FAIL,
                    data: []
                );
            }
        } else {
            // Provider can view only complaints created under their provider_slug.
            if (isset($user->provider_slug) && (string) $complaint->provider_slug !== (string) $user->provider_slug) {
                return self::apiResponse(
                    in_error: true,
                    message: "Action Failed",
                    reason: "Unauthorized to view this complaint",
                    status_code: self::API_FAIL,
                    data: []
                );
            }
        }

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Complaint details retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $complaint->toArray()
        );
    }

    // create complaint
    public function createComplaint(ComplaintCreationRequest $request)
    {
        $user = $request->user();

        $data                  = $request->validated();
        $data['code']          = Str::random(5);
        $data['client_slug']   = $user->client_slug;
        $data['provider_slug'] = $user->provider_slug;

        $data = static::processImage(['images'], $data);

        $complaint = Complaint::create($data);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Complaint created successfully",
            status_code: self::API_SUCCESS,
            data: $complaint->toArray()
        );
    }
    public function updateComplaint(ComplaintUpdateRequest $request, Complaint $complaint)
    {
        $user = $request->user();

        if ($complaint->client_slug !== $user->client_slug) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Unauthorized",
                status_code: self::API_FAIL
            );
        }

        $data = $request->validated();

        if (array_key_exists('images', $data)) {
            // Convert base64 → URL
            $data = static::processImage(['images'], $data);

            // ✅ REPLACE images with final list (NO MERGE)
            $data['images'] = array_values(array_unique($data['images']));
        }

        $complaint->update($data);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Complaint updated successfully",
            status_code: self::API_SUCCESS,
            data: $complaint->fresh()->toArray()
        );
    }

    public function deleteComplaint(Complaint $complaint)
    {
        $user = request()->user();

        // Verify ownership
        if ($complaint->client_slug !== $user->client_slug) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Unauthorized to delete this complaint",
                status_code: self::API_FAIL,
                data: []
            );
        }

        // Delete associated images and videos
        if ($complaint->images) {
            foreach ($complaint->images as $image) {
                static::deleteImage($image);
            }
        }
        if ($complaint->videos) {
            foreach ($complaint->videos as $video) {
                static::deleteImage($video); // Reuse deleteImage for videos
            }
        }

        $complaint->delete();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Complaint deleted successfully",
            status_code: self::API_SUCCESS,
            data: []
        );
    }

    public function updateComplaintStatus(Complaint $complaint)
    {
        $user = request()->user();

        // Provider-scoped status updates.
        if (isset($user->provider_slug) && (string) $complaint->provider_slug !== (string) $user->provider_slug) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Unauthorized to update this complaint status",
                status_code: self::API_FAIL,
                data: []
            );
        }

        $data = request()->validate([
            'status' => 'required|string|in:pending,in_progress,resolved',
        ]);

        $complaint->update(['status' => $data['status']]);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Complaint status updated successfully",
            status_code: self::API_SUCCESS,
            data: $complaint->toArray()
        );
    }

}
