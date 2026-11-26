{{-- أيقونات التواصل. اللينكات من config/shop.php (تتغير من الـ .env). تليجرام بنفس رقم الواتساب. --}}
@php
    $wa = config('shop.whatsapp');
    $links = array_filter([
        ['Instagram', 'bi-instagram', config('shop.instagram')],
        ['WhatsApp',  'bi-whatsapp',  $wa ? 'https://wa.me/' . $wa : null],
        ['TikTok',    'bi-tiktok',    config('shop.tiktok')],
        ['Facebook',  'bi-facebook',  config('shop.facebook')],
        ['Telegram',  'bi-telegram',  $wa ? 'https://t.me/+' . $wa : null],
    ], fn ($l) => filled($l[2]));
@endphp
<div class="d-flex gap-3 fs-5">
    @foreach($links as [$label, $icon, $url])
        <a class="text-reset" href="{{ $url }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $label }}" title="{{ $label }}"><i class="bi {{ $icon }}"></i></a>
    @endforeach
</div>
