<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = [
            ['code' => 'QHD', 'name' => "Qur'an Hadits"],
            ['code' => 'AAK', 'name' => 'Aqidah Akhlak'],
            ['code' => 'FIQ', 'name' => 'Fiqih'],
            ['code' => 'BAR', 'name' => 'Bahasa Arab'],
            ['code' => 'SKI', 'name' => 'Sejarah Kebudayaan Islam'],
            ['code' => 'MAT', 'name' => 'Matematika'],
            ['code' => 'BIG', 'name' => 'Bahasa Inggris'],
            ['code' => 'BIN', 'name' => 'Bahasa Indonesia'],
            ['code' => 'EKO', 'name' => 'Ekonomi'],
            ['code' => 'SEJ', 'name' => 'Sejarah'],
            ['code' => 'SOS', 'name' => 'Sosiologi'],
            ['code' => 'PKN', 'name' => 'Pendidikan Pancasila dan Kewarganegaraan'],
            ['code' => 'FIS', 'name' => 'Fisika'],
            ['code' => 'BIO', 'name' => 'Biologi'],
            ['code' => 'KIM', 'name' => 'Kimia'],
            ['code' => 'USH', 'name' => 'Ushul Fiqih'],
            ['code' => 'ITF', 'name' => 'Ilmu Tafsir'],
            ['code' => 'IHD', 'name' => 'Ilmu Hadits'],
            ['code' => 'KKU', 'name' => 'Kitab Kuning'],
            ['code' => 'PJK', 'name' => 'Penjaskes'],
            ['code' => 'ASW', 'name' => 'Aswaja'],
            ['code' => 'INF', 'name' => 'Informatika'],
            ['code' => 'BIM', 'name' => 'Bimbingan Konseling'],
            ['code' => 'PUD', 'name' => 'Praktek Ubudiyah'],
            ['code' => 'THF', 'name' => 'Tahfidz'],
            ['code' => 'TUR', 'name' => 'Turats'],
            ['code' => 'HIT', 'name' => 'Hitung Cepat'],
            ['code' => 'KTI', 'name' => 'Karya Tulis Ilmiah'],
        ];

        foreach ($subjects as $subject) {
            Subject::create([
                ...$subject,
                'is_active' => true,
            ]);
        }
    }
}

