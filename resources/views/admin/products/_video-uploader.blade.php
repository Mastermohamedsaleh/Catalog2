{{-- حقول الفيديو: لينك يوتيوب للمشاهدة + لينك تنزيل منفصل (اختياري). مفيش رفع ملفات على السيرفر. --}}
@php
    $uid = 'vu' . ($product->id ?? 'new');
    $hasVideo = !empty($product) && filled($product->video_url);
    $currentYoutube = !empty($product) ? $product->video_watch_url : null;
@endphp
<div class="video-fields">
    @if($hasVideo)
        <div class="d-flex align-items-center gap-2 mb-2 small">
            @if($currentYoutube)
                <span class="badge badge-soft-success"><i class="bi bi-youtube me-1"></i>YouTube</span>
            @else
                <span class="badge badge-soft-warning"><i class="bi bi-hdd me-1"></i>Old local file</span>
            @endif
            @unless($currentYoutube)
                {{-- الـ checkbox ده للملف القديم بس. لينك يوتيوب بيتشال بمسح الحقل تحت. --}}
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" name="remove_video" value="1" id="rm{{ $uid }}">
                    <label class="form-check-label text-danger" for="rm{{ $uid }}">Delete old file</label>
                </div>
            @else
                <span class="text-muted">لإزالة الفيديو امسح اللينك من الحقل وأحفظ.</span>
            @endunless
        </div>
    @endif

    <label class="form-label small text-muted mb-1" for="yt{{ $uid }}">YouTube link (Unlisted)</label>
    <input type="url" class="form-control @error('video_url') is-invalid @enderror" id="yt{{ $uid }}" name="video_url"
           value="{{ old('video_url', $currentYoutube) }}" placeholder="https://www.youtube.com/watch?v=xxxxxxxxxxx" maxlength="255">
    @error('video_url')<div class="text-danger small mt-1">{{ $message }}</div>@enderror

    <label class="form-label small text-muted mb-1 mt-2" for="dl{{ $uid }}">Download link (optional)</label>
    <input type="url" class="form-control @error('video_download_url') is-invalid @enderror" id="dl{{ $uid }}" name="video_download_url"
           value="{{ old('video_download_url', $product->video_download_url ?? '') }}" placeholder="https://drive.google.com/file/d/.../view" maxlength="500">
    @error('video_download_url')<div class="text-danger small mt-1">{{ $message }}</div>@enderror

    <div class="form-text small">
        ارفع الفيديو على يوتيوب كـ <b>Unlisted</b> وحط اللينك فوق. يوتيوب مفيهوش تنزيل رسمي، فعشان زرار التنزيل يشتغل
        ارفع نفس الفيديو على Google Drive (أو أي مكان) وحط لينكه تحت. لو سيبته فاضي هيظهر زرار "Watch on YouTube" بدل التنزيل.
    </div>
</div>
