<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('evacuees')
            ->select(['id', 'full_name'])
            ->where('is_demo', true)
            ->where('full_name', 'like', '[DEMO] %')
            ->orderBy('id')
            ->chunkById(100, function ($records) {
                foreach ($records as $record) {
                    DB::table('evacuees')
                        ->where('id', $record->id)
                        ->update(['full_name' => substr($record->full_name, 7)]);
                }
            });

        DB::table('evacuees')
            ->where('is_demo', true)
            ->where('vulnerability_notes', 'like', 'Demo record%')
            ->update([
                'is_vulnerable' => false,
                'vulnerability_notes' => null,
            ]);
    }

    public function down(): void
    {
    }
};
