<!doctype html>
<html><body style="margin:0;background:#f4f6f9;font-family:Arial,Helvetica,sans-serif;color:#1f2937">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:28px 12px"><tr><td align="center">
<table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border:1px solid #e5e7eb;border-radius:10px">
<tr><td style="padding:22px 28px;border-bottom:1px solid #eef0f3;font-size:20px;font-weight:bold;color:#111827">Cloud<span style="color:#2563eb">Tech</span></td></tr>
<tr><td style="padding:26px 28px;font-size:14px;line-height:1.6">@yield('body')</td></tr>
<tr><td style="padding:16px 28px;border-top:1px solid #eef0f3;font-size:12px;color:#6b7280">Need help? Call {{ \App\Models\Setting::get('support_phone') }} or reply to this email.<br>&copy; {{ date('Y') }} {{ \App\Models\Setting::get('footer_text') }}</td></tr>
</table></td></tr></table>
</body></html>
