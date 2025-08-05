<?php
namespace Database\Seeders;

use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

class ProductVariantSeeder extends Seeder
{
    public function run()
    {
        $variants = [
            // Kerupuk Udang Premium (Product ID: 1)
            [
                'product_id' => 1,
                'variant_name' => 'Kemasan 100gr',
                'barcode' => '1234567890001',
                'barcode_type' => 'EAN',
                'selling_price' => 15000,
                'unit' => 'pcs',
                'is_active' => true,
            ],
            [
                'product_id' => 1,
                'variant_name' => 'Kemasan 250gr',
                'barcode' => '1234567890002',
                'barcode_type' => 'EAN',
                'selling_price' => 35000,
                'unit' => 'pcs',
                'is_active' => true,
            ],
            [
                'product_id' => 1,
                'variant_name' => 'Kemasan 500gr',
                'barcode' => '1234567890003',
                'barcode_type' => 'EAN',
                'selling_price' => 65000,
                'unit' => 'pcs',
                'is_active' => true,
            ],

            // Kerupuk Ikan (Product ID: 2)
            [
                'product_id' => 2,
                'variant_name' => 'Kemasan 100gr',
                'barcode' => '1234567890011',
                'barcode_type' => 'EAN',
                'selling_price' => 12000,
                'unit' => 'pcs',
                'is_active' => true,
            ],
            [
                'product_id' => 2,
                'variant_name' => 'Kemasan 250gr',
                'barcode' => '1234567890012',
                'barcode_type' => 'EAN',
                'selling_price' => 28000,
                'unit' => 'pcs',
                'is_active' => true,
            ],

            // Kerupuk Bawang (Product ID: 3)
            [
                'product_id' => 3,
                'variant_name' => 'Kemasan 150gr',
                'barcode' => '1234567890021',
                'barcode_type' => 'EAN',
                'selling_price' => 10000,
                'unit' => 'pcs',
                'is_active' => true,
            ],

            // Kacang Thailand (Product ID: 4)
            [
                'product_id' => 4,
                'variant_name' => 'Kemasan 50gr',
                'barcode' => '1234567890031',
                'barcode_type' => 'EAN',
                'selling_price' => 8000,
                'unit' => 'pcs',
                'is_active' => true,
            ],
            [
                'product_id' => 4,
                'variant_name' => 'Kemasan 100gr',
                'barcode' => '1234567890032',
                'barcode_type' => 'EAN',
                'selling_price' => 15000,
                'unit' => 'pcs',
                'is_active' => true,
            ],

            // Biskuit Marie (Product ID: 5)
            [
                'product_id' => 5,
                'variant_name' => 'Kemasan 200gr',
                'barcode' => '1234567890041',
                'barcode_type' => 'EAN',
                'selling_price' => 12000,
                'unit' => 'pcs',
                'is_active' => true,
            ],

            // Garam Dapur (Product ID: 6)
            [
                'product_id' => 6,
                'variant_name' => 'Kemasan 500gr',
                'barcode' => '1234567890051',
                'barcode_type' => 'EAN',
                'selling_price' => 3000,
                'unit' => 'pcs',
                'is_active' => true,
            ],
            [
                'product_id' => 6,
                'variant_name' => 'Kemasan 1kg',
                'barcode' => '1234567890052',
                'barcode_type' => 'EAN',
                'selling_price' => 5500,
                'unit' => 'pcs',
                'is_active' => true,
            ],

            // Penyedap Rasa (Product ID: 7)
            [
                'product_id' => 7,
                'variant_name' => 'Sachet 10gr',
                'barcode' => '1234567890061',
                'barcode_type' => 'EAN',
                'selling_price' => 1000,
                'unit' => 'pcs',
                'is_active' => true,
            ],
            [
                'product_id' => 7,
                'variant_name' => 'Kemasan 100gr',
                'barcode' => '1234567890062',
                'barcode_type' => 'EAN',
                'selling_price' => 8000,
                'unit' => 'pcs',
                'is_active' => true,
            ],

            // Teh Celup (Product ID: 8)
            [
                'product_id' => 8,
                'variant_name' => 'Isi 25 kantong',
                'barcode' => '1234567890071',
                'barcode_type' => 'EAN',
                'selling_price' => 12000,
                'unit' => 'pcs',
                'is_active' => true,
            ],

            // Beras Premium (Product ID: 9)
            [
                'product_id' => 9,
                'variant_name' => 'Kemasan 5kg',
                'barcode' => '1234567890081',
                'barcode_type' => 'EAN',
                'selling_price' => 75000,
                'unit' => 'pcs',
                'is_active' => true,
            ],
            [
                'product_id' => 9,
                'variant_name' => 'Kemasan 10kg',
                'barcode' => '1234567890082',
                'barcode_type' => 'EAN',
                'selling_price' => 145000,
                'unit' => 'pcs',
                'is_active' => true,
            ],
        ];

        foreach ($variants as $variant) {
            ProductVariant::create($variant);
        }
    }
}
