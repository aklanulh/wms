<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductCategory;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $reagentKimia = ProductCategory::where('name', 'Reagent Kimia')->first();
        $alatKesehatan = ProductCategory::where('name', 'Alat Kesehatan')->first();
        $consumables = ProductCategory::where('name', 'Consumables')->first();
        $instrumenLab = ProductCategory::where('name', 'Instrumen Lab')->first();

        $products = [
            // Reagent Kimia
            [
                'code' => 'RK001',
                'name' => 'DS Diluent',
                'description' => 'Diluent solution untuk analisis hematologi',
                'category_id' => $reagentKimia->id,
                'unit' => 'Liter',
                'price' => 125000,
                'current_stock' => 10,
                'minimum_stock' => 100,
                'lot_number' => 'LOT2024001',
                'expired_date' => '2025-12-31',
                'distribution_permit' => 'AKL20501234567'
            ],
            [
                'code' => 'RK002',
                'name' => 'SC Cal Plus',
                'description' => 'Calibrator solution untuk chemistry analyzer',
                'category_id' => $reagentKimia->id,
                'unit' => 'ml',
                'price' => 85000,
                'current_stock' => 30,
                'minimum_stock' => 5,
                'lot_number' => 'LOT2024002',
                'expired_date' => '2025-11-30',
                'distribution_permit' => 'AKL20501234568'
            ],
            [
                'code' => 'RK003',
                'name' => 'Vicom Glucose Kit',
                'description' => 'Kit reagent untuk pemeriksaan glukosa',
                'category_id' => $reagentKimia->id,
                'unit' => 'Kit',
                'price' => 450000,
                'current_stock' => 15,
                'minimum_stock' => 3,
                'lot_number' => 'LOT2024003',
                'expired_date' => '2025-10-15',
                'distribution_permit' => 'AKL20501234569'
            ],
            [
                'code' => 'RK004',
                'name' => 'Vicom Cholesterol Kit',
                'description' => 'Kit reagent untuk pemeriksaan kolesterol',
                'category_id' => $reagentKimia->id,
                'unit' => 'Kit',
                'price' => 380000,
                'current_stock' => 12,
                'minimum_stock' => 3,
                'lot_number' => 'LOT2024004',
                'expired_date' => '2025-12-31',
                'distribution_permit' => 'AKL20501234570'
            ],
            [
                'code' => 'RK005',
                'name' => 'Vicom Triglyceride Kit',
                'description' => 'Kit reagent untuk pemeriksaan trigliserida',
                'category_id' => $reagentKimia->id,
                'unit' => 'Kit',
                'price' => 420000,
                'current_stock' => 8,
                'minimum_stock' => 2,
                'lot_number' => 'LOT2024005',
                'expired_date' => '2025-10-15',
                'distribution_permit' => 'AKL20501234571'
            ],

            // Alat Kesehatan
            [
                'code' => 'AK001',
                'name' => 'Terumo Syringe 3ml',
                'description' => 'Spuit disposable 3ml merk Terumo',
                'category_id' => $alatKesehatan->id,
                'unit' => 'Pcs',
                'price' => 2500,
                'current_stock' => 200,
                'minimum_stock' => 200,
                'lot_number' => 'TER2024001',
                'expired_date' => '2027-08-30',
                'distribution_permit' => 'AKL20601234567'
            ],
            [
                'code' => 'AK002',
                'name' => 'Terumo Syringe 5ml',
                'description' => 'Spuit disposable 5ml merk Terumo',
                'category_id' => $alatKesehatan->id,
                'unit' => 'Pcs',
                'price' => 3200,
                'current_stock' => 150,
                'minimum_stock' => 150,
                'lot_number' => 'TER2024002',
                'expired_date' => '2027-09-15',
                'distribution_permit' => 'AKL20601234568'
            ],
            [
                'code' => 'AK003',
                'name' => 'Ansell Latex Gloves',
                'description' => 'Sarung tangan latex disposable Ansell',
                'category_id' => $alatKesehatan->id,
                'unit' => 'Box',
                'price' => 65000,
                'current_stock' => 10,
                'minimum_stock' => 10,
                'lot_number' => 'ANS2024001',
                'expired_date' => '2026-12-31',
                'distribution_permit' => 'AKL20601234569'
            ],

            // Consumables
            [
                'code' => 'CS001',
                'name' => 'Eppendorf Pipette Tips 10μl',
                'description' => 'Tips pipet 10 mikroliter merk Eppendorf',
                'category_id' => $consumables->id,
                'unit' => 'Box',
                'price' => 180000,
                'current_stock' => 25,
                'minimum_stock' => 5,
                'lot_number' => 'EPP2024001',
                'expired_date' => '2026-12-31',
                'distribution_permit' => 'AKL20701234567'
            ],
            [
                'code' => 'CS002',
                'name' => 'Eppendorf Pipette Tips 200μl',
                'description' => 'Tips pipet 200 mikroliter merk Eppendorf',
                'category_id' => $consumables->id,
                'unit' => 'Box',
                'price' => 165000,
                'current_stock' => 30,
                'minimum_stock' => 8,
                'lot_number' => 'EPP2024002',
                'expired_date' => '2026-12-31',
                'distribution_permit' => 'AKL20701234568'
            ],
            [
                'code' => 'CS003',
                'name' => 'Falcon Centrifuge Tube 15ml',
                'description' => 'Tabung sentrifuge 15ml merk Falcon',
                'category_id' => $consumables->id,
                'unit' => 'Pack',
                'price' => 95000,
                'current_stock' => 40,
                'minimum_stock' => 10,
                'lot_number' => 'FAL2024001',
                'expired_date' => '2026-12-31',
                'distribution_permit' => 'AKL20701234569'
            ],
            [
                'code' => 'CS004',
                'name' => 'Falcon Centrifuge Tube 50ml',
                'description' => 'Tabung sentrifuge 50ml merk Falcon',
                'category_id' => $consumables->id,
                'unit' => 'Pack',
                'price' => 125000,
                'current_stock' => 35,
                'minimum_stock' => 8,
                'lot_number' => 'FAL2024002',
                'expired_date' => '2026-12-31',
                'distribution_permit' => 'AKL20701234570'
            ],

            // Instrumen Lab
            [
                'code' => 'IL001',
                'name' => 'Eppendorf Pipette 10-100μl',
                'description' => 'Pipet variabel 10-100 mikroliter Eppendorf',
                'category_id' => $instrumenLab->id,
                'unit' => 'Unit',
                'price' => 2850000,
                'current_stock' => 5,
                'minimum_stock' => 1,
                'lot_number' => 'EPP2024003',
                'expired_date' => '2026-12-31',
                'distribution_permit' => 'AKL20801234567'
            ],
            [
                'code' => 'IL002',
                'name' => 'Eppendorf Pipette 100-1000μl',
                'description' => 'Pipet variabel 100-1000 mikroliter Eppendorf',
                'category_id' => $instrumenLab->id,
                'unit' => 'Unit',
                'price' => 3200000,
                'current_stock' => 3,
                'minimum_stock' => 1,
                'lot_number' => 'EPP2024004',
                'expired_date' => '2026-12-31',
                'distribution_permit' => 'AKL20801234568'
            ],
            [
                'code' => 'IL003',
                'name' => 'Eppendorf Microcentrifuge',
                'description' => 'Mikrosentrifuge Eppendorf 5424',
                'category_id' => $instrumenLab->id,
                'unit' => 'Unit',
                'price' => 45000000,
                'current_stock' => 2,
                'minimum_stock' => 1,
                'lot_number' => 'EPP2024005',
                'expired_date' => '2026-12-31',
                'distribution_permit' => 'AKL20801234569'
            ]
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
