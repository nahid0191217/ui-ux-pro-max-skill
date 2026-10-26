<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for `consultations` (Telemedicine) table (MySQL).
     */
    public function up(): void
    {
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('patient_name');
            $table->string('phone', 25)->index();
            $table->string('email')->nullable();

            // Pre-travel evaluation vs Post-return follow-up consultation
            $table->enum('consultation_stage', ['pre_travel', 'post_return'])
                ->default('pre_travel')
                ->index();
            $table->string('department')->nullable();
            $table->string('preferred_doctor')->nullable();
            $table->dateTime('preferred_date')->index();
            $table->string('preferred_time_slot', 50)->nullable();
            $table->text('symptoms_notes')->nullable();
            $table->string('medical_report_path')->nullable();

            $table->enum('status', [
                'pending',
                'scheduled',
                'completed',
                'cancelled',
            ])->default('pending')->index();

            $table->string('meeting_link', 500)->nullable()->comment('Zoom / Google Meet video conference link');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};
