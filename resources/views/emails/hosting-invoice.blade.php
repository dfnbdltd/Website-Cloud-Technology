@extends('emails.layout')
@section('body')
<p style="margin:0 0 14px">Hi {{ $order->customer_name }},</p>
<p style="margin:0 0 18px">Thank you for your purchase. We have received your payment for your hosting plan — your receipt is below.</p>
<table cellpadding="0" cellspacing="0" style="width:100%;margin:0 0 16px;font-size:13px">
<tr><td style="color:#6b7280">Order</td><td align="right" style="font-weight:bold">{{ $order->reference }}</td></tr>
@if($invoice)<tr><td style="color:#6b7280">Invoice</td><td align="right">{{ $invoice->invoice_number }}</td></tr>@endif
<tr><td style="color:#6b7280">Date</td><td align="right">{{ ($order->paid_at ?: $order->updated_at)->format('d M Y') }}</td></tr>
<tr><td style="color:#6b7280">Payment</td><td align="right">{{ strtoupper($order->payment_method) }}@if($order->provider_payment_id) · {{ $order->provider_payment_id }}@endif</td></tr>
<tr><td style="color:#6b7280">Status</td><td align="right" style="color:#16a34a;font-weight:bold">PAID</td></tr>
</table>
<table cellpadding="0" cellspacing="0" style="width:100%;border:1px solid #e5e7eb;border-radius:8px;font-size:13px">
<tr style="background:#f9fafb"><td style="padding:10px 14px;font-weight:bold">Item</td><td style="padding:10px 14px;font-weight:bold" align="right">Amount</td></tr>
<tr><td style="padding:10px 14px;border-top:1px solid #eef0f3">{{ $order->product }} — {{ $order->planLabel() }}<br><span style="color:#6b7280;font-size:12px">{{ $order->domain }} · {{ $order->period_label }}</span></td><td style="padding:10px 14px;border-top:1px solid #eef0f3" align="right">৳ {{ number_format($order->total, 2) }}</td></tr>
<tr><td style="padding:12px 14px;border-top:1px solid #e5e7eb;font-weight:bold">Total paid</td><td style="padding:12px 14px;border-top:1px solid #e5e7eb;font-weight:bold" align="right">৳ {{ number_format($order->total, 2) }}</td></tr>
</table>
<p style="margin:20px 0 0"><a href="{{ route('invoices') }}" style="color:#2563eb">View your invoices in the client area →</a></p>
@endsection
