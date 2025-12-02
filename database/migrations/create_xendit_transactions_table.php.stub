<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('xendit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->index();
            $table->string('transaction_id')->unique()->index(); // Xendit transaction ID
            $table->string('type'); // payment, refund, adjustment
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->nullable(); // Will be set by model observer
            $table->tinyInteger('status')->nullable(); // Will be set by model observer
            $table->string('payment_method')->nullable();

            // Financial details
            $table->decimal('fee', 15, 2)->default(0); // transaction fee charged by Xendit
            $table->decimal('net_amount', 15, 2); // amount - fee

            // Store complete API response for debugging/audit
            $table->json('raw_response')->nullable();

            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['payment_id', 'type']);
            $table->index(['status', 'completed_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('xendit_transactions');
    }
};
