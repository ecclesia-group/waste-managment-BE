<?php
namespace App\Traits;

use App\Models\Actor;
use App\Models\OtpToken;
use Illuminate\Support\Facades\Mail;

trait AppNotifications
{
    use Helpers;

    // public static function sendSms(string $phone_number, string $msg, string $from): bool
    // {
    //     $endpoint = "https://txtconnect.net/dev/api/sms/send";

    //     $payload = [
    //         "to"      => $phone_number,
    //         "from"    => $from,
    //         "unicode" => 1,
    //         "sms"     => $msg,
    //     ];

    //     try {
    //         $response = Http::withHeaders([
    //             // "Authorization" => "Bearer OwdSDtqAZhvPIYjl7c2n5KVu6kiB8TxF3p1mUbo9sXRGMfHQJ4",
    //             "Authorization" => "Bearer d0VBeZQ98GFwOEuvpaotyTniLWj7SxcJHMgfIr4ND5Am2RbC3X",
    //             'Accept'        => 'application/json',
    //             "Content-Type"  => "application/json",
    //         ])->post($endpoint, $payload);

    //         // Log::channel("sent_sms")->info("SMS API Response", [
    //         //     'phone_number' => $phone_number,
    //         //     'response'     => json_encode($response->json()),
    //         // ]);

    //         return $response->successful();
    //     } catch (\Exception $e) {
    //         Log::channel("sent_sms")->error("SMS API Exception", [
    //             'phone_number' => $phone_number,
    //             'error'        => $e->getMessage(),
    //             'trace'        => $e->getTraceAsString(),
    //         ]);
    //         return false;
    //     }
    // }

    // protected static function sendOtp(Actor $actor, string $type, string $msg, string $channel, string $guard): void
    // {
    //     $otp = self::otpCode(type: $type, actor_id: $actor->id, channel: $channel, guard: $guard);
    //     $msg = $msg . " " . $otp;
    //     self::sendSms(phone_number: $actor->phone_number, msg: $msg, from: "DEALBOXX");
    // }

    protected static function verifyOtp(string $guard, int $otp, int $actor_id): string
    {
        $token = OtpToken::where("actor_id", $actor_id)
            ->where("guard", $guard)
            ->where("token", $otp)
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

    protected static function sendEmail(string $email, array $parameters, string $email_class, string $login_url = null): void
    {
        Mail::to($email)->send(new $email_class(...$parameters));
    }

    public static function sendActorResetPasswordNotification(?Actor $actor = null, string $guard)
    {
        if ($actor) {
            if (in_array($guard, ["admin", "provider", "facility", "client", "district_assembly"])) {
                $otp = self::otpCode(
                    channel: "email",
                    type: "password_reset",
                    actor_id: $actor->id,
                    guard: $guard
                );

                self::sendEmail(
                    email: $actor->email,
                    email_class: "App\Mail\PasswordResetEmail",
                    parameters: [
                        $otp,
                        $actor->email,
                    ],
                );

                return self::apiResponse(in_error: false, message: "Action Successful", reason: "Otp sent to email successfully", status_code: self::API_SUCCESS, data: $actor->toArray());
            }
        }

        return self::apiResponse(in_error: true, message: "Action Unsuccessful", reason: "User cannot be found", status_code: self::API_NOT_FOUND, data: []);
    }

    protected static function verifyActorAccount(string $otp, ?Actor $actor = null, string $guard)
    {
        if ($actor) {
            $channel = self::verifyOtp(guard: $guard, otp: $otp, actor_id: $actor->id);

            if ($channel == "phone") {
                $actor->phone_number_verified_at = now();
                $actor->save();
                $actor = self::apiToken($actor, $guard);
                return self::apiResponse(in_error: false, message: "Action Successful", reason: "Phone number verified successfully", status_code: self::API_SUCCESS, data: $actor->toArray());
            }

            if ($channel == "email") {
                $actor->email_verified_at = now();
                $actor->save();
                $actor = self::apiToken($actor, $guard);
                return self::apiResponse(in_error: false, message: "Action Successful", reason: "Email Verified successfully", status_code: self::API_SUCCESS, data: $actor->toArray());
            }

            if ($channel == self::API_FAIL) {
                return self::apiResponse(in_error: true, message: "Action Unsuccessful", reason: "Otp expired", status_code: self::API_FAIL, data: []);
            }

            return self::apiResponse(in_error: true, message: "Action Unsuccessful", reason: "Otp not found", status_code: self::API_NOT_FOUND, data: []);
        }

        return self::apiResponse(in_error: true, message: "Action Unsuccessful", reason: "User account cannot be found", status_code: self::API_NOT_FOUND, data: []);
    }

    protected static function verifyAffiliateAccount(string $otp, ?Actor $actor = null, string $guard)
    {
        if ($actor) {
            $channel = self::verifyOtp(guard: $guard, otp: $otp, actor_id: $actor->id);

            if ($channel == "phone") {
                $actor->phone_number_verified_at = now();
                $actor->save();

                $actor = self::apiToken($actor, $guard);

                return self::apiResponse(in_error: false, message: "Action Successful", reason: "Phone number verified successfully", status_code: self::API_SUCCESS, data: $actor->toArray());
            }

            if ($channel == "email") {
                $actor->email_verified_at = now();
                $actor->save();

                $actor = self::apiToken($actor, $guard);

                return self::apiResponse(in_error: false, message: "Action Successful", reason: "Email Verified successfully", status_code: self::API_SUCCESS, data: $actor->toArray());
            }

            if ($channel == self::API_FAIL) {
                return self::apiResponse(in_error: true, message: "Action Unsuccessful", reason: "Otp expired", status_code: self::API_FAIL, data: []);
            }

            return self::apiResponse(in_error: true, message: "Action Unsuccessful", reason: "Otp not found", status_code: self::API_NOT_FOUND, data: []);
        }

        return self::apiResponse(in_error: true, message: "Action Unsuccessful", reason: "User account cannot be found", status_code: self::API_NOT_FOUND, data: []);
    }

    protected static function resetActorPassword(string $otp, ?Actor $actor = null, string $guard, string $password)
    {
        if ($actor) {
            $status = self::verifyOtp(guard: $guard, otp: $otp, actor_id: $actor->id);

            if ($status == self::API_NOT_FOUND) {
                return self::apiResponse(in_error: true, message: "Action Unsuccessful", reason: "Otp not found", status_code: self::API_NOT_FOUND, data: []);
            }

            if ($status == self::API_FAIL) {
                return self::apiResponse(in_error: true, message: "Action Unsuccessful", reason: "Otp expired", status_code: self::API_FAIL, data: []);
            }

            $actor->password = $password;
            $actor->save();

            return self::apiResponse(in_error: false, message: "Action Successful", reason: "Password reset succcessfully", status_code: self::API_SUCCESS, data: []);
        }

        return self::apiResponse(in_error: true, message: "Action Unsuccessful", reason: "User account cannot be found", status_code: self::API_NOT_FOUND, data: []);
    }
}
