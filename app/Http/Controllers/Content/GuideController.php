<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Models\Guide;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GuideController extends Controller
{
    public function listForAudience(Request $request)
    {
        $audience = $request->string('audience')->toString();
        if (! $audience) {
            $audience = auth('client')->check() ? 'client' : (auth('provider')->check() ? 'provider' : 'all');
        }
        $category = $request->string('category')->toString() ?: null;

        $query = Guide::query()
            ->where('status', 'active')
            ->where(function ($q) use ($audience) {
                $q->where('audience', 'all')->orWhere('audience', $audience);
            })
            ->latest();

        if ($category) {
            $query->where('category', $category);
        }

        $guides = $query->get();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Guides retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $guides->toArray()
        );
    }

    public function adminList()
    {
        $guides = Guide::latest()->get();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Guides retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $guides->toArray()
        );
    }

    public function adminCreate(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string'],
            'category' => ['required', 'string'],
            'content' => ['nullable', 'string'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['nullable'],
            'audience' => ['required', 'string', 'in:client,provider,all'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ]);

        $data['guide_slug'] = (string) Str::uuid();
        $data = static::processImage(['attachments'], $data);

        $guide = Guide::create($data);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Guide created successfully",
            status_code: self::API_CREATED,
            data: $guide->toArray()
        );
    }

    public function adminUpdate(Request $request, Guide $guide)
    {
        $data = $request->validate([
            'title' => ['sometimes', 'string'],
            'category' => ['sometimes', 'string'],
            'content' => ['sometimes', 'nullable', 'string'],
            'attachments' => ['sometimes', 'nullable', 'array'],
            'attachments.*' => ['nullable'],
            'audience' => ['sometimes', 'string', 'in:client,provider,all'],
            'status' => ['sometimes', 'string', 'in:active,inactive'],
        ]);

        if (array_key_exists('attachments', $data)) {
            $data = static::processImage(['attachments'], $data);
        }

        $guide->update($data);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Guide updated successfully",
            status_code: self::API_SUCCESS,
            data: $guide->fresh()->toArray()
        );
    }

    public function adminDelete(Guide $guide)
    {
        if ($guide->attachments) {
            foreach ($guide->attachments as $file) {
                static::deleteImage($file);
            }
        }

        $guide->delete();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Guide deleted successfully",
            status_code: self::API_SUCCESS,
            data: []
        );
    }
}

