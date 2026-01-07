<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    }

    /**
     * Test admin can list users with department information.
     */
    public function test_users_list_includes_department(): void
    {
        // Create admin
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Create department
        $department = Department::factory()->create([
            'name_en' => 'Cardiology',
            'name_ar' => 'أمراض القلب',
        ]);

        // Create a doctor with department
        $doctor = User::factory()->create([
            'department_id' => $department->id,
        ]);
        $doctor->assignRole('doctor');

        // Make request as admin
        $response = $this->actingAs($admin)->getJson('/api/users');

        $response->assertStatus(200);
        
        // Check that department is included in response
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'email',
                    'roles',
                    'department',
                ],
            ],
        ]);

        // Find the doctor in response and verify department
        $responseData = $response->json('data');
        $doctorData = collect($responseData)->firstWhere('email', $doctor->email);
        
        $this->assertNotNull($doctorData);
        $this->assertIsArray($doctorData['department']);
        $this->assertEquals('Cardiology', $doctorData['department']['name_en']);
        $this->assertEquals('أمراض القلب', $doctorData['department']['name_ar']);
    }

    /**
     * Test doctors list by department returns correct results.
     */
    public function test_doctors_list_by_department(): void
    {
        // Create admin
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Create departments
        $cardiology = Department::factory()->create(['name_en' => 'Cardiology']);
        $neurology = Department::factory()->create(['name_en' => 'Neurology']);

        // Create doctors in cardiology
        $doctor1 = User::factory()->create(['department_id' => $cardiology->id]);
        $doctor1->assignRole('doctor');
        
        $doctor2 = User::factory()->create(['department_id' => $cardiology->id]);
        $doctor2->assignRole('doctor');

        // Create doctor in neurology
        $doctor3 = User::factory()->create(['department_id' => $neurology->id]);
        $doctor3->assignRole('doctor');

        // Request doctors in cardiology only
        $response = $this->actingAs($admin)->getJson("/api/staff/doctors?department_id={$cardiology->id}");

        $response->assertStatus(200);
        
        $data = $response->json();
        
        // Should return exactly 2 doctors (from cardiology)
        $this->assertCount(2, $data);
        
        // Verify all returned doctors are from cardiology
        foreach ($data as $doctor) {
            $this->assertEquals($cardiology->id, $doctor['department_id']);
        }
    }

    /**
     * Test doctors endpoint returns array directly (not wrapped in data key).
     */
    public function test_doctors_endpoint_returns_array_directly(): void
    {
        $patient = User::factory()->create();
        $patient->assignRole('patient');

        $department = Department::factory()->create();
        $doctor = User::factory()->create(['department_id' => $department->id]);
        $doctor->assignRole('doctor');

        $response = $this->actingAs($patient)->getJson("/api/staff/doctors?department_id={$department->id}");

        $response->assertStatus(200);
        
        // Response should be an array, not { data: [...] }
        $data = $response->json();
        $this->assertIsArray($data);
        $this->assertArrayNotHasKey('data', $data);
        $this->assertCount(1, $data);
        $this->assertEquals($doctor->id, $data[0]['id']);
    }
}
