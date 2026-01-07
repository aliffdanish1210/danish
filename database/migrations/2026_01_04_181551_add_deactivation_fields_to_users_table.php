<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeactivationFieldsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Track when user was deactivated
            $table->timestamp('deactivated_at')->nullable()->after('is_active');
            
            // Track who deactivated the user
            $table->unsignedBigInteger('deactivated_by')->nullable()->after('deactivated_at');
            
            // Track when user was activated (reactivated)
            $table->timestamp('activated_at')->nullable()->after('deactivated_by');
            
            // Track who activated the user
            $table->unsignedBigInteger('activated_by')->nullable()->after('activated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'deactivated_at',
                'deactivated_by',
                'activated_at',
                'activated_by'
            ]);
        });
    }
}