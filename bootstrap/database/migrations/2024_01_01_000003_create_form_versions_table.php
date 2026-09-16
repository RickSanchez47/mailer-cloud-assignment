<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            // The entire dynamic field definition lives here as JSON.
            // Once published, a row is never mutated again — see
            // FormController::publish() and ARCHITECTURE.md.
            $table->json('schema');
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['form_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_versions');
    }
};
