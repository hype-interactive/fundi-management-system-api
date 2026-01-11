<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('seminar_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seminar_id')->constrained('seminars')->onDelete('no action');

            
            // Notification details
            $table->enum('type', ['sms', 'push', 'other'])->default('sms');
            $table->text('message');
            $table->json('recipient_filters')->nullable(); // Location filters used
            
            // Sending status
            $table->enum('status', ['pending', 'processing', 'sent', 'failed'])->default('pending');
            $table->integer('total_recipients')->default(0);
            $table->integer('sent_count')->default(0);
            $table->integer('failed_count')->default(0);
            
            
            // Scheduling
            $table->dateTime('scheduled_for')->nullable();
            $table->dateTime('sent_at')->nullable();
            
            // Metadata
            $table->foreignId('sent_by')->constrained('users')->onDelete('no action');
            $table->text('failure_reason')->nullable();
            
            $table->timestamps();

            
            // Indexes
            $table->index('seminar_id');
            $table->index('status');
            $table->index('scheduled_for');
        });
    }

    public function down()
    {
        Schema::dropIfExists('seminar_notifications');
    }
};
