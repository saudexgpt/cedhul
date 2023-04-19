<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use SoftDeletes;

    public function registerMediaConversions(Media $media = null): void
    {
        // $this
        //     ->addMediaConversion('preview')
        //     ->fit(Manipulations::FIT_CROP, 300, 300)
        //     ->nonQueued();

        // $this->addMediaConversion('preview')
        //     ->width(368)
        //     ->height(232)
        //     ->border(2, 'black')
        //     ->sharpen(10);

        // $this->addMediaConversion('old-picture')
        //     ->sepia()
        //     ->border(10, 'black', Manipulations::BORDER_OVERLAY);

        // $this->addMediaConversion('thumb-cropped')
        //     ->crop('crop-center', 400, 400);
    }

    /**
     * Get the approver that owns the Article
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by', 'id');
    }
}
