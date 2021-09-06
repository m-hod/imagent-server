<?php

namespace Tests\Unit;

use App\Models\Image;
use App\Models\ImageTag;
use App\Models\ImageUser;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserTag;
use App\Models\ImageUserTag;
use App\Services\Common\Utils;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /** @var User */
    private $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Auth::login($this->user);
    }

    public function test_list_user_tags()
    {
        $user = Auth::user();
        $tag = Tag::factory()->create();
        UserTag::factory()->create([
            'user_id' => $user->id,
            'tag_id' => $tag->id
        ]);

        $response = $this->actingAs($this->user)->getJson('api/user/tags')->assertSuccessful();

        $userTags = $response->decodeResponseJson();

        $this->assertGreaterThanOrEqual($userTags->count(), 1);
        $this->assertTrue(Utils::arraySome(collect($userTags['data'])->toArray(), function ($value) use ($tag) {
            return $value['tag']['id'] === $tag->id;
        }));
    }

    public function test_create_user_tag()
    {
        $tagSlug = str_random(6);

        $response = $this->actingAs($this->user)->postJson('api/user/tag', [
            'tag' => $tagSlug
        ])->assertSuccessful();

        $userTag = $response->decodeResponseJson();

        $this->assertEquals($userTag['data']['tag']['tag'], $tagSlug);
    }

    public function test_delete_user_tag()
    {
        $user = Auth::user();
        $tags = Tag::factory()->count(2)->create();

        $firstTagId = $tags->first()->id;
        $lastTagId = $tags->last()->id;

        UserTag::factory()->create([
            'user_id' => $user->id,
            'tag_id' => $firstTagId
        ]);
        UserTag::factory()->create([
            'user_id' => $user->id,
            'tag_id' => $lastTagId,
        ]);
        UserTag::factory()->create([
            'user_id' => User::inRandomOrder()->first()->id,
            'tag_id' => $lastTagId,
        ]);

        $this->actingAs($this->user)->deleteJson("api/user/tag/{$firstTagId}")->assertSuccessful();

        $this->assertEmpty(UserTag::where('user_id', $user->id)->where('tag_id', $firstTagId)->get());
        $this->actingAs($this->user)->deleteJson("api/user/tag/{$tags->last()->id}")->assertSuccessful();
        $this->assertEmpty(UserTag::where('user_id', $user->id)->where('tag_id', $lastTagId)->get());
        $this->assertNotEmpty(Tag::find($lastTagId));
    }

    /**
     * POST api/user/image
    */
    public function test_create_image() {
        Storage::fake('digitalocean');

        $upload = new UploadedFile(resource_path('test-files/p07ryyyj.jpg'), 'p07ryyyj.jpg', null, null, true);

        $response = $this->actingAs($this->user)->postJson("api/user/image", [
            'image' => $upload,
            'tags' => ['test']
        ])->dump()->assertSuccessful();

        $image = $response->decodeResponseJson();

        $baseUrl = config('filesystems.disks.digitalocean.endpoint');
        $hash = hash_file('sha1', $upload);
        $ext = $upload->extension();

        $this->assertEquals($image['data']['url'], "{$baseUrl}/imagent/{$hash}.{$ext}");
        $this->assertEquals($image['data']['tags'][0]['tag'], "test");
        $this->assertEquals($image['data']['user_tags'][0]['tag'], "test");

        $response = $this->actingAs($this->user)->postJson("api/user/image", [
            'image' => $upload,
        ])->assertStatus(422);

        $newUser = User::factory()->create();

        $response = $this->actingAs($newUser)->postJson("api/user/image", [
            'image' => $upload,
        ])->assertSuccessful();

        $image = $response->decodeResponseJson();
        $imageId = $image['data']['id'];
        $imageUsers = ImageUser::where('image_id', $imageId)->get();
        $initialImageUserTags = ImageUserTag::where('image_id', $imageId)->where('user_id', $this->user->id)->get();
        $newUserImageTagUsers = ImageUserTag::where('image_id', $imageId)->where('user_id', $newUser->id)->get();

        $this->assertEquals($imageUsers->count(), 2);
        $this->assertEquals($initialImageUserTags->count(), 1);
        $this->assertEquals($newUserImageTagUsers->count(), 1);
    }

    /**
     * DELETE api/user/image/{image}
    */
    public function test_delete_image()
    {
        Storage::fake('digitalocean');

        $upload = new UploadedFile(resource_path('test-files/p07ryyyj.jpg'), 'p07ryyyj.jpg', null, null, true);
        $response = $this->actingAs($this->user)->postJson("api/user/image", [
            'image' => $upload,
            'tags' => ['test']
        ])->assertSuccessful();

        $image = $response->decodeResponseJson();
        $imageId = $image['data']['id'];

        $response = $this->actingAs($this->user)->deleteJson("api/user/image/{$imageId}")->assertSuccessful();

        $hash = hash_file('sha1', $upload);
        $ext = $upload->extension();

        $this->assertTrue(!Storage::disk('digitalocean')->exists("imagent/{$hash}.{$ext}"));

        $imageTags = ImageTag::where('image_id', $imageId)->get();
        $imageUserTags = ImageUserTag::where('image_id', $imageId)->where('user_id', $this->user->id)->get();

        $this->assertEquals($imageTags->count(), 0);
        $this->assertEquals($imageUserTags->count(), 0);

        $this->actingAs($this->user)->postJson("api/user/image", [
            'image' => $upload,
        ])->assertSuccessful();

        $newUser = User::factory()->create();

        $response = $this->actingAs($newUser)->postJson("api/user/image", [
            'image' => $upload,
        ])->assertSuccessful();

        $image = $response->decodeResponseJson();
        $imageId = $image['data']['id'];

        $this->actingAs($newUser)->deleteJson("api/user/image/{$imageId}")->assertSuccessful();

        $image = Image::find($imageId);

        $this->assertNotNull($image);
        $this->assertTrue(Storage::disk('digitalocean')->exists("imagent/{$hash}.{$ext}"));
    }
}
