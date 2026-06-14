<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // replace all table prefix start with bravo_ to bc_
        $tables = DB::select('SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ?', [config('database.connections.mysql.database')]);
        foreach ($tables as $table) {
            $tableName = $table->TABLE_NAME;
            if (strpos($tableName, 'bravo_') === 0) {
                $newTableName = 'bc_' . substr($tableName, 6);
                if (Schema::hasTable($newTableName)) {
                    continue;
                }
                DB::statement("RENAME TABLE $tableName TO $newTableName");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
