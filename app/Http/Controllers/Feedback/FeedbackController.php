<?php
namespace App\Http\Controllers\Feedback;

use App\Http\Controllers\Controller;
use App\Http\Requests\Feedback\CreateClientFeedbackRequest;
use App\Http\Requests\Feedback\UpdateClientFeedbackRequest;
use App\Models\Feedback;
use Illuminate\Support\Str;

class FeedbackController extends Controller
{
    //create a method to store feedback
    public function createFeedback(CreateClientFeedbackRequest $request)
    {
        $user                = $request->user();
        $data                = $request->validated();
        $data['code']        = Str::uuid();
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
        $feedbacks = Feedback::all();
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
