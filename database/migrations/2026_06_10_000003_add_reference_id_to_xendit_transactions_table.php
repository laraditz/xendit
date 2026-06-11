<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('xendit_transactions', function (Blueprint $table) {
            $table->string('reference_id')->nullable()->after('transaction_id')->index();
        });
    }

    public function down()
    {
        Schema::table('xendit_transactions', function (Blueprint $table) {
            $table->dropColumn('reference_id');
        });
    }
};
