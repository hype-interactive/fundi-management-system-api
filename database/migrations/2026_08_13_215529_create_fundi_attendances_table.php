<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('fundi_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fundi_id')->constrained('fundis')->onDelete('cascade');
            $table->string('event_key')->index();
            $table->foreignId('operator_id')->nullable()->constrained('users')->onDelete('no action');
            $table->timestamp('scanned_at');
            $table->timestamps();

            $table->unique(['fundi_id', 'event_key']);
            $table->index(['event_key', 'id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('fundi_attendances');
    }
};
