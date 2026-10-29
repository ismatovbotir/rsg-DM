<?php

namespace Database\Seeders;

use App\Models\Counterparty;
use App\Models\Item;
use App\Models\PriceType;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database. Development data only: change before production.
     */
    public function run(): void
    {
        $this->call(LabelTemplateSeeder::class);
        $this->call(CheckerThemeSeeder::class);

        // roles come from the migration (Administrator, PDA operator, Operator); dev PINs: change before production
        $roles = ['admin' => Role::where('is_admin', true)->value('id'), 'user' => Role::where('name', 'PDA operator')->value('id')];
        foreach ([
            ['admin@rsg.uz', 'Admin', 'tsd-admin-2026', 'admin', '4821'],
            ['pda@rsg.uz', 'PDA user', 'tsd-pda-2026', 'user', '7305'],
        ] as [$email, $name, $password, $role, $pin]) {
            $user = User::updateOrCreate(['email' => $email], ['name' => $name, 'password' => Hash::make($password), 'role' => $role, 'role_id' => $roles[$role]]);
            $user->pin || $user->issuePin($pin);
            $user->ensureBadge();
        }

        foreach ([['code' => 'MAIN', 'name' => 'Asosiy ombor'], ['code' => 'SHOP', 'name' => 'Do‘kon']] as $w) {
            Warehouse::updateOrCreate(['code' => $w['code']], $w);
        }

        foreach ([['code' => 'RETAIL', 'name' => 'Chakana narx'], ['code' => 'WHOLE', 'name' => 'Ulgurji narx']] as $pt) {
            PriceType::updateOrCreate(['code' => $pt['code']], $pt);
        }

        foreach ([['name' => 'Yetkazib beruvchi 1 MCHJ', 'stir' => '301234567'], ['name' => 'Oziq-ovqat Savdo', 'stir' => '302345678']] as $c) {
            Counterparty::updateOrCreate(['stir' => $c['stir']], $c);
        }

        foreach ([
            ['code' => '0001', 'article' => 'SUT-1L', 'barcode' => '4780000000011', 'name' => 'Sut 1 l', 'unit' => 'dona', 'price' => 12000],
            ['code' => '0002', 'article' => 'NON-01', 'barcode' => '4780000000028', 'name' => 'Non', 'unit' => 'dona', 'price' => 4000],
            ['code' => '0003', 'article' => 'SHK-KG', 'barcode' => '4780000000035', 'name' => 'Shakar', 'unit' => 'kg', 'price' => 11500, 'is_weighed' => true],
        ] as $item) {
            Item::updateOrCreate(['code' => $item['code']], $item);
        }

        // A case of 12 bottles of milk: scanning it books 12 pieces.
        Item::where('code', '0001')->first()->barcodes()->updateOrCreate(['barcode' => '4780000000099'], ['qty_in_pack' => 12]);
    }
}
