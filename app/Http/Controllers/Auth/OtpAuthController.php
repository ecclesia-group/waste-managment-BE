<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Actor;
use App\Models\Admin;
use App\Models\Client;
use App\Models\DistrictAssembly;
use App\Models\Facility;
use App\Models\Provider;
use App\Models\Role;
use App\Traits\AppNotifications;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OtpAuthController extends Controller
{
    /** @var array<string, class-string<Actor>> */
    private const ACTOR_MODELS = [
        'admin' => Admin::class,
        'provider' => Provider::class,
        'client' => Client::class,
        'facility' => Facility::class,
        'district_assembly' => DistrictAssembly::class,
    ];

    public function send(Request $request, string $actor): JsonResponse
    {
        $this->assertActor($actor);

        $data = $request->validate([
            'phone_number' => ['required', 'string'],
            'type' => ['required', 'string', 'in:login,password_reset'],
            'actor' => ['sometimes', 'string'],
        ]);

        if (isset($data['actor']) && $data['actor'] !== $actor) {
            throw ValidationException::withMessages([
                'actor' => ['Actor in body must match the URL actor.'],
            ]);
        }

        $user = $this->findActorByPhone($actor, $data['phone_number']);
        if (! $user) {
            throw ValidationException::withMessages([
                'phone_number' => ['No account found for this phone number.'],
            ]);
        }

        $otp = self::otpCode(
            type: $data['type'],
            actor_id: (string) $user->id,
            channel: 'phone',
            guard: $actor,
        );

        AppNotifications::sendSms(
            (string) $user->phone_number,
            'Your verification code is '.$otp.'. It expires in 10 minutes.',
            (string) config('services.sms.from', 'WMS'),
            $actor.'_otp_'.$data['type'],
        );

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'OTP sent successfully',
            status_code: self::API_SUCCESS,
            data: [
                'phone_number' => $user->phone_number,
                'type' => $data['type'],
                'actor' => $actor,
            ]
        );
    }

    public function verify(Request $request, string $actor): JsonResponse
    {
        $this->assertActor($actor);

        $data = $request->validate([
            'phone_number' => ['required', 'string'],
            'token' => ['required', 'string', 'digits:6'],
            'type' => ['required', 'string', 'in:login,password_reset'],
            'actor' => ['sometimes', 'string'],
        ]);

        if (isset($data['actor']) && $data['actor'] !== $actor) {
            throw ValidationException::withMessages([
                'actor' => ['Actor in body must match the URL actor.'],
            ]);
        }

        $user = $this->findActorByPhone($actor, $data['phone_number']);
        if (! $user) {
            throw ValidationException::withMessages([
                'token' => ['Invalid or expired OTP.'],
            ]);
        }

        $status = self::verifyOtp(
            guard: $actor,
            otp: $data['token'],
            actor_id: (int) $user->id,
        );

        if ($status === self::API_FAIL || $status === self::API_NOT_FOUND) {
            throw ValidationException::withMessages([
                'token' => ['Invalid or expired OTP.'],
            ]);
        }

        if ($data['type'] === 'login') {
            return $this->loginResponse($user, $actor);
        }

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'OTP verified successfully',
            status_code: self::API_SUCCESS,
            data: [
                'phone_number' => $user->phone_number,
                'type' => $data['type'],
                'actor' => $actor,
            ]
        );
    }

    private function loginResponse(Actor $user, string $actor): JsonResponse
    {
        if (($user->status ?? 'active') !== 'active') {
            return self::apiResponse(
                in_error: true,
                message: 'Action Unsuccessful',
                reason: 'Account is suspended or deactivated',
                status_code: self::API_FAIL,
                data: []
            );
        }

        if (! (bool) ($user->is_main ?? true) && in_array($actor, ['admin', 'provider', 'facility', 'district_assembly'], true)) {
            $ownerSlug = $user->ownerSlug();
            $role = Role::query()
                ->where('role_slug', $user->role_slug)
                ->where('actor', $actor)
                ->where('actor_slug', $ownerSlug)
                ->first();

            if (! $role) {
                return self::apiResponse(
                    in_error: true,
                    message: 'Action Unsuccessful',
                    reason: 'Team member role is missing or invalid',
                    status_code: self::API_FAIL,
                    data: []
                );
            }
        }

        $user = self::apiToken($user, $actor);
        $payload = array_merge($user->toArray(), $user->rbacForFrontend());

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: ucfirst($actor).' logged in successfully',
            status_code: self::API_SUCCESS,
            data: $payload
        );
    }

    private function assertActor(string $actor): void
    {
        if (! isset(self::ACTOR_MODELS[$actor])) {
            throw ValidationException::withMessages([
                'actor' => ['Unsupported actor type.'],
            ]);
        }
    }

    private function findActorByPhone(string $actor, string $phoneNumber): ?Actor
    {
        $modelClass = self::ACTOR_MODELS[$actor];
        $candidates = $this->phoneLookupCandidates($phoneNumber);

        /** @var Actor|null $user */
        $user = $modelClass::query()
            ->whereIn('phone_number', $candidates)
            ->first();

        return $user;
    }

    /** @return list<string> */
    private function phoneLookupCandidates(string $phoneNumber): array
    {
        $digits = preg_replace('/\D+/', '', $phoneNumber) ?? '';
        $candidates = array_values(array_filter([$phoneNumber, $digits]));

        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            $candidates[] = '233'.substr($digits, 1);
        }

        if (str_starts_with($digits, '233') && strlen($digits) >= 12) {
            $candidates[] = '0'.substr($digits, 3);
        }

        return array_values(array_unique($candidates));
    }
}
