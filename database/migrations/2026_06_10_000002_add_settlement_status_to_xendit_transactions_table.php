<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('xendit_transactions', function (Blueprint $table) {
            $table->string('settlement_status')->nullable()->after('net_amount');
            $table->timestamp('settled_at')->nullable()->after('completed_at');
        });
    }

    public function down()
    {
        Schema::table('xendit_transactions', function (Blueprint $table) {
            $table->dropColumn(['settlement_status', 'settled_at']);
        });
    }
};
