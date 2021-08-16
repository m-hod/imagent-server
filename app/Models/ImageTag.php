<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImageTag extends Model
{
    use HasFactory;

    protected $table = 'image_tag';

    protected $fillable = ['image_id', 'tag_id'];

    public function image()
    {
        return $this->belongsTo(Image::class);
    }

    public function tag()
    {
        return $this->belongsTo(Tag::class);
    }
}
