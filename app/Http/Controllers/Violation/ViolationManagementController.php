<?php
namespace App\Http\Controllers\Violation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Violation\ViolationCreationRequest;
use App\Http\Requests\Violation\ViolationUpdateRequest;
use App\Models\Violation;
use Illuminate\Support\Str;

class ViolationManagementController extends Controller
{
    // Lists all violations
    public function listViolations()
    {
        $user       = request()->user();
        $violations = Violation::where('client_slug', $user->client_slug)->get();
        if ($violations->isEmpty()) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "No violations found",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Violations retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $violations?->toArray()
        );
    }

    // Gets details of a single violation
    public function getViolationDetails(Violation $violation)
    {
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Violation details retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $violation->toArray()
        );
    }

    // create violation
    public function createViolation(ViolationCreationRequest $request)
    {
        $user                = request()->user();
        $data                = $request->validated();
        $code                = Str::random(5);
        $data['code']        = $code;
        $data['client_slug'] = $user->client_slug;

        $image_fields = ['images'];
        $video_fields = ['videos'];

        $data      = static::processImage($image_fields, $data);
        $data      = static::processVideo($video_fields, $data);
        $violation = Violation::create($data);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Violation created successfully",
            status_code: self::API_SUCCESS,
            data: $violation->toArray()
        );
    }

    public function updateViolation(ViolationUpdateRequest $request, Violation $violation)
    {
        $user = request()->user();

        // Verify ownership
        if ($violation->client_slug !== $user->client_slug) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Unauthorized to update this violation",
                status_code: self::API_FAIL,
                data: []
            );
        }

        $data         = $request->validated();
        $image_fields = ['images'];
        $video_fields = ['videos'];

        // Process images and videos
        $data = static::processImage($image_fields, $data);
        $data = static::processVideo($video_fields, $data);

        $violation->update($data);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Violation updated successfully",
            status_code: self::API_SUCCESS,
            data: $violation->toArray()
        );
    }

    public function deleteViolation(Violation $violation)
    {
        $user = request()->user();

        // Verify ownership
        if ($violation->client_slug !== $user->client_slug) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Unauthorized to delete this violation",
                status_code: self::API_FAIL,
                data: []
            );
        }

        // Delete associated images
        if ($violation->images) {
            foreach ($violation->images as $image) {
                static::deleteImage($image);
            }
        }

        $violation->delete();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Violation deleted successfully",
            status_code: self::API_SUCCESS,
            data: []
        );
    }

    public function updateViolationStatus(Violation $violation)
    {
        $data = request()->validate([
            'status' => 'required|string|in:pending,open,in_progress,closed',
        ]);

        $violation->update(['status' => $data['status']]);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Violation status updated successfully",
            status_code: self::API_SUCCESS,
            data: $violation->toArray()
        );
    }
}
