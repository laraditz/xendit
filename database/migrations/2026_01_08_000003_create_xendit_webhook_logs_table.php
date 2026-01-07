<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('xendit_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_type'); // invoice.paid, ewallet.charge.succeeded, etc
            $table->string('external_id')->nullable()->index();
            $table->string('xendit_id')->nullable()->index();
            $table->json('payload'); // complete webhook payload
            $table->tinyInteger('status')->nullable(); // Will be set by model observer
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            // Indexes for efficient queries
            $table->index(['event_type', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('xendit_webhook_logs');
    }
};
