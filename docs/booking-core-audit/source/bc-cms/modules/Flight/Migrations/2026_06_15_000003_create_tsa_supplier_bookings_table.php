<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bc_tsa_supplier_bookings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('booking_id')->unique();
            $table->unsignedBigInteger('quote_id')->index();
            $table->uuid('quote_uuid')->index();
            $table->string('supplier_code', 64)->index();
            $table->string('supplier_booking_reference', 191)->nullable()->index();
            $table->string('pnr', 64)->nullable()->index();
            $table->json('ticket_numbers_json')->nullable();
            $table->string('payment_status', 32)->default('unpaid')->index();
            $table->string('fulfillment_status', 48)->default('not_started')->index();
            $table->boolean('manual_review_required')->default(false)->index();
            $table->json('snapshot_json')->nullable();
            $table->timestamps();

            $table->foreign('booking_id')->references('id')->on('bc_bookings')->cascadeOnDelete();
            $table->foreign('quote_id')->references('id')->on('bc_tsa_supplier_quotes')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bc_tsa_supplier_bookings');
    }
};
