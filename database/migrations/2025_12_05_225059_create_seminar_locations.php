<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
     public function up()
    {
        Schema::create('seminar_locations', function (Blueprint $table) {

            $table->id();
            $table->foreignId('seminar_id')->constrained('seminars')->onDelete('cascade');
            $table->foreignId('region_id')->constrained('regions')->onDelete('cascade');
            $table->foreignId('district_id')->constrained('districts')->onDelete('cascade');
            
            $table->timestamps();
            
            // Indexes
            $table->index('seminar_id');
            $table->index(['region_id', 'district_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('seminar_locations');
    }
};
