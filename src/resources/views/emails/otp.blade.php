<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body style="font-family: sans-serif; background: #f9fafb; padding: 40px 0;">
    <div style="max-width: 480px; margin: 0 auto; background: #fff; border-radius: 16px; border: 1px solid #e5e7eb; padding: 40px;">

        <h2 style="font-size: 20px; font-weight: 700; color: #111827; margin: 0 0 8px;">Reset Password</h2>
        <p style="font-size: 14px; color: #6b7280; margin: 0 0 24px;">Halo {{ $name }}, berikut kode OTP untuk reset password akun Claro kamu:</p>

        <div style="background: #eff6ff; border-radius: 12px; padding: 24px; text-align: center; margin-bottom: 24px;">
            <p style="font-size: 36px; font-weight: 800; color: #2563eb; letter-spacing: 12px; margin: 0;">{{ $otp }}</p>
        </div>

        <p style="font-size: 13px; color: #6b7280; margin: 0 0 8px;">⏱ Kode berlaku selama <strong>5 menit</strong>.</p>
        <p style="font-size: 13px; color: #6b7280; margin: 0;">Jika kamu tidak merasa meminta reset password, abaikan email ini.</p>

        <hr style="border: none; border-top: 1px solid #f3f4f6; margin: 24px 0;">
        <p style="font-size: 12px; color: #9ca3af; margin: 0;">© {{ date('Y') }} Claro. All rights reserved.</p>
    </div>
</body>
</html>