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
        Schema::create('communication_channels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('thesis_project_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['supervision', 'internal', 'external'])->default('supervision');
            $table->foreignUuid('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('communication_channels');
    }
};
