<?php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Models\ImageUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\ImageUserResource;
use App\Models\ImageTag;
use App\Models\Tag;
use App\Models\UserTag;
use App\Models\ImageUserTag;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ImageController extends Controller
{
    /**
     * GET api/user/images?tags=""
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $request->validate([
            'tags' => ['sometimes', 'string'],
        ]);

        $tags = json_decode(request()->input('tags', "[]"));

        $user = Auth::user();

        $images = Image::whereHas('users', function ($query) use ($user) {
            $query->where('users.id', $user->id);
        })->whereHas('tags', function ($query) use ($tags) {
            $query->whereIn('tags.tag', $tags);
        })->get();

        return ImageUserResource::collection($images);
    }

    /**
     * POST api/user/image
     * Store image in CDN with hash as url name
     * Store image hash in db
     * Store user image assosciation
     * Store any tags added with the image
     * Store user tag assosciation
     * Store image tag assosciation
     * Store image user tag assosciation
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'image' => ['required', 'image'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['sometimes', 'string']
        ]);

        $user = Auth::user();

        $hash = hash_file('sha1', $request['image']);
        $ext = $request['image']->extension();
        $filename = "{$hash}.{$ext}";

        $image = Image::where('hash', $hash)->first();

        // if image doesn't already exist, create it on cdn and database
        if(!$image) {
            Storage::disk('digitalocean')->putFileAs('imagent', new File($request['image']), $filename, 'public');

            $image = Image::create([
                'hash' => $hash,
                'ext' => $ext
            ]);
        }

        // should only do this if doesn't already exist
        $imageUser = ImageUser::where('user_id', $user->id)->where('image_id', $image->id)->first();

        if($imageUser) {
            throw ValidationException::withMessages([
                'image' => 'Image already in library'
            ]);
        }

        // create user image assosciation
        ImageUser::create([
            'user_id' => $user->id,
            'image_id' => $image->id,
        ]);

        // if tags
        $tags = request()->input('tags', []);

        if(count($tags)) {
            foreach($tags as $_tag) {
                $tag = Tag::where('tag', $_tag)->first();

                // if tag does not already exist, create it
                if(!$tag) {
                    $tag = Tag::create([
                        'tag' => $_tag
                    ]);
                }

                $userTag = UserTag::where('user_id', $user->id)->where('tag_id', $tag->id)->first();

                // if user tag does not already exist, create the assosciation
                if(!$userTag) {
                    UserTag::create([
                        'user_id' => $user->id,
                        'tag_id' => $tag->id,
                    ]);
                }

                $imageTag = ImageTag::where('image_id', $image->id)->where('tag_id', $tag->id)->first();

                if(!$imageTag) {
                    ImageTag::create([
                        'image_id' => $image->id,
                        'tag_id' => $tag->id
                    ]);
                }

                $imageUserTag = ImageUserTag::where('image_id', $image->id)->where('tag_id', $tag->id)->where('user_id', $user->id)->first();

                if(!$imageUserTag) {
                    ImageUserTag::create([
                        'image_id' => $image->id,
                        'tag_id' => $tag->id,
                        'user_id' => $user->id
                    ]);
                }
            }
        }

        $image->refresh();

        return new ImageUserResource($image);
    }

    /**
     * PUT api/user/image/{image}
     * Update the specified resource from storage.
     * Store any tags added with the image
     * Store user tag assosciation
     * Store image tag assosciation
     * Store image user tag assosciation
     * Remove any tags on the image that were not included in the params
     * Remove user tag assosciation if so
     * Remove image user tag assosciation if so
     * Check if no other users have tag assosciated with image
     * Remove image tag if so
     *
     * @param  \App\Models\Image  $image
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Image $image)
    {
        $request->validate([
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['sometimes', 'string']
        ]);

        $tags = request()->input('tags', []);

        $user = Auth::user();

        // track tag ids of user tags on image
        $tagIds = [];

        // add tags if don't already exist
        foreach($tags as $_tag) {
            $tag = Tag::where('tag', $_tag)->first();

            // if tag does not already exist, create it
            if(!$tag) {
                $tag = Tag::create([
                    'tag' => $_tag
                ]);
            }

            array_push($tagIds, $tag->id);

            $userTag = UserTag::where('user_id', $user->id)->where('tag_id', $tag->id)->first();

            // if user tag does not already exist, create the assosciation
            if(!$userTag) {
                UserTag::create([
                    'user_id' => $user->id,
                    'tag_id' => $tag->id,
                ]);
            }

            $imageTag = ImageTag::where('image_id', $image->id)->where('tag_id', $tag->id)->first();

            if(!$imageTag) {
                ImageTag::create([
                    'image_id' => $image->id,
                    'tag_id' => $tag->id
                ]);
            }

            $imageUserTag = ImageUserTag::where('image_id', $image->id)->where('tag_id', $tag->id)->where('user_id', $user->id)->first();

            if(!$imageUserTag) {
                ImageUserTag::create([
                    'image_id' => $image->id,
                    'tag_id' => $tag->id,
                    'user_id' => $user->id
                ]);
            }
        }

        // remove tags that exist that weren't in the payload
        $deprecatedUserTags = ImageUserTag::where('image_id', $image->id)->where('user_id', $user->id)->whereNotIn('tag_id', $tagIds);
        $deprecatedUserTags->delete();

        $image->refresh();

        return new ImageUserResource($image);
    }

    /**
     * DELETE api/user/image/{image}
     * Remove the specified resource from storage.
     * Remove user image assosciation
     * Check if image has any users still assosciated with it
     * Remove image from cdn if so
     * Remove image if so
     * Remove image tags if so
     * Remove image user tags if so
     *
     * @param  \App\Models\Image  $image
     * @return \Illuminate\Http\Response
     */
    public function delete(Image $image)
    {
        $user = Auth::user();

        $imageUser = ImageUser::where('image_id', $image->id)->where('user_id', $user->id)->first();
        $imageUser->delete();

        $image->refresh();

        $users = $image->users;

        // if image no longer has any users assosciated with it, clean it up
        if(!$users->count()) {
            // delete image on cdn
            Storage::disk('digitalocean')->delete("imagent/{$image->hash}.{$image->ext}");

            // delete image (which also cascade deletes all image tag / image tag user assosciations)
            $image->delete();
        }

        return response()->noContent();
    }
}
