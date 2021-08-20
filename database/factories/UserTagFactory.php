<?php

namespace Database\Factories;

use App\Models\Tag;
use App\Models\User;
use App\Models\UserTag;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserTagFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UserTag::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => User::inRandomOrder()->first()->id,
            'tag_id' => Tag::inRandomOrder()->first()->id
        ];
    }
}
