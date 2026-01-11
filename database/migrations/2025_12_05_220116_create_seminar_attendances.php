<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
       public function up()
    {
        Schema::create('seminar_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seminar_id')->constrained('seminars')->onDelete('cascade');
            $table->foreignId('fundi_id')->constrained('fundis')->onDelete('no action');
            $table->foreignId('operator_id')->constrained('users')->onDelete('no action');
            
            // Registration details
            $table->dateTime('registered_at');
            $table->enum('registration_method',['qr_scan','manual'])->default('qr_scan'); // qr_scan, manual
            
            // Attendance tracking
            $table->dateTime('check_in_time')->nullable();
            $table->dateTime('check_out_time')->nullable();
            
            
            $table->timestamps();
            $table->softDeletes();
            
            
            // Indexes
            $table->index('seminar_id');
            $table->index('fundi_id');
            $table->index('registered_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('seminar_attendances');
    }
};
