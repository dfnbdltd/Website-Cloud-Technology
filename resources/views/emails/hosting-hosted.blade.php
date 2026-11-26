@extends('emails.layout')
@section('body')
<p style="margin:0 0 14px">Hi {{ $order->customer_name }},</p>
<p style="margin:0 0 18px">Good news — your domain <strong>{{ $order->domain }}</strong> is now hosted.</p>
<table cellpadding="0" cellspacing="0" style="width:100%;margin:0 0 16px;font-size:13px">
<tr><td style="color:#6b7280">Domain</td><td align="right" style="font-weight:bold">{{ $order->domain }}</td></tr>
<tr><td style="color:#6b7280">Plan</td><td align="right">{{ $order->product }} — {{ $order->planLabel() }}</td></tr>
<tr><td style="color:#6b7280">Billing</td><td align="right">{{ $order->period_label }}</td></tr>
<tr><td style="color:#6b7280">Order</td><td align="right">{{ $order->reference }}</td></tr>
<tr><td style="color:#6b7280">Hosted on</td><td align="right">{{ ($order->hosted_at ?: $order->updated_at)->format('d M Y') }}</td></tr>
<tr><td style="color:#6b7280">Status</td><td align="right" style="color:#16a34a;font-weight:bold">HOSTED</td></tr>
</table>
@if(trim((string) $order->admin_note) !== '')
<table cellpadding="0" cellspacing="0" style="width:100%;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;margin:0 0 16px">
<tr style="background:#f9fafb"><td style="padding:10px 14px;font-weight:bold">Note from our team</td></tr>
<tr><td style="padding:12px 14px;border-top:1px solid #eef0f3">{!! nl2br(e($order->admin_note)) !!}</td></tr>
</table>
@endif
<p style="margin:20px 0 0"><a href="{{ route('mystore.hosting') }}" style="color:#2563eb">View your hosting in the client area →</a></p>
@endsection
