<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('larena_notifications', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('recipient_ref', 191);
            $table->string('status', 16);
            $table->string('title', 200);
            $table->text('text')->nullable();
            $table->string('source', 120);
            $table->string('href', 500)->nullable();
            $table->string('dedupe_key', 191)->nullable();
            $table->unsignedInteger('repeat_count')->default(1);
            $table->timestamp('created_at');
            $table->timestamp('shown_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->index(['recipient_ref', 'created_at']);
            $table->index(['recipient_ref', 'read_at']);
            $table->index(['recipient_ref', 'dedupe_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('larena_notifications');
    }
};
