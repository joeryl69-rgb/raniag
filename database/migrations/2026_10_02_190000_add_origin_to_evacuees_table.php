<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evacuees', function (Blueprint $table) {
            $table->string('origin_scope', 16)->default('inside')->after('barangay');
            $table->string('origin_place')->nullable()->after('origin_scope');
        });
    }

    public function down(): void
    {
        Schema::table('evacuees', function (Blueprint $table) {
            $table->dropColumn(['origin_scope', 'origin_place']);
        });
    }
};
