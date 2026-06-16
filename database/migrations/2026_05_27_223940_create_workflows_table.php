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
    Schema::create('workflows', function (Blueprint $table) {
        $table->id();
        $table->string('name'); // Le nom du workflow (
        $table->foreignId('user_id')->constrained()->onDelete('cascade'); 
        $table->boolean('is_active')->default(false); // Statut (Actif / Inactif)
        $table->timestamps(); // create_at et updated_at d'office
        $table->json('nodes_structure')->nullable();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflows');
    }
};
