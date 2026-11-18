<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Product videos are stored at Bunny Stream. The app server only creates a video slot,
 * signs a short-lived upload ticket (the admin's browser uploads straight to Bunny),
 * stores the video GUID, and signs playback URLs for logged-in viewers.
 */
class BunnyStreamService
{
    private const API = 'https://video.bunnycdn.com';
    public const TUS_ENDPOINT = 'https://video.bunnycdn.com/tusupload';

    public function configured(): bool
    {
        return filled(config('services.bunny.library_id')) && filled(config('services.bunny.api_key'));
    }

    /** A Bunny video id is a GUID; anything else stored in products.video_url is a legacy local file path. */
    public function isVideoId(?string $value): bool
    {
        return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', (string) $value);
    }

    /** Creates the (empty) video object and returns its GUID. */
    public function createVideo(string $title): string
    {
        $lib = config('services.bunny.library_id');
        $response = Http::withHeaders(['AccessKey' => config('services.bunny.api_key'), 'Accept' => 'application/json'])
            ->timeout(15)
            ->post(self::API . "/library/{$lib}/videos", ['title' => Str::limit($title, 200, '')]);

        $guid = $response->json('guid');
        if (! $response->successful() || ! $this->isVideoId($guid)) {
            Log::error('Bunny createVideo failed', ['status' => $response->status(), 'body' => Str::limit($response->body(), 300)]);
            throw new RuntimeException('Bunny create video failed');
        }

        return $guid;
    }

    /** Pre-signed TUS ticket: lets the browser upload this one video without ever seeing the API key. */
    public function uploadTicket(string $guid): array
    {
        $lib = (string) config('services.bunny.library_id');
        $expire = time() + 6 * 3600;
        $signature = hash('sha256', $lib . config('services.bunny.api_key') . $expire . $guid);

        return [
            'endpoint' => self::TUS_ENDPOINT,
            'video_id' => $guid,
            'headers'  => [
                'AuthorizationSignature' => $signature,
                'AuthorizationExpire'    => (string) $expire,
                'VideoId'                => $guid,
                'LibraryId'              => $lib,
            ],
        ];
    }

    /** Player URL. Signed (expiring) when an embed token key is configured, so it only plays from our pages. */
    public function embedUrl(string $guid): string
    {
        $url = 'https://iframe.mediadelivery.net/embed/' . config('services.bunny.library_id') . '/' . $guid;

        if ($key = config('services.bunny.embed_token_key')) {
            $expires = time() + (int) config('services.bunny.token_ttl', 21600);
            $url .= '?token=' . hash('sha256', $key . $guid . $expires) . '&expires=' . $expires;
        }

        return $url;
    }

    /** Direct MP4 link for the marketers' "download" button (needs MP4 Fallback enabled + BUNNY_CDN_HOST). */
    public function downloadUrl(string $guid): ?string
    {
        $host = config('services.bunny.cdn_host');
        if (blank($host)) {
            return null;
        }

        $path = "/{$guid}/play_720p.mp4";
        $url = "https://{$host}{$path}";

        if ($key = config('services.bunny.cdn_token_key')) {
            $expires = time() + (int) config('services.bunny.token_ttl', 21600);
            $token = rtrim(strtr(base64_encode(hash('sha256', $key . $path . $expires, true)), '+/', '-_'), '=');
            $url .= "?token={$token}&expires={$expires}";
        }

        return $url;
    }

    /** Best effort: a failed delete must never block the admin; it is logged and the video can be removed in Bunny. */
    public function delete(string $guid): void
    {
        if (! $this->configured() || ! $this->isVideoId($guid)) {
            return;
        }

        try {
            $lib = config('services.bunny.library_id');
            Http::withHeaders(['AccessKey' => config('services.bunny.api_key')])
                ->timeout(15)
                ->delete(self::API . "/library/{$lib}/videos/{$guid}");
        } catch (\Throwable $e) {
            Log::warning('Bunny delete failed', ['video' => $guid, 'message' => $e->getMessage()]);
        }
    }
}
