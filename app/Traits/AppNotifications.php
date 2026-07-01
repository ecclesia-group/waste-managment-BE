<?php

namespace App\Traits;

use App\Jobs\SendEmailJob;
use App\Jobs\SendSmsJob;
use App\Models\Actor;
use App\Models\OtpToken;

trait AppNotifications
{
    use ApiTransformer, Helpers;

    public static function sendSms(string $phone_number, string $msg, string $from, string $context = 'general'): void
    {
        SendSmsJob::dispatch($phone_number, $msg, $from, $context);
    }

    protected static function verifyOtp(string $guard, int $otp, int $actor_id): string
    {
        $token = OtpToken::where('actor_id', $actor_id)
            ->where('guard', $guard)
            ->where('token', $otp)
            ->latest()
            ->first();

        if ($token) {
            $expires_at = strtotime($token->expires_at);
            if ($expires_at < time()) {
                return self::API_FAIL;
            }

            return $token->channel;
        }

        return self::API_NOT_FOUND;
    }

    public static function sendEmail(
        string $email,
        array $parameters,
        string $email_class,
        ?string $login_url = null,
        string $context = 'general',
    ): void {
        SendEmailJob::dispatch($email, $email_class, $parameters, $context);
    }

    public static function sendActorResetPasswordNotification(?Actor $actor = null, string $guard)
    {
        if ($actor) {
            if (in_array($guard, ['admin', 'provider', 'facility', 'client', 'district_assembly'])) {
                $otp = self::otpCode(
                    channel: 'email',
                    type: 'password_reset',
                    actor_id: $actor->id,
                    guard: $guard
                );

                self::sendEmail(
                    email: $actor->email,
                    email_class: 'App\Mail\PasswordResetEmail',
                    parameters: [
                        $otp,
                        $actor->email,
                    ],
                    context: 'password_reset',
                );

                return self::apiResponse(in_error: false, message: 'Action Successful', reason: 'Otp sent to email successfully', status_code: self::API_SUCCESS, data: $actor->toArray());
            }
        }

        return self::apiResponse(in_error: true, message: 'Action Unsuccessful', reason: 'User cannot be found', status_code: self::API_NOT_FOUND, data: []);
    }

    protected static function verifyActorAccount(string $otp, ?Actor $actor = null, string $guard)
    {
        if ($actor) {
            $channel = self::verifyOtp(guard: $guard, otp: $otp, actor_id: $actor->id);

            if ($channel == 'phone') {
                $actor->phone_number_verified_at = now();
                $actor->save();
                $actor = self::apiToken($actor, $guard);

                return self::apiResponse(in_error: false, message: 'Action Successful', reason: 'Phone number verified successfully', status_code: self::API_SUCCESS, data: $actor->toArray());
            }

            if ($channel == 'email') {
                $actor->email_verified_at = now();
                $actor->save();
                $actor = self::apiToken($actor, $guard);

                return self::apiResponse(in_error: false, message: 'Action Successful', reason: 'Email Verified successfully', status_code: self::API_SUCCESS, data: $actor->toArray());
            }

            if ($channel == self::API_FAIL) {
                return self::apiResponse(in_error: true, message: 'Action Unsuccessful', reason: 'Otp expired', status_code: self::API_FAIL, data: []);
            }

            return self::apiResponse(in_error: true, message: 'Action Unsuccessful', reason: 'Otp not found', status_code: self::API_NOT_FOUND, data: []);
        }

        return self::apiResponse(in_error: true, message: 'Action Unsuccessful', reason: 'User account cannot be found', status_code: self::API_NOT_FOUND, data: []);
    }

    protected static function verifyAffiliateAccount(string $otp, ?Actor $actor = null, string $guard)
    {
        if ($actor) {
            $channel = self::verifyOtp(guard: $guard, otp: $otp, actor_id: $actor->id);

            if ($channel == 'phone') {
                $actor->phone_number_verified_at = now();
                $actor->save();

                $actor = self::apiToken($actor, $guard);

                return self::apiResponse(in_error: false, message: 'Action Successful', reason: 'Phone number verified successfully', status_code: self::API_SUCCESS, data: $actor->toArray());
            }

            if ($channel == 'email') {
                $actor->email_verified_at = now();
                $actor->save();

                $actor = self::apiToken($actor, $guard);

                return self::apiResponse(in_error: false, message: 'Action Successful', reason: 'Email Verified successfully', status_code: self::API_SUCCESS, data: $actor->toArray());
            }

            if ($channel == self::API_FAIL) {
                return self::apiResponse(in_error: true, message: 'Action Unsuccessful', reason: 'Otp expired', status_code: self::API_FAIL, data: []);
            }

            return self::apiResponse(in_error: true, message: 'Action Unsuccessful', reason: 'Otp not found', status_code: self::API_NOT_FOUND, data: []);
        }

        return self::apiResponse(in_error: true, message: 'Action Unsuccessful', reason: 'User account cannot be found', status_code: self::API_NOT_FOUND, data: []);
    }

    protected static function resetActorPassword(string $otp, ?Actor $actor = null, string $guard, string $password)
    {
        if ($actor) {
            $status = self::verifyOtp(guard: $guard, otp: $otp, actor_id: $actor->id);

            if ($status == self::API_NOT_FOUND) {
                return self::apiResponse(in_error: true, message: 'Action Unsuccessful', reason: 'Otp not found', status_code: self::API_NOT_FOUND, data: []);
            }

            if ($status == self::API_FAIL) {
                return self::apiResponse(in_error: true, message: 'Action Unsuccessful', reason: 'Otp expired', status_code: self::API_FAIL, data: []);
            }

            $actor->password = $password;
            $actor->save();
            // revoke all tokens for the actor
            $actor->tokens()->where('name', $guard)->delete();

            return self::apiResponse(in_error: false, message: 'Action Successful', reason: 'Password reset succcessfully', status_code: self::API_SUCCESS, data: []);
        }

        return self::apiResponse(in_error: true, message: 'Action Unsuccessful', reason: 'User account cannot be found', status_code: self::API_NOT_FOUND, data: []);
    }
}
