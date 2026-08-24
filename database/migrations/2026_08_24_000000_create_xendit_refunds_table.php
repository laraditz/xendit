<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('xendit_refunds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_id')->nullable()->index();
            $table->string('refund_id')->unique(); // Xendit refund ID
            $table->string('payment_request_id')->index();
            $table->string('reference_id')->nullable();
            $table->string('currency', 3)->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('status');
            $table->string('reason')->nullable();
            $table->string('failure_code')->nullable();
            $table->decimal('refund_fee_amount', 15, 2)->nullable();
            $table->json('metadata')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('xendit_refunds');
    }
};
