{{-- Direct-to-Bunny uploader. The file never passes through this server; only the video id is submitted. --}}
@php
    $bunnyReady = app(\App\Services\BunnyStreamService::class)->configured();
    $uid = 'vu' . ($product->id ?? 'new');
@endphp
<div class="video-uploader" data-prepare-url="{{ route('admin.videos.prepare') }}">
    @if(!empty($product) && $product->video_url)
        <div class="d-flex align-items-center gap-2 mb-2 small">
            @if($product->video_embed_url)
                <span class="badge badge-soft-success"><i class="bi bi-cloud-check me-1"></i>Hosted on Bunny</span>
            @else
                <span class="badge badge-soft-warning"><i class="bi bi-hdd me-1"></i>Old local file</span>
            @endif
            <div class="form-check mb-0">
                <input class="form-check-input" type="checkbox" name="remove_video" value="1" id="rm{{ $uid }}">
                <label class="form-check-label text-danger" for="rm{{ $uid }}">Remove current video</label>
            </div>
        </div>
    @endif

    <input type="file" class="form-control video-file" accept="video/mp4,video/quicktime,video/*" @disabled(! $bunnyReady)>
    <input type="hidden" name="video_id" class="video-id" value="{{ old('video_id') }}">
    @error('video_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror

    <div class="progress mt-2 d-none" style="height:8px"><div class="progress-bar" role="progressbar" style="width:0%"></div></div>
    <div class="form-text small video-status">
        @if(! $bunnyReady)
            <span class="text-danger">Video hosting is not configured (set BUNNY_LIBRARY_ID and BUNNY_API_KEY in .env).</span>
        @elseif(old('video_id'))
            <span class="text-success">A video was already uploaded — it will be attached when you save.</span>
        @else
            MP4 / MOV. The upload goes straight to the video service and can resume if the connection drops. Keep this window open until it says "uploaded".
        @endif
    </div>
</div>
