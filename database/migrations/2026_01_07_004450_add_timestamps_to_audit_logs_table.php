<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTimestampsToAuditLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
public function up()
{
    Schema::table('audit_logs', function (Blueprint $table) {
        // Only add updated_at because created_at exists
        $table->timestamp('updated_at')->nullable();
    });
}

public function down()
{
    Schema::table('audit_logs', function (Blueprint $table) {
        $table->dropColumn('updated_at');
    });
}
}
