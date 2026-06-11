<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('xendit_transactions', function (Blueprint $table) {
            $table->dropIndex(['payment_id', 'type']);
            $table->dropIndex(['payment_id']);
            $table->dropColumn('payment_id');
        });

        Schema::table('xendit_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('source_id')->nullable()->after('reference_id');
            $table->string('source_type')->nullable()->after('source_id');
            $table->index(['source_type', 'source_id']);
            $table->index('type');
        });
    }

    public function down()
    {
        Schema::table('xendit_transactions', function (Blueprint $table) {
            $table->dropIndex(['source_type', 'source_id']);
            $table->dropIndex(['type']);
            $table->dropColumn(['source_id', 'source_type']);
        });

        Schema::table('xendit_transactions', function (Blueprint $table) {
            $table->foreignId('payment_id')->nullable()->after('id')->index();
            $table->index(['payment_id', 'type']);
        });
    }
};
