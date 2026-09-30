@extends('emails.layout')
@section('body')
<p style="margin:0 0 14px">Hi {{ $user->first_name ?: $user->name }},</p>
<p style="margin:0 0 20px">Thanks for creating a CloudTech account. Please confirm your email address to activate it.</p>
<p style="margin:0 0 22px"><a href="{{ $url }}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-weight:bold;padding:12px 22px;border-radius:6px">Verify email address</a></p>
<p style="margin:0 0 6px;color:#6b7280;font-size:12px">This link expires in 48 hours. If the button does not work, copy this address into your browser:</p>
<p style="margin:0;word-break:break-all;font-size:12px"><a href="{{ $url }}" style="color:#2563eb">{{ $url }}</a></p>
<p style="margin:20px 0 0;color:#6b7280;font-size:12px">If you did not create an account, you can ignore this email.</p>
@endsection
