<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAuditColumnsToTsaSupplierTables extends Migration
{
    protected array $tables = [
        'bc_tsa_supplier_offers',
        'bc_tsa_supplier_quotes',
        'bc_tsa_supplier_bookings',
        'bc_tsa_supplier_operation_logs',
    ];

    public function up()
    {
        foreach ($this->tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'create_user')) {
                    $table->unsignedBigInteger('create_user')->nullable()->after('id');
                }

                if (!Schema::hasColumn($tableName, 'update_user')) {
                    $table->unsignedBigInteger('update_user')->nullable()->after('create_user');
                }
            });
        }
    }

    public function down()
    {
        foreach (array_reverse($this->tables) as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'update_user')) {
                    $table->dropColumn('update_user');
                }

                if (Schema::hasColumn($tableName, 'create_user')) {
                    $table->dropColumn('create_user');
                }
            });
        }
    }
}
