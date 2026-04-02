<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_mappings', function (Blueprint $table): void {
            $table->id();
            $table->string('source_platform', 20);
            $table->string('source_field', 100);
            $table->string('source_path', 255);
            $table->string('target_platform', 20);
            $table->string('target_field', 100);
            $table->string('target_path', 255);
            $table->string('transform_type', 30)->nullable();
            $table->json('transform_config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->string('description', 255)->nullable();
            $table->timestamps();

            $table->index(['source_platform', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_mappings');
    }
};
