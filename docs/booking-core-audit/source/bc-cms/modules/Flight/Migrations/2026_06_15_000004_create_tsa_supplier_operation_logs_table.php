<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bc_tsa_supplier_operation_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('booking_id')->nullable()->index();
            $table->unsignedBigInteger('quote_id')->nullable()->index();
            $table->uuid('quote_uuid')->nullable()->index();
            $table->string('supplier_code', 64)->nullable()->index();
            $table->string('operation', 64)->index();
            $table->string('status', 32)->index();
            $table->string('normalized_error_code', 64)->nullable()->index();
            $table->text('supplier_error_raw')->nullable();
            $table->json('request_json')->nullable();
            $table->json('response_json')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->uuid('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->foreign('booking_id')->references('id')->on('bc_bookings')->nullOnDelete();
            $table->foreign('quote_id')->references('id')->on('bc_tsa_supplier_quotes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bc_tsa_supplier_operation_logs');
    }
};
