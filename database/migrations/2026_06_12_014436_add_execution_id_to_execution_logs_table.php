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
        Schema::table('execution_logs', function (Blueprint $table) {
            $table->string('execution_id')->nullable()->after('workflow_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('execution_logs', function (Blueprint $table) {
            $table->dropColumn('execution_id');
        });
    }
};
