<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            // Denormalized alongside form_id so tenant-scoped queries
            // (dashboard, export, isolation checks) never need a join.
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();
            $table->foreignId('form_version_id')->constrained()->cascadeOnDelete();
            $table->json('data');
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 512)->nullable();
            // Append-only: no updated_at. created_at is set explicitly.
            $table->timestamp('created_at')->useCurrent();

            $table->index(['form_id', 'created_at']);
            $table->index(['account_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
