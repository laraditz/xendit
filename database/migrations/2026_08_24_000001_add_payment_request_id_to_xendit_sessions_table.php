<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('xendit_sessions', function (Blueprint $table) {
            $table->string('payment_request_id')->nullable()->after('payment_token_id');
        });
    }

    public function down(): void
    {
        Schema::table('xendit_sessions', function (Blueprint $table) {
            $table->dropColumn('payment_request_id');
        });
    }
};
