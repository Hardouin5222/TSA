<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTsaSupplierSearchLogsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('bc_tsa_supplier_search_logs')) {
            return;
        }

        Schema::create('bc_tsa_supplier_search_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('search_uuid')->unique();
            $table->string('search_hash', 64)->index();
            $table->string('supplier_mode', 64)->nullable()->index();
            $table->string('supplier_code', 64)->nullable()->index();

            $table->string('origin', 8)->nullable()->index();
            $table->string('destination', 8)->nullable()->index();
            $table->date('departure_date')->nullable()->index();
            $table->date('return_date')->nullable()->index();
            $table->unsignedSmallInteger('adult_count')->default(1);
            $table->unsignedSmallInteger('child_count')->default(0);
            $table->unsignedSmallInteger('infant_count')->default(0);
            $table->string('cabin_class', 32)->nullable();

            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip_hash', 64)->nullable()->index();
            $table->string('session_hash', 64)->nullable()->index();

            $table->string('status', 32)->default('allowed')->index();
            $table->string('source', 32)->nullable()->index();
            $table->unsignedInteger('offers_count')->default(0);
            $table->unsignedInteger('duration_ms')->nullable();

            $table->json('criteria_json')->nullable();
            $table->json('guard_context_json')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamp('booked_at')->nullable()->index();
            $table->unsignedBigInteger('booking_id')->nullable()->index();

            $table->timestamps();

            $table->index(['search_hash', 'created_at'], 'bc_tsa_search_hash_created_idx');
            $table->index(['ip_hash', 'created_at'], 'bc_tsa_search_ip_created_idx');
            $table->index(['session_hash', 'created_at'], 'bc_tsa_search_session_created_idx');
            $table->index(['supplier_mode', 'created_at'], 'bc_tsa_search_mode_created_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('bc_tsa_supplier_search_logs');
    }
}
