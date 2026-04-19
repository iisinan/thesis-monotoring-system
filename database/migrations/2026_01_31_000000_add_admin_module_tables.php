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
        // 1. Announcements Table
        Schema::create('announcements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('content');
            $table->string('type')->default('info');
            $table->string('target_role')->nullable(); // null = all
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->foreignUuid('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        // 2. Document Templates Table
        Schema::create('document_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title'); // e.g., "Proposal Format"
            $table->string('file_path');
            $table->string('type'); // proposal, seminar, defence, other
            $table->string('version')->default('1.0');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. User Locking & Active Status (Adding columns)
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('locked_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('document_templates');
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['locked_at', 'is_active', 'last_login_at']);
        });
    }
};
