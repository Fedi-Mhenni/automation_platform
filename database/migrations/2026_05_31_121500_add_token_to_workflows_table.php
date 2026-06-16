<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('workflows', 'token')) {
            Schema::table('workflows', function (Blueprint $table) {
                $table->uuid('token')->unique()->after('id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('workflows', 'token')) {
            Schema::table('workflows', function (Blueprint $table) {
                $table->dropColumn('token');
            });
        }
    }
};
