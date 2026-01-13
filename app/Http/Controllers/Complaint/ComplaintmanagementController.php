<?php
namespace App\Http\Controllers\Complaint;

use App\Http\Controllers\Controller;
use App\Http\Requests\Complaint\ComplaintCreationRequest;
use App\Http\Requests\Complaint\ComplaintUpdateRequest;
use App\Models\Complaint;
use Illuminate\Support\Str;

class ComplaintmanagementController extends Controller
{
    // Lists all complaints
    public function listComplaints()
    {
        $user       = request()->user();
        $complaints = Complaint::where('client_slug', $user->client_slug)->get();
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

        $data                = $request->validated();
        $data['code']        = Str::random(5);
        $data['client_slug'] = $user->client_slug;

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

        if (isset($data['images'])) {
            // Convert base64 → stored file
            $data = static::processImage(['images'], $data);

            // Merge old + new images
            $data['images'] = array_values(array_unique(
                array_merge($complaint->images ?? [], $data['images'])
            ));
        }

        $complaint->update($data);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Complaint updated successfully",
            status_code: self::API_SUCCESS,
            data: $complaint->toArray()
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
