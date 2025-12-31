<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            ['name' => 'Cardiology', 'name_ar' => 'أمراض القلب', 'icon' => '🫀'],
            ['name' => 'Orthopedics', 'name_ar' => 'جراحة العظام', 'icon' => '🦴'],
            ['name' => 'Neurology', 'name_ar' => 'أمراض الأعصاب', 'icon' => '🧠'],
            ['name' => 'Pediatrics', 'name_ar' => 'طب الأطفال', 'icon' => '👶'],
            ['name' => 'Dermatology', 'name_ar' => 'الأمراض الجلدية', 'icon' => '🩹'],
            ['name' => 'Ophthalmology', 'name_ar' => 'طب العيون', 'icon' => '👁️'],
            ['name' => 'ENT', 'name_ar' => 'الأنف والأذن والحنجرة', 'icon' => '👂'],
            ['name' => 'General Medicine', 'name_ar' => 'الطب العام', 'icon' => '🩺'],
            ['name' => 'IT & Maintenance', 'name_ar' => 'تقنية المعلومات والصيانة', 'icon' => '🔧'],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(['name' => $dept['name']], $dept);
        }
    }
}
