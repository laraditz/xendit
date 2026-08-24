<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('xendit_api_logs', function (Blueprint $table) {
            $table->id();
            $table->string('method');
            $table->string('endpoint');
            $table->string('reference_id')->nullable()->index();

            // longText, not json — guards against a JSON-column strict-validation
            // error if a payload ever contains non-JSON-safe content.
            $table->longText('request_payload')->nullable();
            $table->longText('response_payload')->nullable();

            $table->unsignedSmallInteger('http_status')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();

            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('xendit_api_logs');
    }
};
