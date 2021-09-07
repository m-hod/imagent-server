<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Image extends Model
{
    use HasFactory;

    protected $table = 'images';

    protected $fillable = ['hash', 'ext'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'image_user', 'image_id', 'user_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'image_tag', 'image_id', 'tag_id');
    }

    public function imageTags()
    {
        return $this->hasMany(ImageTag::class);
    }

    public function imageUserTags()
    {
        return $this->hasMany(ImageUserTag::class);
    }

    // this is a recommended way to declare event handlers
    public static function boot() {
        parent::boot();

        static::deleting(function($image) {
             $image->imageTags()->delete();
             $image->imageUserTags()->delete();
        });
    }

    public function getUserTags()
    {
        $user = Auth::user();

        $imageUserTags = ImageUserTag::where('image_id', $this->id)->where('user_id', $user->id)->get();

        $tagIds = $imageUserTags->transform(function ($value) {
            return $value->tag_id;
        });

        $tags = Tag::whereIn('id', $tagIds)->get();

        return $tags;
    }
}
