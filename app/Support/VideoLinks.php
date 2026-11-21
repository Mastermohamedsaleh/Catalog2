<?php

namespace App\Support;

/**
 * التعامل مع لينكات الفيديو بدل Bunny:
 *  - لينك المشاهدة: يوتيوب (Unlisted) بنحوله لـ embed.
 *  - لينك التنزيل: لينك تاني منفصل (Google Drive مثلاً) لأن يوتيوب مفيهوش تنزيل رسمي.
 *
 * كل حاجة هنا بتتحقق بـ regex صارم، عشان مفيش لينك غريب يتحط جوه iframe أو href.
 */
class VideoLinks
{
    /** يطلع الـ ID (11 حرف) من أي شكل لينك يوتيوب: watch / youtu.be / shorts / embed / live. */
    public static function youtubeId(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        $pattern = '~^(?:https?://)?(?:www\.|m\.)?'
            . '(?:youtube(?:-nocookie)?\.com/(?:watch\?(?:[^#]*&)?v=|embed/|shorts/|live/|v/)|youtu\.be/)'
            . '([A-Za-z0-9_-]{11})(?![A-Za-z0-9_-])~i';

        return preg_match($pattern, $url, $m) ? $m[1] : null;
    }

    /** الشكل الموحد اللي بنخزنه في الداتابيز. null لو اللينك مش يوتيوب. */
    public static function normalizeYoutube(?string $url): ?string
    {
        $id = self::youtubeId($url);

        return $id ? 'https://www.youtube.com/watch?v=' . $id : null;
    }

    /** لينك المشغل. youtube-nocookie عشان ميزرعش كوكيز تتبع، و rel=0 عشان ميعرضش فيديوهات غريبة بعد الانتهاء. */
    public static function embedUrl(?string $url): ?string
    {
        $id = self::youtubeId($url);

        return $id ? 'https://www.youtube-nocookie.com/embed/' . $id . '?rel=0&modestbranding=1' : null;
    }

    /**
     * لينك التنزيل. لو لينك Google Drive عادي (…/file/d/ID/view) بنحوله لتنزيل مباشر،
     * وأي لينك http/https تاني بيتسيب زي ما هو (Dropbox, MEGA, سيرفرك...). غير كده null.
     */
    public static function downloadLink(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '' || ! preg_match('~^https?://~i', $url)) {
            return null;
        }

        if (preg_match('~^https?://drive\.google\.com/file/d/([A-Za-z0-9_-]+)~i', $url, $m)
            || preg_match('~^https?://drive\.google\.com/(?:open|uc)\?(?:[^#]*&)?id=([A-Za-z0-9_-]+)~i', $url, $m)) {
            return 'https://drive.google.com/uc?export=download&id=' . $m[1];
        }

        return $url;
    }
}
