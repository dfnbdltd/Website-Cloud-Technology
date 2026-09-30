@extends('emails.layout')
@section('body')
<p style="margin:0 0 14px">Hi {{ $user->first_name ?: $user->name }},</p>
<p style="margin:0 0 18px">Your email is verified and your CloudTech account is active. Here are your login details:</p>
<table cellpadding="0" cellspacing="0" style="width:100%;border:1px solid #e5e7eb;border-radius:8px;margin:0 0 20px">
<tr><td style="padding:10px 14px;color:#6b7280;width:130px">Login email</td><td style="padding:10px 14px;font-weight:bold">{{ $user->email }}</td></tr>
<tr><td style="padding:10px 14px;color:#6b7280;border-top:1px solid #eef0f3">Customer ID</td><td style="padding:10px 14px;font-weight:bold;border-top:1px solid #eef0f3">{{ $user->customer_uid }}</td></tr>
<tr><td style="padding:10px 14px;color:#6b7280;border-top:1px solid #eef0f3">Password</td><td style="padding:10px 14px;border-top:1px solid #eef0f3">The password you chose when you signed up</td></tr>
</table>
<p style="margin:0 0 22px"><a href="{{ route('login') }}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-weight:bold;padding:12px 22px;border-radius:6px">Sign in to your account</a></p>
<p style="margin:0;color:#6b7280;font-size:12px">For your security we never send passwords by email. Keep your Customer ID handy when contacting support.</p>
@endsection
