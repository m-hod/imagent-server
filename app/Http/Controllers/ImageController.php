<?php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Models\ImageUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\ImageUserResource;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    /**
     * GET api/user/images?tags=""
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // $user = Auth::user();
        // $userImages = ImageUser::where('user_id', $user->id)->get();

        dump(Storage::disk('digitalocean')->files('enim'));

        // return ImageUserResource::collection($userImages);
    }

    /**
     * POST api/user/image
     * Store a newly created resource in storage.
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

        $hash = hash_file('sha1', $request['image']);
        $ext = $request['image']->extension();
        $filename = "{$hash}.{$ext}";

        // check if image exists
        if(Storage::disk('digitalocean')->exists("imagent/{$filename}")) {
            $image = Image::where('hash', $hash);
            dd("already exists");

            // check if image exists locally
            // check if image exists cdn
            // if one not true, update whatever isnt

        } else {
            Storage::disk('digitalocean')->putFileAs('imagent', new File($request['image']), $filename, 'public');

            $image = Image::create([
                'hash' => $hash,
                'ext' => $ext
            ]);

            dd($image);
        }


        // if image exists, just retrieve image from db
        // else store image in cdn, hash in db, then retrieve it

        // then create tags if tags added
        // and crate assosciation between user and tags
        // and attach them to image
        // and create assosciation between user and image

        // return image resource (should be url + user_tags)
    }

    // store image and assosciated tags
        //

    // delete image (not tags tho)
}
