<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bc_tsa_supplier_offers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('offer_uuid')->unique();
            $table->string('supplier_code', 64)->index();
            $table->string('supplier_offer_id', 191)->nullable()->index();
            $table->string('origin', 16)->index();
            $table->string('destination', 16)->index();
            $table->dateTime('departure_at')->nullable()->index();
            $table->dateTime('arrival_at')->nullable();
            $table->string('currency', 8)->default('TRY');
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->json('payload_json')->nullable();
            $table->json('supplier_context_json')->nullable();
            $table->dateTime('expires_at')->nullable()->index();
            $table->string('status', 32)->default('search_selected')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bc_tsa_supplier_offers');
    }
};
