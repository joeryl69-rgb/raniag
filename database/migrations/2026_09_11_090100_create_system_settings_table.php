<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Single-row settings table (see App\Models\SystemSetting::current()),
     * mirroring the "one settings record for the whole app" pattern.
     * Backs the admin Theme/System Settings screen: which preset theme is
     * active, and whether dark mode is on — applied globally via CSS
     * custom-property overrides injected in layouts/app.blade.php.
     */
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('theme_key', 32)->default('ocean');
            $table->boolean('dark_mode')->default(false);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
