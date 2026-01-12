<?php
namespace App\Traits;

use App\Models\Actor;
use App\Models\OtpToken;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait Helpers
{
    protected static function otpCode(string $type, string $actor_id, string $channel, string $guard): int
    {
        $token    = random_int(1111, 9999);
        $new_time = strtotime('+10 minutes');

        $bool = OtpToken::where("actor_id", $actor_id)
            ->where("guard", $guard)
            ->where("channel", $channel)
            ->where("expires_at", ">", now());

        if ($bool->exists()) {
            $bool        = $bool->first();
            $bool->token = $token;
            $bool->save();

            return $token;
        }

        OtpToken::create([
            "token"      => $token,
            "actor_id"   => $actor_id,
            "guard"      => $guard,
            "type"       => $type,
            "channel"    => $channel,
            "expires_at" => date('Y-m-d H:i:s', $new_time),
        ]);

        return $token;
    }

    protected static function apiToken(Actor $actor, string $oauth_name): Actor
    {
        // dd($oauth_name);
        $accessToken  = $actor->createToken($oauth_name)->accessToken;
        $actor->token = $accessToken;

        return $actor;
    }

    protected static function base64ImageDecode(string $base64_image)
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $base64_image, $matches)) {
            $image_extension = $matches[1];
            $image_data      = substr($base64_image, strpos($base64_image, ',') + 1);

            $fileName  = Str::random(15) . '.' . $image_extension;
            $file_path = "uploads/images/" . $fileName;

            Storage::disk("public")->put($file_path, base64_decode($image_data));
            return config("custom.urls.backend_url") . "/" . "storage/" . $file_path;
        }
    }

    protected static function processImage(array $image_fields, array $data)
    {
        foreach ($image_fields as $field) {
            if (! isset($data[$field]) && ! request()->hasFile($field)) {
                continue;
            }

            $normalized = [];
            // Normalize existing array/base64/urls in payload
            if (isset($data[$field]) && is_array($data[$field])) {
                foreach ($data[$field] as $item) {
                    if (is_string($item)) {
                        $normalized[] = str_starts_with($item, 'data:image')
                            ? static::base64ImageDecode($item)
                            : $item;
                    }
                }
            }

            // Handle file uploads (from multipart form-data)
            if (request()->hasFile($field)) {
                $files = request()->file($field);
                if (! is_array($files)) {
                    $files = [$files];
                }
                foreach ($files as $file) {
                    if ($file && $file->isValid()) {
                        $fileName = Str::random(15) . '.' . $file->getClientOriginalExtension();
                        $filePath = "uploads/images/" . $fileName;
                        Storage::disk("public")->put($filePath, file_get_contents($file->getRealPath()));
                        $normalized[] = config("custom.urls.backend_url") . "/" . "storage/" . $filePath;
                    }
                }
            }

            $data[$field] = array_values($normalized);
        }

        return $data;
    }

    // process video
    protected static function processVideo(array $video_fields, array $data)
    {
        foreach ($video_fields as $field) {
            if (! isset($data[$field]) && ! request()->hasFile($field)) {
                continue;
            }

            $normalized = [];
            if (isset($data[$field]) && is_array($data[$field])) {
                foreach ($data[$field] as $item) {
                    if (is_string($item)) {
                        $normalized[] = str_starts_with($item, 'data:video')
                            ? static::base64VideoDecode($item)
                            : $item;
                    }
                }
            } elseif (isset($data[$field]) && is_string($data[$field])) {
                $normalized[] = str_starts_with($data[$field], 'data:video')
                    ? static::base64VideoDecode($data[$field])
                    : $data[$field];
            }

            // Handle file uploads (from multipart form-data)
            if (request()->hasFile($field)) {
                $files = request()->file($field);
                if (! is_array($files)) {
                    $files = [$files];
                }
                foreach ($files as $file) {
                    if ($file && $file->isValid()) {
                        $fileName = Str::random(15) . '.' . $file->getClientOriginalExtension();
                        $filePath = "uploads/videos/" . $fileName;
                        Storage::disk("public")->put($filePath, file_get_contents($file->getRealPath()));
                        $normalized[] = config("custom.urls.backend_url") . "/" . "storage/" . $filePath;
                    }
                }
            }

            $data[$field] = array_values($normalized);
        }

        return $data;
    }

    protected static function base64VideoDecode(string $base64_video)
    {
        if (preg_match('/^data:video\/(\w+);base64,/', $base64_video, $matches)) {
            $video_extension = $matches[1];
            $video_data = substr($base64_video, strpos($base64_video, ',') + 1);

            $fileName = Str::random(15) . '.' . $video_extension;
            $file_path = "uploads/videos/" . $fileName;

            Storage::disk("public")->put($file_path, base64_decode($video_data));
            return config("custom.urls.backend_url") . "/" . "storage/" . $file_path;
        }
        return $base64_video;
    }

    protected static function deleteImage(?string $image_path): bool
    {
        if (! $image_path) {
            return false;
        }

        try {
            // Extract just the file path from the full URL if it's a URL
            $path = parse_url($image_path, PHP_URL_PATH);
            if ($path) {
                $path = str_replace('/storage/', '', $path);
            } else {
                $path = $image_path;
            }

            if (Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->delete($path);
            }
            return false;
        } catch (\Exception $e) {
            logger()->error('Failed to delete image', ['error' => $e->getMessage(), 'path' => $image_path]);
            return false;
        }
    }

    /**
     * Safely decode a JSON string into an array. Returns null on failure.
     */
    protected static function decodeJsonArray(mixed $value): ?array
    {
        if (! is_string($value)) {
            return null;
        }
        $decoded = json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : null;
    }

    /**
     * Coerce a value (collection/object/array/json string) into a plain PHP array of items.
     */
    protected static function normalizeList(mixed $value): array
    {
        if (is_null($value)) {
            return [];
        }
        // If it's already an array of items
        if (is_array($value)) {
            return $value;
        }
        // JSON stored as string
        $decoded = self::decodeJsonArray($value);
        if (is_array($decoded)) {
            return $decoded;
        }
        // Eloquent Collection
        if (is_object($value) && method_exists($value, 'toArray')) {
            return $value->toArray();
        }
        // Single associative object
        if (is_object($value)) {
            return [(array) $value];
        }
        return [];
    }

    /**
     * Get property from array or object, with default.
     */
    protected static function getProp(mixed $source, string $key, mixed $default = null): mixed
    {
        if (is_array($source)) {
            return $source[$key] ?? $default;
        }
        if (is_object($source)) {
            return $source->{$key} ?? $default;
        }
        return $default;
    }

    protected static function getAuthorizationToken(): string
    {
        $token       = "";
        $auth_header = request()->header('Authorization');

        if ($auth_header) {
            if (str_starts_with($auth_header, 'Bearer ')) {
                $token = substr($auth_header, 7);
            }
        }
        return $token;
    }

    /**
     * Generate QR code image for client bin
     */
    protected static function generateQRCodeImage(string $data, string $clientSlug): string
    {
        // Using a simple API to generate QR code
        // For production, consider installing: composer require simplesoftwareio/simple-qrcode
        $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($data);

        // Download and save the QR code
        $qrCodeContent = @file_get_contents($qrCodeUrl);
        if ($qrCodeContent) {
            $fileName = Str::random(15) . '.png';
            $filePath = "uploads/qrcodes/" . $fileName;
            Storage::disk("public")->put($filePath, $qrCodeContent);
            return config("custom.urls.backend_url") . "/" . "storage/" . $filePath;
        }

        return $qrCodeUrl; // Return the API URL if download fails
    }
}
