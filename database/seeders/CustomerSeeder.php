<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            [
                'name' => 'RS. Siloam Hospitals',
                'contact_person' => 'Dr. Maria Sari',
                'phone' => '021-7771234',
                'email' => 'procurement@siloam.co.id',
                'address' => 'Jl. Garnisun Dalam No. 2-3, Jakarta Pusat'
            ],
            [
                'name' => 'Lab Klinik Prodia',
                'contact_person' => 'Andi Wijaya',
                'phone' => '021-7775678',
                'email' => 'purchasing@prodia.co.id',
                'address' => 'Jl. Kramat Raya No. 150, Jakarta Pusat'
            ],
            [
                'name' => 'Puskesmas Tanah Abang',
                'contact_person' => 'Ibu Ratna',
                'phone' => '021-3456789',
                'email' => 'puskesmas.tanahabang@gmail.com',
                'address' => 'Jl. Tanah Abang III No. 1, Jakarta Pusat'
            ]
        ];

        foreach ($customers as $customer) {
            Customer::create($customer);
        }
    }
}
