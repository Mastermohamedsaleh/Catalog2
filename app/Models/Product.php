<?php

namespace App\Models;

use App\Models\Category;
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
        'is_active',
    ];

    /** Signed player URL when the stored value is a Bunny video id; null for legacy local files / no video. */
    public function getVideoEmbedUrlAttribute(): ?string
    {
        $bunny = app(\App\Services\BunnyStreamService::class);

        return $bunny->isVideoId($this->video_url) ? $bunny->embedUrl($this->video_url) : null;
    }

    public function getVideoDownloadUrlAttribute(): ?string
    {
        $bunny = app(\App\Services\BunnyStreamService::class);

        return $bunny->isVideoId($this->video_url) ? $bunny->downloadUrl($this->video_url) : null;
    }

    /** Videos uploaded before the move to Bunny are still plain files on the public disk. */
    public function getHasLegacyVideoAttribute(): bool
    {
        return filled($this->video_url) && ! app(\App\Services\BunnyStreamService::class)->isVideoId($this->video_url);
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