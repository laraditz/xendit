<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xendit_customers', function (Blueprint $table) {
            $table->id();
            $table->string('reference_id')->unique()->index();
            $table->string('xendit_id')->nullable()->unique();
            $table->string('type')->nullable();

            $table->string('email')->nullable();
            $table->string('mobile_number')->nullable();
            $table->string('phone_number')->nullable();

            $table->json('individual_detail')->nullable();
            $table->json('business_detail')->nullable();
            $table->json('addresses')->nullable();
            $table->json('kyc_documents')->nullable();
            $table->json('identity_accounts')->nullable();
            $table->json('metadata')->nullable();
            $table->json('customer_details')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xendit_customers');
    }
};
