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
        // 1. Create the recipients pivot table
        Schema::create('inbox_message_recipients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('inbox_message_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->string('recipient_type')->default('to'); // to, cc, bcc
            $table->timestamp('read_at')->nullable();
            $table->boolean('is_starred')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'is_archived']);
            $table->index(['inbox_message_id', 'recipient_type']);
        });

        // 2. Remove legacy columns from inbox_messages
        Schema::table('inbox_messages', function (Blueprint $table) {
            $table->dropColumn(['recipient_id', 'read_at', 'starred_by_recipient', 'archived_by_recipient']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inbox_messages', function (Blueprint $table) {
            $table->uuid('recipient_id')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->boolean('starred_by_recipient')->default(false);
            $table->boolean('archived_by_recipient')->default(false);
        });

        Schema::dropIfExists('inbox_message_recipients');
    }
};
