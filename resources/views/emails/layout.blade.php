<!doctype html>
<html lang="en" style="margin:0;padding:0">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light dark">
<meta name="supported-color-schemes" content="light dark">
<title>Cloud Technology</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:Arial,Helvetica,sans-serif;color:#1f2937">
<div style="display:none;font-size:1px;color:#f4f6f9;max-height:0;overflow:hidden">@yield('preheader')</div>
<table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation" style="background:#f4f6f9"><tr><td align="center" style="padding:28px 12px">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation" style="max-width:560px">
        {{-- Brand band: logo + wordmark --}}
        <tr><td align="center" style="padding:6px 0 18px">
            <img src="{{ url('image/logo.png') }}" width="52" height="52" alt="Cloud Technology" style="display:block;margin:0 auto 8px;border:0;outline:none;text-decoration:none;border-radius:10px;box-shadow:0 2px 10px rgba(17,40,80,.15)">
            <div style="font-size:19px;font-weight:bold;color:#12284f;letter-spacing:.2px">Cloud<span style="color:#2563eb"> Technology</span></div>
            <div style="font-size:11px;color:#7a8ba3;text-transform:uppercase;letter-spacing:2px;margin-top:3px">Domains · Hosting · Cloud</div>
        </td></tr>
        {{-- Card --}}
        <tr><td>
            <table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation" style="background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden">
                <tr><td style="height:5px;background:linear-gradient(90deg,#2563eb,#22c55e);font-size:0;line-height:0">&nbsp;</td></tr>
                <tr><td style="padding:26px 30px 24px;font-size:14px;line-height:1.65">@yield('body')</td></tr>
                <tr><td style="padding:15px 30px;border-top:1px solid #eef0f3;background:#f9fafc;font-size:12px;color:#6b7280;line-height:1.7">
                    Need help? Email <a href="mailto:{{ \App\Models\Setting::get('support_email') ?: 'support@cloudtechnologybd.net' }}" style="color:#2563eb;text-decoration:none">{{ \App\Models\Setting::get('support_email') ?: 'support@cloudtechnologybd.net' }}</a>@if(\App\Models\Setting::get('support_phone')) or call <b style="color:#374151">{{ \App\Models\Setting::get('support_phone') }}</b>@endif<br>
                    This is an automated message — replying directly to it will not reach support.
                </td></tr>
            </table>
        </td></tr>
        {{-- Footer --}}
        <tr><td align="center" style="padding:16px 12px 6px;font-size:11.5px;color:#8a97a8;line-height:1.7">
            © {{ date('Y') }} {{ \App\Models\Setting::get('footer_text') ?: 'Cloud Technology BD' }} — All rights reserved.<br>
            Dhaka, Bangladesh · cloudtechnologybd.net
        </td></tr>
    </table>
</td></tr></table>
</body>
</html>
