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
        $user = request()->user();

        return $this->paginatedApiResponse(
            Complaint::query()
                ->where('provider_slug', $user->provider_slug)
                ->with(['client', 'provider'])
                ->latest()
                ->paginate($this->perPage(request())),
            'Complaints retrieved successfully'
        );
    }

    public function listComplaints()
    {
        $user = request()->user();

        // Platform-wide listing for Super Admin.
        if (isset($user->admin_slug)) {
            $complaints = Complaint::query()
                ->with(['client', 'provider'])
                ->latest()
                ->paginate($this->perPage(request()));
            $stats = Complaint::query()
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->get()
                ->keyBy('status')
                ->toArray();

            return $this->paginatedApiResponse(
                $complaints,
                'Complaints retrieved successfully',
                'complaints',
                ['stats' => $stats]
            );
        }

        return $this->paginatedApiResponse(
            Complaint::query()
                ->where('client_slug', $user->client_slug)
                ->where('provider_slug', $user->provider_slug)
                ->with(['client', 'provider'])
                ->latest()
                ->paginate($this->perPage(request())),
            'Complaints retrieved successfully'
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
            data: $complaint->load('client')->toArray()
        );
    }

    // create complaint
    public function createComplaint(ComplaintCreationRequest $request)
    {
        $user = $request->user();

        $data                  = $request->validated();
        $data['code']          = Str::upper(Str::random(8));
        $data['client_slug']   = $user->client_slug ?? null;
        $data['provider_slug'] = $user->provider_slug ?? null;

        $data = static::processImage(['images'], $data);

        $complaint = Complaint::create($data)->fresh();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Complaint created successfully",
            status_code: self::API_SUCCESS,
            data: $complaint->load('client')->toArray()
        );
    }

    // Update complaint
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

        $data = static::processImage(['images'], $data);

        $complaint->update($data);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Complaint updated successfully",
            status_code: self::API_SUCCESS,
            data: $complaint->fresh()->load('client')->toArray()
        );
    }

    // Delete complaint
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

        $complaint->delete();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Complaint deleted successfully",
            status_code: self::API_SUCCESS,
            data: []
        );
    }

    // Update complaint status
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
            data: $complaint->load('client')->toArray()
        );
    }

}
