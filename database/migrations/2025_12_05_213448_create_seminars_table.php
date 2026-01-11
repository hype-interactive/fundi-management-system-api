<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('seminars', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('agenda')->nullable();
            
            // Scheduling
            $table->enum('schedule_type', ['now', 'scheduled'])->default('scheduled');
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            
            // Status management
            $table->enum('status', ['draft', 'published', 'ongoing', 'completed', 'cancelled'])->default('draft');
                       
            // Metadata
            $table->foreignId('created_by')->constrained('users')->onDelete('no action');// Operator/Admin who created
            $table->integer('max_attendees')->nullable();
            $table->string('venue')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('status');
            $table->index('scheduled_at');
            
        });
    }

    public function down()
    {
        Schema::dropIfExists('seminars');
    }

};
