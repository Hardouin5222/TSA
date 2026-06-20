<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bc_tsa_supplier_quotes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('quote_uuid')->unique();
            $table->unsignedBigInteger('offer_id')->index();
            $table->uuid('offer_uuid')->index();
            $table->string('selected_fare_id', 191)->nullable();
            $table->string('supplier_code', 64)->index();
            $table->string('confirmed_currency', 8)->default('TRY');
            $table->decimal('confirmed_total_amount', 14, 2)->default(0);
            $table->boolean('price_changed')->default(false);
            $table->json('requirements_json')->nullable();
            $table->json('rules_json')->nullable();
            $table->json('payload_json')->nullable();
            $table->dateTime('expires_at')->nullable()->index();
            $table->string('status', 32)->default('quoted')->index();
            $table->timestamps();

            $table->foreign('offer_id')->references('id')->on('bc_tsa_supplier_offers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bc_tsa_supplier_quotes');
    }
};
