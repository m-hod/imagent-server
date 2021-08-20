<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserTagResource;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TagController extends Controller
{
    /**
     * GET api/user/tags
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        $userTags = UserTag::where('user_id', $user->id)->get();

        return UserTagResource::collection($userTags);
    }

    /**
     * POST api/user/tag
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'tag' => ['required', 'unique:tags,tag']
        ]);

        $user = Auth::user();
        $tag = Tag::where('tag', request()->input('tag'))->first();

        // if tag doesn't already exist, create it before assosciating with user
        if(!$tag) {
            $tag = Tag::create($request->all());
        }

        $userTag = UserTag::create([
            'user_id' => $user->id,
            'tag_id' => $tag->id,
        ]);

        return new UserTagResource($userTag);
    }

    /**
     * DELETE api/user/tag/{tag}
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Tag  $tag
     * @return \Illuminate\Http\Response
     */
    public function destroy(Tag $tag)
    {
        $user = Auth::user();
        $userTag = UserTag::where('user_id', $user->id)->where('tag_id', $tag->id)->first();
        $userTag->delete();

        $tag = $tag->fresh();
        $userTags = UserTag::where('tag_id', $tag->id)->get();

        if($userTags->count() === 0) {
            $tag->delete();
        }

        return response()->noContent();
    }
}
