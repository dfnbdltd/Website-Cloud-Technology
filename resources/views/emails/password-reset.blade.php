@extends('emails.layout')
@section('body')
<p style="margin:0 0 14px">Hello,</p>
<p style="margin:0 0 18px">We received a request to reset the password for your CloudTech account. Use the secure link below to choose a new password.</p>
<p style="margin:0 0 22px"><a href="{{ $resetUrl }}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-weight:bold;padding:12px 22px;border-radius:6px">Reset your password</a></p>
<p style="margin:0 0 12px;color:#6b7280;font-size:12px">This link expires in {{ $expiresIn }} minutes and can only be used once.</p>
<p style="margin:0 0 12px;color:#6b7280;font-size:12px">If the button doesn't work, copy and paste this link into your browser:</p>
<p style="margin:0 0 18px;font-size:12px;word-break:break-all"><a href="{{ $resetUrl }}" style="color:#2563eb">{{ $resetUrl }}</a></p>
<p style="margin:0;color:#6b7280;font-size:12px">If you didn't request a password reset, you can ignore this email. Your password will remain unchanged.</p>
@endsection
