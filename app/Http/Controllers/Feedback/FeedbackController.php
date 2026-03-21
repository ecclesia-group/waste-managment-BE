<?php
namespace App\Http\Controllers\Feedback;

use App\Http\Controllers\Controller;
use App\Http\Requests\Feedback\CreateClientFeedbackRequest;
use App\Http\Requests\Feedback\UpdateClientFeedbackRequest;
use App\Models\Feedback;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class FeedbackController extends Controller
{
    //create a method to store feedback
    public function createFeedback(CreateClientFeedbackRequest $request)
    {
        $user                = $request->user();
        $data                = $request->validated();
        $code                = Str::random(5);
        $data['code']        = $code;
        $data['client_slug'] = $user->client_slug;

        // Store feedback logic here
        $feedback = Feedback::create($data);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Feedback created successfully",
            status_code: self::API_SUCCESS,
            data: $feedback->toArray()
        );
    }

    //list all feedbacks
    public function listFeedbacks()
    {
        $user      = Auth::user();
        // Feedback table does not include provider_slug; feedback is scoped by client.
        $feedbacks = Feedback::where('client_slug', $user->client_slug)->get();
        if ($feedbacks->isEmpty()) {
            return self::apiResponse(
                in_error: true,
                message: "No Feedbacks Found",
                reason: "There are no feedbacks available",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Feedbacks retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $feedbacks->toArray()
        );
    }

    //get single feedback details
    public function getFeedbackDetails(Feedback $feedback)
    {
        $user = request()->user();

        // Tenant isolation: clients can only view feedback created by themselves.
        if (isset($user->client_slug) && (string) $feedback->client_slug !== (string) $user->client_slug) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Unauthorized to view this feedback",
                status_code: self::API_FAIL,
                data: []
            );
        }

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Feedback details retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $feedback->toArray()
        );
    }

    //delete feedback
    public function deleteFeedback(Feedback $feedback)
    {
        $user = request()->user();

        // Tenant isolation: clients can only delete their own feedback.
        if (isset($user->client_slug) && (string) $feedback->client_slug !== (string) $user->client_slug) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Unauthorized to delete this feedback",
                status_code: self::API_FAIL,
                data: []
            );
        }

        $feedback->delete();
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Feedback deleted successfully",
            status_code: self::API_SUCCESS,
            data: []
        );
    }

    //update feedback
    public function updateFeedback(UpdateClientFeedbackRequest $request, Feedback $feedback)
    {
        $user = request()->user();

        // Tenant isolation: clients can only update their own feedback.
        if (isset($user->client_slug) && (string) $feedback->client_slug !== (string) $user->client_slug) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Unauthorized to update this feedback",
                status_code: self::API_FAIL,
                data: []
            );
        }

        $data = $request->validated();
        $feedback->update($data);
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Feedback updated successfully",
            status_code: self::API_SUCCESS,
            data: $feedback->toArray()
        );
    }
}
