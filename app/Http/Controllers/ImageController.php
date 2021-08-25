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
        if(Storage::disk('digitalocean')->exists("imagent/{$hash}")) {
            dd("already exists");
        }
        $extension = $request['image']->extension();
        $image = Storage::disk('digitalocean')->putFileAs('imagent', new File($request['image']), "{$hash}.{$extension}", 'public');
        dd($image);

        // store image with hash as url
        // if tags, check if tag records for them, if not add them (and add them to user)
        // then add relationships between those tags and the image
        // return image resource
    }
    // store image and assosciated tags
        //

    // delete image (not tags tho)
}
