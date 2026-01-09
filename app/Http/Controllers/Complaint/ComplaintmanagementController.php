<?php
namespace App\Http\Controllers\Complaint;

use App\Http\Controllers\Controller;
use App\Http\Requests\Complaint\ComplaintCreationRequest;
use App\Models\Complaint;
use Illuminate\Support\Str;

class ComplaintmanagementController extends Controller
{
    // Lists all complaints
    public function listComplaints()
    {
        $complaints = Complaint::all();
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
        $data         = $request->validated();
        $data['code'] = Str::uuid();

        $image_fields = [
            'images',
        ];
        // $video_fields = [
        //     'videos',
        // ];

        $data      = static::processImage($image_fields, $data);
        // $data      = static::processVideo($video_fields, $data);
        $complaint = Complaint::create($data);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Complaint created successfully",
            status_code: self::API_SUCCESS,
            data: $complaint->toArray()
        );
    }

}
