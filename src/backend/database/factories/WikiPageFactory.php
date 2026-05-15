<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WikiPage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WikiPage>
 */
class WikiPageFactory extends Factory
{
    protected $model = WikiPage::class;

    public function definition(): array
    {
        $n = fake()->unique()->numberBetween(1, 100000);

        return [
            'slug' => 'page-'.$n,
            'title' => 'Wiki '.$n,
            'body' => "# Wiki {$n}\n\n".fake()->paragraph(),
            'parent_id' => null,
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }
}
