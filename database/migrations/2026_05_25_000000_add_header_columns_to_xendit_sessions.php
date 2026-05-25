<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('xendit_sessions', function (Blueprint $table) {
            $table->string('for_user_id')->nullable()->after('session_details');
            $table->string('split_rule_id')->nullable()->after('for_user_id');
            $table->index('for_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('xendit_sessions', function (Blueprint $table) {
            $table->dropIndex(['for_user_id']);
            $table->dropColumn(['for_user_id', 'split_rule_id']);
        });
    }
};
