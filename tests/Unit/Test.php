<?php

namespace Tests\Unit;

use App\Models\Tag;
use App\Models\User;
use App\Models\UserTag;
use App\Services\Common\Utils;
use Faker\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Fortify;
use Tests\TestCase;

class AuthTest extends TestCase
{

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
        ])->dump()->assertSuccessful();

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
}
