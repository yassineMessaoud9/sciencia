<?php

namespace App\Models;

use App\Enums\Status;
use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProductBrand extends Model implements HasMedia
{
    use InteractsWithMedia;
    protected $table = "product_brands";
    protected $fillable = ['name', 'slug', 'description', 'status'];
    protected $casts = [
        'id'          => 'integer',
        'name'        => 'string',
        'slug'        => 'string',
        'description' => 'string',
        'status'      => 'integer',
    ];

    public function getThumbAttribute(): string
    {
        if (!empty($this->getFirstMediaUrl('product-brand'))) {
            $brand = $this->getMedia('product-brand')->last();
            return $brand->getUrl('thumb');
        }
        return asset('images/default/brand/thumb.png');
    }

    public function getCoverAttribute(): string
    {
        if (!empty($this->getFirstMediaUrl('product-brand'))) {
            $brand = $this->getMedia('product-brand')->last();
            return $brand->getUrl('cover');
        }
        return asset('images/default/brand/cover.png');
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')->crop('crop-center', 72, 72)->keepOriginalImageFormat()->sharpen(10);
        $this->addMediaConversion('cover')->width(300)->keepOriginalImageFormat()->sharpen(10);
    }

    public function products(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Product::class)->where(['status' => Status::ACTIVE]);
    }
}
