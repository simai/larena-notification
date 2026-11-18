<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What a notification is about (a site, «Сервер») for the bell's groups and the history's filter, and its end: a problem
 * and its «решено» are one record (study 2026-11-17, PX-40), so the record keeps when it was resolved and how it ended.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('larena_notifications', static function (Blueprint $table): void {
            $table->string('object', 120)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->string('resolution', 200)->nullable();
            $table->index(['recipient_ref', 'object']);
        });
    }

    public function down(): void
    {
        Schema::table('larena_notifications', static function (Blueprint $table): void {
            $table->dropIndex(['recipient_ref', 'object']);
            $table->dropColumn(['object', 'resolved_at', 'resolution']);
        });
    }
};
