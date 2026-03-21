<?php
namespace App\Http\Controllers\Violation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Violation\ViolationCreationRequest;
use App\Http\Requests\Violation\ViolationUpdateRequest;
use App\Models\Notification;
use App\Models\Client;
use App\Models\Violation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ViolationManagementController extends Controller
{
    // Lists all violations
    public function listClientViolations()
    {
        $user       = request()->user();
        $violations = Violation::where('provider_slug', $user->provider_slug)->get();
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

    public function listViolations()
    {
        $user       = request()->user();
        $violations = Violation::where(['client_slug' => $user->client_slug, 'provider_slug' => $user->provider_slug])->get();
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
        $user = request()->user();

        // Prevent cross-tenant leakage: ensure the violation belongs to the current actor.
        // This method is reused for both client and provider routes.
        if (isset($user->client_slug)) {
            // Client can only view their own violations.
            if ((string) $violation->client_slug !== (string) $user->client_slug) {
                return self::apiResponse(
                    in_error: true,
                    message: "Action Failed",
                    reason: "Unauthorized to view this violation",
                    status_code: self::API_FAIL,
                    data: []
                );
            }

            if (isset($user->provider_slug) && (string) $violation->provider_slug !== (string) $user->provider_slug) {
                return self::apiResponse(
                    in_error: true,
                    message: "Action Failed",
                    reason: "Unauthorized to view this violation",
                    status_code: self::API_FAIL,
                    data: []
                );
            }
        } else {
            // Provider can view only violations created under their provider_slug.
            if (isset($user->provider_slug) && (string) $violation->provider_slug !== (string) $user->provider_slug) {
                return self::apiResponse(
                    in_error: true,
                    message: "Action Failed",
                    reason: "Unauthorized to view this violation",
                    status_code: self::API_FAIL,
                    data: []
                );
            }
        }

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
        $user = $request->user();
        $data = $request->validated();

        // Providers record violations during pickup; clients can only view them.
        if (! isset($user->provider_slug)) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Unauthorized",
                status_code: self::API_FAIL,
                data: []
            );
        }

        // scan-first tolerance: if client_slug isn't provided, infer from the last scanned bin.
        $clientSlug = $data['client_slug'] ?? null;
        // If Postman/clients send an unexpanded placeholder, treat it as missing.
        if (is_string($clientSlug) && str_contains($clientSlug, '{{')) {
            $clientSlug = null;
        }

        if (! $clientSlug) {
            $clientSlug = Cache::get('wms:last_scanned_client_slug:' . $user->provider_slug);
        }

        if (! $clientSlug) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "client_slug is required or you must scan a bin before creating a violation",
                status_code: self::API_FAIL,
                data: []
            );
        }

        $client = Client::where('client_slug', $clientSlug)
            ->where('provider_slug', $user->provider_slug)
            ->first();

        if (! $client) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Client not found for this provider",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }

        $code                  = Str::random(5);
        $data['code']          = $code;
        $data['client_slug']   = $client->client_slug;
        $data['provider_slug'] = $user->provider_slug;

        $image_fields = ['images'];
        $video_fields = ['videos'];

        $data      = static::processImage($image_fields, $data);
        $data      = static::processVideo($video_fields, $data);
        $violation = Violation::create($data);

        // Always notify the client about the violation (education + transparency).
        $this->createClientViolationNotification(
            client: $client,
            violationType: (string) ($data['type'] ?? ''),
            location: (string) ($data['location'] ?? ''),
            description: $data['description'] ?? null,
            violationCode: $data['code'] ?? null
        );

        // If the violation indicates bin damage, regenerate the bin QR + bin_code.
        if ($this->isBinDamageType((string) ($data['type'] ?? ''))) {
            $this->regenerateClientBinQrAndCode($client);

            $this->createClientViolationNotification(
                client: $client,
                violationType: (string) ($data['type'] ?? 'Bin damaged'),
                location: (string) ($data['location'] ?? ''),
                description: $data['description'] ?? 'Bin QR code was regenerated due to reported damage.',
                violationCode: $data['code'] ?? null,
                notificationType: 'bin_damage'
            );
        }

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Violation created successfully",
            status_code: self::API_SUCCESS,
            data: $violation->toArray()
        );
    }

    protected function isBinDamageType(string $type): bool
    {
        // Keep it strict: regenerate QR only for actual bin damage reports.
        $t = strtolower($type);

        return (str_contains($t, 'bin') && str_contains($t, 'damage'))
            || str_contains($t, 'bin damaged')
            || str_contains($t, 'damaged bin')
            || str_contains($t, 'bin_damage');
    }

    protected function regenerateClientBinQrAndCode(Client $client): void
    {
        // bin_code is used for manual scanning; QR data includes bin_code as well.
        // Regeneration invalidates the old QR codes.
        do {
            $newBinCode = Str::upper(Str::random(10));
        } while (Client::where('bin_code', $newBinCode)->exists());

        $client->bin_code = $newBinCode;

        $qrData = json_encode([
            'client_slug' => $client->client_slug,
            'name' => $client->first_name . ' ' . ($client->last_name ?? ''),
            'phone' => $client->phone_number,
            'email' => $client->email,
            'location' => $client->gps_address,
            'bin_code' => $client->bin_code,
        ]);

        $qrCodeUrl = static::generateQRCodeImage($qrData, $client->client_slug);
        $client->qrcode = $qrCodeUrl ? [$qrCodeUrl] : [];
        $client->save();
    }

    protected function createClientViolationNotification(
        Client $client,
        string $violationType,
        string $location,
        ?string $description,
        ?string $violationCode = null,
        string $notificationType = 'violation'
    ): void {
        $msg = "Violation recorded: {$violationType}. Location: {$location}.";
        if ($description) {
            $msg .= " {$description}";
        }
        if ($violationCode) {
            $msg .= " (Code: {$violationCode})";
        }

        Notification::create([
            'actor' => 'client',
            'actor_id' => (string) $client->id,
            'actor_slug' => $client->client_slug,
            'title' => $notificationType === 'bin_damage' ? 'Bin damaged - QR regenerated' : 'Violation report',
            'message' => $msg,
            'type' => $notificationType,
        ]);
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

        // Get existing images to merge with new ones
        $existingData = [
            'images' => $violation->images ?? [],
            'videos' => $violation->videos ?? [],
        ];

        // Process images and videos (merge with existing)
        $data = static::processImage($image_fields, $data, $existingData);
        $data = static::processVideo($video_fields, $data, $existingData);

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
        $user = request()->user();

        // Provider-scoped update: only allow changing status for violations of this provider.
        if (isset($user->provider_slug) && (string) $violation->provider_slug !== (string) $user->provider_slug) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Unauthorized to update this violation status",
                status_code: self::API_FAIL,
                data: []
            );
        }

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
