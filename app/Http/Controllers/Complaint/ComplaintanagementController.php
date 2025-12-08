<?php
namespace App\Http\Controllers\Complaint;

use App\Http\Controllers\Controller;
use App\Models\Complaint;

class ComplaintanagementController extends Controller
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
            data: $complaints->toArray()
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
    public function createComplaint(Complaint $complaint)
    {

    }

}
