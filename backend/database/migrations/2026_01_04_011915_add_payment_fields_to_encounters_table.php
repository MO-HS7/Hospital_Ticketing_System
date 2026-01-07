<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('encounters', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('payment_reference');
            $table->string('payment_gateway')->nullable()->after('payment_method');
            $table->timestamp('initiated_at')->nullable()->after('payment_gateway');
            $table->timestamp('paid_at')->nullable()->after('initiated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('encounters', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'payment_gateway', 'initiated_at', 'paid_at']);
        });
    }
};
