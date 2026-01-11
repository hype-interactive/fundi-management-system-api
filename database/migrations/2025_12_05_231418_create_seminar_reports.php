<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('seminar_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seminar_id')->constrained('seminars')->onDelete('cascade');

            
            // Attendance statistics
            $table->integer('total_registered')->default(0);
            $table->integer('total_attended')->default(0);
            $table->integer('total_absent')->default(0);
            
            
            // Location breakdown
            $table->json('attendance_by_region')->nullable();
            $table->json('attendance_by_district')->nullable();
            
            // Time tracking
            $table->integer('average_attendance_duration')->nullable(); // in minutes
            
            $table->timestamp('generated_at');
            $table->timestamps();
            
            // Indexes
            $table->index('seminar_id');
            $table->index('generated_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('seminar_reports');
    }
};
