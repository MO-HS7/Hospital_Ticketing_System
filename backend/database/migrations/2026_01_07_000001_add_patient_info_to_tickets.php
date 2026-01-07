<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds patient information fields to tickets table for Step 3 enhancement:
     * - Patient demographics (age, gender)
     * - Contact information (method, phone)
     * - Medical context (emergency, conditions, notes)
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Patient Demographics
            $table->unsignedTinyInteger('patient_age')->nullable()->after('description');
            $table->enum('patient_gender', ['male', 'female'])->nullable()->after('patient_age');
            
            // Contact Information
            $table->enum('contact_method', ['phone', 'whatsapp', 'sms', 'in_app'])->nullable()->after('patient_gender');
            $table->string('contact_phone', 20)->nullable()->after('contact_method');
            
            // Medical Context
            $table->boolean('is_emergency')->default(false)->after('contact_phone');
            $table->text('medical_conditions')->nullable()->after('is_emergency');
            $table->text('additional_notes')->nullable()->after('medical_conditions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn([
                'patient_age',
                'patient_gender',
                'contact_method',
                'contact_phone',
                'is_emergency',
                'medical_conditions',
                'additional_notes',
            ]);
        });
    }
};
