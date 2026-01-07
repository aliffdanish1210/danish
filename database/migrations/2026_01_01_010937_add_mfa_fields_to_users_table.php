<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMfaFieldsToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('mfa_enabled')->default(false)->after('password');
            $table->enum('mfa_method', ['email', 'sms', 'authenticator'])->default('email')->after('mfa_enabled');
            $table->string('mfa_phone')->nullable()->after('mfa_method');
            $table->text('mfa_secret')->nullable()->after('mfa_phone'); // For authenticator apps
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['mfa_enabled', 'mfa_method', 'mfa_phone', 'mfa_secret']);
        });
    }
}