<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('failed_webhooks', function (Blueprint $table) {
            $table->id();

            // Relacion con el log original
            $table->foreignId('webhook_log_id')
                ->constrained('webhook_logs')
                ->cascadeOnDelete();

            // Datos para reintento
            $table->string('direction', 50)
                ->comment('botmaker_to_bitrix | bitrix_to_botmaker');
            $table->json('payload')
                ->comment('Payload a reintentar (ya transformado)');
            $table->string('target_url', 500)->nullable()
                ->comment('URL destino del reintento');

            // Control de reintentos
            $table->unsignedTinyInteger('attempts')->default(0)
                ->comment('Numero de intentos realizados');
            $table->unsignedTinyInteger('max_attempts')->default(5)
                ->comment('Maximo de reintentos permitidos');
            $table->json('backoff_schedule')->nullable()
                ->comment('Segundos entre reintentos: [30, 60, 300, 900, 3600]');
            $table->timestamp('next_retry_at')->nullable()
                ->comment('Cuando se debe reintentar');

            // Resultado
            $table->text('last_error')->nullable()
                ->comment('Ultimo error registrado');
            $table->unsignedSmallInteger('last_http_status')->nullable()
                ->comment('Ultimo codigo HTTP recibido');
            $table->timestamp('resolved_at')->nullable()
                ->comment('Fecha de resolucion exitosa');

            // Estado
            $table->string('status', 20)->default('pending')
                ->comment('pending | retrying | resolved | exhausted');

            $table->timestamps();

            // Indices
            $table->index('status');
            $table->index('next_retry_at');
            $table->index(['status', 'next_retry_at']);
            $table->index('direction');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('failed_webhooks');
    }
};
