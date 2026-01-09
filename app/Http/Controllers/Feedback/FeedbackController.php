<?php
namespace App\Http\Controllers\Feedback;

use App\Http\Controllers\Controller;
use App\Http\Requests\Feedback\CreateClientFeedbackRequest;
use App\Models\Feedback;
use Illuminate\Support\Str;

class FeedbackController extends Controller
{
    //create a method to store feedback
    public function createFeedback(CreateClientFeedbackRequest $request)
    {
        $data         = $request->validated();
        $data['code'] = Str::uuid();

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
}
