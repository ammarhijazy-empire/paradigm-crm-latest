@php $dir = ($locale ?? app()->getLocale()) === 'ar' ? 'rtl' : 'ltr'; $co = App\Support\Settings::get('company'); @endphp
<!doctype html><html lang="{{ $locale ?? app()->getLocale() }}" dir="{{ $dir }}"><head><meta charset="utf-8"><title>{{ $co['name'] }}</title></head>
<body style="margin:0;background:#f2f5f5;font-family:Tahoma,Arial,sans-serif;color:#16262b">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f2f5f5;padding:24px 12px"><tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;background:#ffffff;border:1px solid #dbe3e4;border-radius:8px">
<tr><td style="background:#12303a;color:#fff;padding:14px 20px;font-weight:bold;border-radius:8px 8px 0 0;text-align:{{ $dir === 'rtl' ? 'right' : 'left' }}">{{ $co['name'] }}</td></tr>
<tr><td style="padding:20px;font-size:14px;line-height:1.6;text-align:{{ $dir === 'rtl' ? 'right' : 'left' }}" dir="{{ $dir }}">@yield('body')</td></tr>
<tr><td style="padding:12px 20px;border-top:1px solid #dbe3e4;font-size:12px;color:#5d7076;text-align:{{ $dir === 'rtl' ? 'right' : 'left' }}">{{ $co['address'] }} · {{ $co['phone'] }} · {{ $co['email'] }}</td></tr>
</table></td></tr></table></body></html>
