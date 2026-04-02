<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_rules', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->string('event_type', 50);
            $table->string('condition_field', 100)->nullable();
            $table->string('condition_operator', 20)->default('equals');
            $table->string('condition_value', 255)->nullable();
            $table->foreignId('message_template_id')->nullable()->constrained('message_templates')->nullOnDelete();
            $table->text('message_template')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_rules');
    }
};
