<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        $name = fake()->unique()->word();
        
        return [
            'name_en' => ucfirst($name),
            'name_ar' => 'قسم ' . $name,
            'slug' => Str::slug($name),
            'description_en' => fake()->sentence(),
            'description_ar' => 'وصف ' . $name,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }
}
