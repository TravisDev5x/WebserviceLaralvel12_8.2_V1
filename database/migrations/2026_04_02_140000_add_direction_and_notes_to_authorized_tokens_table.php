<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('authorized_tokens', function (Blueprint $table): void {
            $table->string('direction', 20)->nullable()->after('webhook_url');
            $table->text('notes')->nullable()->after('last_used_at');
        });

        if (! Schema::hasTable('authorized_tokens')) {
            return;
        }

        $rows = DB::table('authorized_tokens')->orderBy('id')->get();

        foreach ($rows as $row) {
            $url = trim((string) ($row->webhook_url ?? ''));
            $token = trim((string) ($row->token ?? ''));

            if ($url !== '' && $token !== '') {
                DB::table('authorized_tokens')->insert([
                    'platform' => $row->platform,
                    'label' => $row->label.' (webhook saliente)',
                    'token' => $token,
                    'webhook_url' => null,
                    'direction' => 'outgoing',
                    'is_active' => (bool) $row->is_active,
                    'last_used_at' => $row->last_used_at,
                    'notes' => $row->notes ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('authorized_tokens')->where('id', $row->id)->update([
                    'token' => '',
                    'direction' => 'incoming',
                    'updated_at' => now(),
                ]);

                continue;
            }

            if ($url !== '') {
                DB::table('authorized_tokens')->where('id', $row->id)->update([
                    'direction' => 'incoming',
                    'updated_at' => now(),
                ]);

                continue;
            }

            DB::table('authorized_tokens')->where('id', $row->id)->update([
                'direction' => 'outgoing',
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('authorized_tokens', function (Blueprint $table): void {
            $table->dropColumn(['direction', 'notes']);
        });
    }
};
