<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->string('system_name', 80)->nullable();
            $table->string('organization_name', 160)->nullable();
            $table->string('logo_path')->nullable();
            $table->string('primary_color', 7)->default('#086b78');
            $table->string('accent_color', 7)->default('#29aa96');
            $table->string('page_background_color', 7)->default('#eaf0f2');
            $table->string('header_text', 180)->nullable();
            $table->string('footer_text', 500)->nullable();
            $table->boolean('show_header_text')->default(true);
            $table->boolean('show_footer_text')->default(true);
            $table->string('contact_address')->nullable();
            $table->string('contact_phone', 40)->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_hotline', 40)->nullable();
            $table->string('municipality_label', 120)->nullable();
            $table->string('province_label', 120)->nullable();
            $table->string('response_area_label', 120)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn([
                'system_name',
                'organization_name',
                'logo_path',
                'primary_color',
                'accent_color',
                'page_background_color',
                'header_text',
                'footer_text',
                'show_header_text',
                'show_footer_text',
                'contact_address',
                'contact_phone',
                'contact_email',
                'contact_hotline',
                'municipality_label',
                'province_label',
                'response_area_label',
            ]);
        });
    }
};
