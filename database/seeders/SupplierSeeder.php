<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supplier;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'PT. Kimia Farma',
                'contact_person' => 'Budi Santoso',
                'phone' => '021-5551234',
                'email' => 'budi@kimiafarma.co.id',
                'address' => 'Jl. Veteran No. 9, Jakarta Pusat'
            ],
            [
                'name' => 'CV. Medika Jaya',
                'contact_person' => 'Siti Nurhaliza',
                'phone' => '021-5555678',
                'email' => 'siti@medikajaya.com',
                'address' => 'Jl. Sudirman No. 45, Jakarta Selatan'
            ],
            [
                'name' => 'PT. Alkes Indonesia',
                'contact_person' => 'Ahmad Rahman',
                'phone' => '021-5559012',
                'email' => 'ahmad@alkesindonesia.co.id',
                'address' => 'Jl. Gatot Subroto No. 12, Jakarta Selatan'
            ]
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }
    }
}
