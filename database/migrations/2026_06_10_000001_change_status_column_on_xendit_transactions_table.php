<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('xendit_transactions', function (Blueprint $table) {
            $table->dropIndex(['status', 'completed_at']);
            $table->dropColumn('status');
        });

        Schema::table('xendit_transactions', function (Blueprint $table) {
            $table->string('status')->nullable()->after('type');
            $table->index(['status', 'completed_at']);
        });
    }

    public function down()
    {
        Schema::table('xendit_transactions', function (Blueprint $table) {
            $table->dropIndex(['status', 'completed_at']);
            $table->dropColumn('status');
        });

        Schema::table('xendit_transactions', function (Blueprint $table) {
            $table->tinyInteger('status')->nullable()->after('type');
            $table->index(['status', 'completed_at']);
        });
    }
};
