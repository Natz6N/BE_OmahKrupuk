<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run()
    {
        $suppliers = [
            [
                'name' => 'CV Kerupuk Sejahtera',
                'contact_person' => 'Budi Santoso',
                'phone' => '08123456789',
                'address' => 'Jl. Industri No. 15, Sidoarjo',
                'email' => 'budi@kerupuksejahtera.com',
                'is_active' => true,
            ],
            [
                'name' => 'PT Camilan Nusantara',
                'contact_person' => 'Siti Nurhaliza',
                'phone' => '08234567890',
                'address' => 'Jl. Raya Malang No. 45, Malang',
                'email' => 'siti@camilannusantara.com',
                'is_active' => true,
            ],
            [
                'name' => 'Toko Grosir Murah Jaya',
                'contact_person' => 'Ahmad Wijaya',
                'phone' => '08345678901',
                'address' => 'Jl. Pasar Besar No. 12, Surabaya',
                'email' => 'ahmad@grosirmurah.com',
                'is_active' => true,
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }
    }
}
