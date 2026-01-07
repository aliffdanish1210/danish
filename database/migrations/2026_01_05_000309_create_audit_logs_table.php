<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAuditLogsTable extends Migration
{
    public function up()
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // User information (NO FK)
            $table->string('user_id')->nullable(); // application-level ID
            $table->string('user_name')->nullable();
            $table->string('user_email')->nullable();

            // Activity
            $table->string('event_type', 50);
            $table->string('action', 100);
            $table->text('description')->nullable();

            // Security
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('device_type', 50)->nullable();
            $table->string('browser', 100)->nullable();
            $table->string('platform', 100)->nullable();

            // Risk
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->enum('status', ['success', 'failed', 'suspicious', 'blocked'])->default('success');

            // Metadata
            $table->json('metadata')->nullable();

            // Location
            $table->string('country', 100)->nullable();
            $table->string('city', 100)->nullable();

            // Timestamp
            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('user_id');
            $table->index('event_type');
            $table->index('action');
            $table->index('severity');
            $table->index('status');
            $table->index('ip_address');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('audit_logs');
    }
}
