<?php

namespace App\Models;

use App\Models\Category;
use App\Support\VideoLinks;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'sku',         // <-- إضافة الـ sku هنا
        'slug',
        'description',
        'price',
        'stock',
        'main_image',
        'gallery_images',
        'downloadable_file',
        'video_url',
        'video_download_url',
        'is_active',
    ];

    /**
     * لينك مشغل يوتيوب (embed) من اللينك المخزّن في video_url.
     * بيرجع null لو القيمة مش لينك يوتيوب (مفيش فيديو، أو ملف قديم على السيرفر).
     */
    public function getVideoEmbedUrlAttribute(): ?string
    {
        return VideoLinks::embedUrl($this->video_url);
    }

    /** لينك فتح الفيديو على يوتيوب نفسه (زرار "Watch on YouTube"). */
    public function getVideoWatchUrlAttribute(): ?string
    {
        return VideoLinks::normalizeYoutube($this->video_url);
    }

    /** لينك التنزيل الجاهز للزرار (لينك Drive بيتحول لتنزيل مباشر). null لو مفيش. */
    public function getVideoDownloadLinkAttribute(): ?string
    {
        return VideoLinks::downloadLink($this->video_download_url);
    }

    /** فيديوهات قديمة اترفعت كملف على السيرفر (مسار زي products/videos/x.mp4). */
    public function getHasLegacyVideoAttribute(): bool
    {
        return filled($this->video_url)
            && ! VideoLinks::youtubeId($this->video_url)
            && str_contains($this->video_url, '/')
            && ! preg_match('~^https?://~i', $this->video_url);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }
}