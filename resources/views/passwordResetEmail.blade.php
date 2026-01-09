<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Password Reset</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>

<body style="margin: 0; padding: 0; background-color: #e6e6e6; font-family: 'Poppins', Arial, sans-serif;">
    <!-- Main Container -->
    <table width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#e6e6e6">
        <tr>
            <td align="center" style="padding: 40px 10px;">
                <!-- Email Container -->
                <table width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#ffffff"
                    style="max-width: 600px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);">
                    <!-- Header -->
                    <tr>
                        <td align="center" bgcolor="#00361B" style="padding: 30px 20px;">
                            <img src="https://wastebackend.tripsecuregh.com/images/logo.png" alt="Logo"
                                style="max-width: 140px; margin-bottom: 15px;">
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 30px 25px; background-color: #fdfdfd;">
                            <!-- First Content Box -->
                            <table width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#ffffff"
                                style="border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); margin-bottom: 25px;">
                                <tr>
                                    <td style="padding: 20px 25px;">
                                        <p style="font-size: 16px; color: #333333; margin-bottom: 12px;">
                                            Hello
                                            <span
                                                style="display: inline-block; background-color: #f4fff0; padding: 6px 10px; border-radius: 6px; color: #62a93b; font-weight: 600; font-size: 16px; border: 1px solid #bcffb3;">
                                                {{ $email }}
                                            </span>,
                                        </p>
                                        <p style="font-size: 15px; color: #555; margin: 0;">
                                            Please reset your password by using the OTP
                                            code below.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- OTP Box -->
                            <table width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#00361B"
                                style="border: 1px dashed #00361B; border-radius: 12px; margin-bottom: 20px; box-shadow:0 4px 12px rgba(0,0,0,0.10);">
                                <tr>
                                    <td align="center" valign="middle" style="padding: 30px;">
                                        <!-- Center Fix: Nested table with a single centered cell -->
                                        <table cellspacing="0" cellpadding="0" border="0" align="center">
                                            <tr>
                                                <td align="center">
                                                    <span
                                                        style="font-size: 32px; letter-spacing: 6px; font-weight: 700; color: #ffffff; text-align: center; display: block;">
                                                        {{ $otp }}
                                                    </span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Expiration Notice -->
                            <table width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td align="center" style="padding-bottom: 30px; justify-content: center;">
                                        <p
                                            style="font-size: 13px; color: #FF4C4C; text-align: center; font-weight: 500; margin: 0;">
                                            ⚠️ This code will expire after 10 minutes
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Disclaimer -->
                            <table width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td align="center">
                                        <p style="font-size: 14px; color: #888; text-align: center; margin: 0;">
                                            If you did not request this service, please ignore this email or
                                            contact support.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
