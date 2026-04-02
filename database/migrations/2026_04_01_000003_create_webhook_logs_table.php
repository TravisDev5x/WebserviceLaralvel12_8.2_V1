<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();

            // Direccion del flujo
            $table->string('direction', 50)
                ->comment('botmaker_to_bitrix | bitrix_to_botmaker');

            // Identificadores de correlacion
            $table->string('correlation_id', 64)->unique()
                ->comment('UUID generado por el middleware para rastreo');
            $table->string('external_id', 128)->nullable()
                ->comment('ID del mensaje/evento en la plataforma origen');

            // Evento y payload
            $table->string('source_event', 100)
                ->comment('Tipo de evento: new_message, lead_update, etc.');
            $table->json('payload_in')
                ->comment('JSON crudo recibido del webhook origen');
            $table->json('payload_out')->nullable()
                ->comment('JSON transformado enviado al destino');

            // Resultado del envio
            $table->unsignedSmallInteger('http_status')->nullable()
                ->comment('Codigo HTTP de respuesta del destino');
            $table->text('response_body')->nullable()
                ->comment('Cuerpo de respuesta del destino');

            // Estado de procesamiento
            $table->string('status', 20)->default('received')
                ->comment('received | processing | sent | failed');
            $table->unsignedInteger('processing_ms')->nullable()
                ->comment('Tiempo de procesamiento en milisegundos');

            // Error
            $table->text('error_message')->nullable()
                ->comment('Mensaje de error si fallo');
            $table->string('error_type', 50)->nullable()
                ->comment('timeout | validation | auth | server_error | unknown');

            // Metadata
            $table->string('source_ip', 45)->nullable()
                ->comment('IP de origen del webhook');
            $table->string('user_agent', 255)->nullable()
                ->comment('User-Agent del request entrante');

            $table->timestamps();

            // Indices para consultas frecuentes
            $table->index('direction');
            $table->index('status');
            $table->index('source_event');
            $table->index('created_at');
            $table->index(['direction', 'status']);
            $table->index('external_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
