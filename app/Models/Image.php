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

    // this is a recommended way to declare event handlers
    public static function boot() {
        parent::boot();

        static::deleting(function($image) {
             $image->imageTags()->delete();
        });
    }

    public function getUserTags()
    {
        $user = Auth::user();

        $userTags = UserTag::where('user_id', $user->id)->get();

        $tags = $userTags->transform(function ($value) {
            return $value->tag;
        });

        $tagIds = $tags->pluck("id");

        $imageTags = ImageTag::where('image_id', $this->id)->whereIn('tag_id', $tagIds)->get();

        return $imageTags->transform(function ($value) {
            return $value->tag;
        });
    }
}
