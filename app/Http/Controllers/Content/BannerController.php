<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BannerController extends Controller
{
    public function listForAudience(Request $request)
    {
        $audience = $request->string('audience')->toString();
        if (! $audience) {
            $audience = auth('client')->check() ? 'client' : (auth('provider')->check() ? 'provider' : 'all');
        }
        $now = now();

        $banners = Banner::query()
            ->where('status', 'active')
            ->where(function ($q) use ($audience) {
                $q->where('audience', 'all')->orWhere('audience', $audience);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->latest()
            ->get();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Banners retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $banners->toArray()
        );
    }

    public function adminList()
    {
        $banners = Banner::latest()->get();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Banners retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $banners->toArray()
        );
    }

    public function adminCreate(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string'],
            'message' => ['nullable', 'string'],
            'image' => ['nullable'],
            'audience' => ['required', 'string', 'in:client,provider,all'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $data['banner_slug'] = (string) Str::uuid();

        $data = static::processImage(['image'], $data);

        $banner = Banner::create($data);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Banner created successfully",
            status_code: self::API_CREATED,
            data: $banner->toArray()
        );
    }

    public function adminUpdate(Request $request, Banner $banner)
    {
        $data = $request->validate([
            'title' => ['sometimes', 'string'],
            'message' => ['sometimes', 'nullable', 'string'],
            'image' => ['sometimes', 'nullable'],
            'audience' => ['sometimes', 'string', 'in:client,provider,all'],
            'status' => ['sometimes', 'string', 'in:active,inactive'],
            'starts_at' => ['sometimes', 'nullable', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        if (array_key_exists('image', $data)) {
            $data = static::processImage(['image'], $data);
        }

        $banner->update($data);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Banner updated successfully",
            status_code: self::API_SUCCESS,
            data: $banner->fresh()->toArray()
        );
    }

    public function adminDelete(Banner $banner)
    {
        if ($banner->image) {
            foreach ($banner->image as $img) {
                static::deleteImage($img);
            }
        }

        $banner->delete();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Banner deleted successfully",
            status_code: self::API_SUCCESS,
            data: []
        );
    }
}

