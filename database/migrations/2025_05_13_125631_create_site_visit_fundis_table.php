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
        Schema::create('site_visit_fundis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_visit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fundi_id')->constrained();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_visit_fundis');
    }
};
