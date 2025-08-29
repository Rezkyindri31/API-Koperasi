<?php

namespace Database\Seeders;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
{
    User::updateOrCreate(
        ['email' => 'admin@koperasi.com'],
        ['name'=>'Admin', 'password'=>Hash::make('password'), 'role'=>'admin']
    );

    User::updateOrCreate(
        ['email' => 'karyawan@koperasi.com'],
        ['name'=>'Karyawan', 'password'=>Hash::make('password'), 'role'=>'karyawan']
    );
}
}