<?php
namespace App\Traits;

use App\Models\Actor;
use App\Models\Category;
use App\Models\OtpToken;
use App\Models\Transaction;
use App\Models\User;
use AshAllenDesign\ShortURL\Facades\ShortURL;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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

    protected static function transformDealSummary(object $item): array
    {
        return [
            "id"                         => $item->deal_id,
            "vendor_id"                  => $item->vendor_id,
            "deal_slug"                  => $item->deal_slug,
            "title"                      => $item->title,
            "category"                   => $item->category ?? [],
            "quantity"                   => $item->quantity ?? [],
            "status"                     => $item->status,
            "image"                      => $item->image,
            "images"                     => $item->images ?? [],
            "company_logo"               => $item->logo,
            "company_name"               => $item->brand_name,
            "google_map_url"             => $item->google_map_url,
            "price"                      => $item->price,
            "discounted_price"           => $item->discounted_price,
            "discount_percentage"        => $item->discount_percentage,
            "created_at"                 => $item->created_at,
            "start_date"                 => $item->start_date,
            "expiry_date"                => $item->expiry_date,
            "region_location"            => $item->region_location ?? [],
            "partner_location"           => $item->partner_location ?? [],
            "target_branches"            => $item->target_branches ?? [],
            "promote_deal"               => $item->promote_deal,
            "redemption_type"            => $item->redemption_type ?? [],
            "online_link"                => $item->online_link,
            "offer_type"                 => $item->offer_type,
            "sku"                        => $item->sku,
            "properties"                 => $item->properties ?? [],
            "redemption_limits"          => $item->redemption_limits ?? [],
            "exclusions"                 => $item->exclusions ?? [],
            "age"                        => self::decodeJsonArray($item->age) ?? $item->age,
            "delivery_fee"               => $item->delivery_fee,
            "free_delivery_areas"        => $item->free_delivery_areas,
            "validity_type"              => $item->validity_type,
            "flash"                      => $item->flash,
            "terms"                      => $item->terms_and_conditions,
            "refund_policy"              => $item->refund_policy,
            "reject_reason"              => $item->reject_reason,
            "rejected_date"              => $item->rejected_date,
            "initial_blast"              => $item->initial_blast,
            "sms_delivery_status"        => $item->sms_delivery_status,
            "sms_count"                  => $item->sms_count,
            "subscriber_count"           => $item->subscriber_count,
            "bogo"                       => self::decodeJsonArray($item->bogo) ?? $item->bogo,
            "bundle"                     => self::decodeJsonArray($item->bundle) ?? $item->bundle,
            "details"                    => [
                "description"             => $item->description,
                "redemption_instructions" => $item->redemption_instructions,
            ],
            "contact"                    => [
                "email"        => $item->email,
                "phone_number" => $item->deals_phone_number,
                "website"      => $item->website,
                "social_media" => [
                    "facebook"  => $item->facebook,
                    "instagram" => $item->instagram,
                    "twitter"   => $item->twitter,
                ],
            ],
            "redemption_region_location" => $item->redemption_region_location ?? [],
            "redemption_branches"        => $item->redemption_branches ?? [],
            "redemption_partners"        => $item->redemption_partners ?? [],
        ];
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

    protected static function processImage(array $image_fields, array $data)
    {
        foreach ($image_fields as $field)
        {
            if (isset($data[$field]) && is_string($data[$field]))
            {
                $is_base_64   = str_starts_with($data[$field], 'data:image');
                $data[$field] = $is_base_64 ? static::base64ImageDecode($data[$field]) : $data[$field];
            }
        }

        return $data;
    }
}
