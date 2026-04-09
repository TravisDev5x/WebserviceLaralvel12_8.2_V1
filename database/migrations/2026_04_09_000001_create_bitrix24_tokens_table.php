<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bitrix24_tokens', function (Blueprint $table): void {
            $table->id();
            $table->string('domain', 255);
            $table->text('access_token');
            $table->text('refresh_token');
            $table->timestamp('expires_at')->nullable();
            $table->string('application_token', 255)->nullable();
            $table->string('client_id', 255)->nullable();
            $table->string('client_secret', 255)->nullable();
            $table->timestamps();

            $table->index('domain');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitrix24_tokens');
    }
};
