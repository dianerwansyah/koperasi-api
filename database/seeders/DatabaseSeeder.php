<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Saving;
use App\Models\Loan;
use App\Models\Settlement;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User admin
        User::create([
            'name' => 'Admin',
            'email' => 'admin@koperasi.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'created_by' => null,
            'updated_by' => null,
        ]);

        // User karyawan
        User::create([
            'name' => 'Karyawan',
            'email' => 'karyawan@koperasi.com',
            'password' => Hash::make('password'),
            'role' => 'karyawan',
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        // Savings sample
        Saving::create([
            'user_id' => 1, // admin
            'value' => 1000000,
            'type' => 'wajib',
            'date' => now(),
            'bagi_hasil' => 0,
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        // Loans sample
        $loan = Loan::create([
            'user_id' => 2, // karyawan
            'amount' => 5000000,
            'phone' => '081234567890',
            'address' => 'Jl. Contoh No.1',
            'status' => 'applied',
            'apply_date' => now(),
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        // Settlements sample
        Settlement::create([
            'user_id' => 2,
            'loan_id' => $loan->id,
            'amount' => 5000000,
            'proof_path' => null,
            'status' => 'applied',
            'settlement_date' => now(),
            'created_by' => 1,
            'updated_by' => 1,
        ]);
    }
}
