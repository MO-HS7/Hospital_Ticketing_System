<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name_en' => 'Emergency', 'name_ar' => 'الطوارئ', 'icon_key' => 'emergency', 'sort_order' => 1],
            ['name_en' => 'Internal Medicine', 'name_ar' => 'الطب الباطني', 'icon_key' => 'internal', 'sort_order' => 2],
            ['name_en' => 'Cardiology', 'name_ar' => 'أمراض القلب', 'icon_key' => 'cardiology', 'sort_order' => 3],
            ['name_en' => 'Neurology', 'name_ar' => 'أمراض الأعصاب', 'icon_key' => 'neurology', 'sort_order' => 4],
            ['name_en' => 'Orthopedics', 'name_ar' => 'جراحة العظام', 'icon_key' => 'orthopedics', 'sort_order' => 5],
            ['name_en' => 'General Surgery', 'name_ar' => 'الجراحة العامة', 'icon_key' => 'surgery', 'sort_order' => 6],
            ['name_en' => 'Pediatrics', 'name_ar' => 'طب الأطفال', 'icon_key' => 'pediatrics', 'sort_order' => 7],
            ['name_en' => 'Obstetrics & Gynecology', 'name_ar' => 'النساء والتوليد', 'icon_key' => 'obgyn', 'sort_order' => 8],
            ['name_en' => 'ENT', 'name_ar' => 'الأنف والأذن والحنجرة', 'icon_key' => 'ent', 'sort_order' => 9],
            ['name_en' => 'Ophthalmology', 'name_ar' => 'طب العيون', 'icon_key' => 'ophthalmology', 'sort_order' => 10],
            ['name_en' => 'Dermatology', 'name_ar' => 'الأمراض الجلدية', 'icon_key' => 'dermatology', 'sort_order' => 11],
            ['name_en' => 'Urology', 'name_ar' => 'المسالك البولية', 'icon_key' => 'urology', 'sort_order' => 12],
            ['name_en' => 'Nephrology', 'name_ar' => 'أمراض الكلى', 'icon_key' => 'nephrology', 'sort_order' => 13],
            ['name_en' => 'Gastroenterology', 'name_ar' => 'أمراض الجهاز الهضمي', 'icon_key' => 'gastro', 'sort_order' => 14],
            ['name_en' => 'Pulmonology', 'name_ar' => 'أمراض الصدر والرئة', 'icon_key' => 'pulmonology', 'sort_order' => 15],
            ['name_en' => 'Endocrinology', 'name_ar' => 'الغدد الصماء والسكري', 'icon_key' => 'endocrinology', 'sort_order' => 16],
            ['name_en' => 'Oncology', 'name_ar' => 'الأورام', 'icon_key' => 'oncology', 'sort_order' => 17],
            ['name_en' => 'Hematology', 'name_ar' => 'أمراض الدم', 'icon_key' => 'hematology', 'sort_order' => 18],
            ['name_en' => 'Psychiatry', 'name_ar' => 'الطب النفسي', 'icon_key' => 'psychiatry', 'sort_order' => 19],
            ['name_en' => 'Radiology', 'name_ar' => 'الأشعة والتصوير', 'icon_key' => 'radiology', 'sort_order' => 20],
            ['name_en' => 'Anesthesiology', 'name_ar' => 'التخدير', 'icon_key' => 'anesthesia', 'sort_order' => 21],
            ['name_en' => 'ICU', 'name_ar' => 'العناية المركزة', 'icon_key' => 'icu', 'sort_order' => 22],
            ['name_en' => 'Physical Therapy', 'name_ar' => 'العلاج الطبيعي', 'icon_key' => 'physio', 'sort_order' => 23],
            ['name_en' => 'Dentistry', 'name_ar' => 'طب الأسنان', 'icon_key' => 'dentistry', 'sort_order' => 24],
            ['name_en' => 'Nutrition', 'name_ar' => 'التغذية', 'icon_key' => 'nutrition', 'sort_order' => 25],
            ['name_en' => 'Pharmacy', 'name_ar' => 'الصيدلية', 'icon_key' => 'pharmacy', 'sort_order' => 26],
            ['name_en' => 'Laboratory', 'name_ar' => 'المختبر', 'icon_key' => 'laboratory', 'sort_order' => 27],
            ['name_en' => 'Vaccination', 'name_ar' => 'التطعيمات', 'icon_key' => 'vaccination', 'sort_order' => 28],
            ['name_en' => 'Rheumatology', 'name_ar' => 'أمراض الروماتيزم', 'icon_key' => 'rheumatology', 'sort_order' => 29],
            ['name_en' => 'Infectious Diseases', 'name_ar' => 'الأمراض المعدية', 'icon_key' => 'infectious', 'sort_order' => 30],
            ['name_en' => 'Plastic Surgery', 'name_ar' => 'الجراحة التجميلية', 'icon_key' => 'plastic', 'sort_order' => 31],
            ['name_en' => 'Neurosurgery', 'name_ar' => 'جراحة المخ والأعصاب', 'icon_key' => 'neurosurgery', 'sort_order' => 32],
            ['name_en' => 'Cardiac Surgery', 'name_ar' => 'جراحة القلب', 'icon_key' => 'cardiac_surgery', 'sort_order' => 33],
            ['name_en' => 'Vascular Surgery', 'name_ar' => 'جراحة الأوعية الدموية', 'icon_key' => 'vascular', 'sort_order' => 34],
            ['name_en' => 'IT & Maintenance', 'name_ar' => 'تقنية المعلومات والصيانة', 'icon_key' => 'it', 'sort_order' => 99],
        ];

        foreach ($departments as $dept) {
            Department::updateOrCreate(
                ['slug' => Str::slug($dept['name_en'])],
                array_merge($dept, [
                    'is_active' => true,
                    'description_en' => null,
                    'description_ar' => null,
                ])
            );
        }
    }
}
