<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authorized_tokens', function (Blueprint $table): void {
            $table->id();
            $table->string('platform', 20);
            $table->string('label', 100);
            $table->text('token');
            $table->string('webhook_url', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['platform', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authorized_tokens');
    }
};
