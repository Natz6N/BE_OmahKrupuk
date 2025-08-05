<?php
namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $products = [
            // Kategori Kerupuk (ID: 1)
            [
                'category_id' => 1,
                'name' => 'Kerupuk Udang Premium',
                'description' => 'Kerupuk udang berkualitas tinggi dengan rasa gurih',
                'brand' => 'Udang Mas',
                'has_expiry' => true,
                'is_active' => true,
            ],
            [
                'category_id' => 1,
                'name' => 'Kerupuk Ikan',
                'description' => 'Kerupuk ikan segar dengan bumbu tradisional',
                'brand' => 'Ikan Segar',
                'has_expiry' => true,
                'is_active' => true,
            ],
            [
                'category_id' => 1,
                'name' => 'Kerupuk Bawang',
                'description' => 'Kerupuk bawang renyah dan harum',
                'brand' => 'Bawang Wangi',
                'has_expiry' => true,
                'is_active' => true,
            ],

            // Kategori Camilan (ID: 2)
            [
                'category_id' => 2,
                'name' => 'Kacang Thailand',
                'description' => 'Kacang thailand pedas manis',
                'brand' => 'Kacang Enak',
                'has_expiry' => true,
                'is_active' => true,
            ],
            [
                'category_id' => 2,
                'name' => 'Biskuit Marie',
                'description' => 'Biskuit marie klasik untuk teh',
                'brand' => 'Marie Gold',
                'has_expiry' => true,
                'is_active' => true,
            ],

            // Kategori Bumbu Dapur (ID: 3)
            [
                'category_id' => 3,
                'name' => 'Garam Dapur',
                'description' => 'Garam dapur beryodium',
                'brand' => 'Garam Sehat',
                'has_expiry' => false,
                'is_active' => true,
            ],
            [
                'category_id' => 3,
                'name' => 'Penyedap Rasa',
                'description' => 'Penyedap rasa serbaguna',
                'brand' => 'Sedap Sekali',
                'has_expiry' => true,
                'is_active' => true,
            ],

            // Kategori Minuman (ID: 4)
            [
                'category_id' => 4,
                'name' => 'Teh Celup',
                'description' => 'Teh celup aroma wangi',
                'brand' => 'Teh Wangi',
                'has_expiry' => true,
                'is_active' => true,
            ],

            // Kategori Sembako (ID: 5)
            [
                'category_id' => 5,
                'name' => 'Beras Premium',
                'description' => 'Beras premium kualitas terbaik',
                'brand' => 'Beras Pulen',
                'has_expiry' => false,
                'is_active' => true,
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
