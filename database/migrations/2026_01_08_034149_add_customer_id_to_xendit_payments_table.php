<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('xendit_payments', function (Blueprint $table) {
            $table->after('description', function ($table) {
                $table->string('customer_id')->nullable();
                $table->string('payer_id')->nullable();
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('xendit_payments', function (Blueprint $table) {
            $table->dropColumn(['customer_id', 'payer_id']);
        });
    }
};
