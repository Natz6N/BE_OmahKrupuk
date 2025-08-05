<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            [
                'name' => 'Kerupuk',
                'description' => 'Berbagai jenis kerupuk tradisional dan modern'
            ],
            [
                'name' => 'Camilan',
                'description' => 'Camilan ringan dan makanan kecil'
            ],
            [
                'name' => 'Bumbu Dapur',
                'description' => 'Bumbu masak dan penyedap rasa'
            ],
            [
                'name' => 'Minuman',
                'description' => 'Minuman kemasan dan serbuk'
            ],
            [
                'name' => 'Sembako',
                'description' => 'Kebutuhan pokok sehari-hari'
            ]
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
