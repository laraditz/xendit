<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xendit_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('reference_id')->unique()->index();
            $table->string('payment_session_id')->nullable()->unique();
            $table->nullableMorphs('payable');

            $table->string('session_type')->nullable();
            $table->string('mode')->nullable();
            $table->tinyInteger('status')->nullable();

            $table->decimal('amount', 15, 2)->nullable();
            $table->char('currency', 3)->nullable();
            $table->char('country', 2)->nullable();
            $table->text('description')->nullable();

            $table->string('customer_id')->nullable();
            $table->json('customer')->nullable();

            $table->text('payment_link_url')->nullable();
            $table->text('components_sdk_key')->nullable();
            $table->string('success_return_url')->nullable();
            $table->string('cancel_return_url')->nullable();

            $table->string('payment_id')->nullable();
            $table->string('payment_token_id')->nullable();

            $table->json('metadata')->nullable();
            $table->json('session_details')->nullable();

            $table->timestamp('expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('canceled_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
            $table->index('session_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xendit_sessions');
    }
};
