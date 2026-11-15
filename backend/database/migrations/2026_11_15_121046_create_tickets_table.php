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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('creator_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('assignee_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('category_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('title', 120);
            $table->text('description');

            $table->string('status', 20)->default('open');
            $table->string('priority', 20)->default('medium');

            $table->date('due_date')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'status', 'created_at']);
            $table->index(['company_id', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};