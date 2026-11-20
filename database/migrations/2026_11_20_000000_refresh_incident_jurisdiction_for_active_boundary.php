<?php

use App\Services\GeofenceService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $geofence = app(GeofenceService::class);
        $geofence->clearBoundaryCache();

        DB::transaction(fn () => $geofence->refreshIncidentJurisdictions());
    }

    public function down(): void {}
};
