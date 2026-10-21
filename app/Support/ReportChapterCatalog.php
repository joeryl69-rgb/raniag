<?php

namespace App\Support;

use Illuminate\Support\Str;

class ReportChapterCatalog
{
    public const TYPES = [
        'open_cases',
        'response',
        'repeats',
        'shelter',
        'map',
        'papers',
        'texts',
        'support',
        'notices',
        'activity',
        'one_case',
    ];

    public const TITLES = [
        'open_cases' => 'Open cases',
        'response' => 'How the office answered',
        'repeats' => 'Repeat places',
        'shelter' => 'Shelter roll',
        'map' => 'Map as it stands',
        'papers' => 'Papers on closed cases',
        'texts' => 'Texts that failed',
        'support' => 'Support still waiting',
        'notices' => 'Notices posted',
        'activity' => 'What staff changed',
        'one_case' => 'One case',
    ];

    public const EXPORTABLE = ['open_cases', 'shelter', 'texts'];

    /** @var array<string, list<string>> */
    public const OPTIONS = [
        'open_cases' => ['aor_scope', 'barangay', 'agency_id', 'incident_type_id'],
        'response' => ['view_mode', 'compare', 'compare_from', 'compare_to'],
        'repeats' => [],
        'shelter' => ['guest_scope'],
        'map' => [],
        'papers' => ['document_type', 'completion'],
        'texts' => ['status'],
        'support' => ['status', 'category', 'source'],
        'notices' => ['published'],
        'activity' => ['log_name'],
        'one_case' => ['tracking_number'],
    ];

    /** @return list<array{key: string, type: string, options: array<string, mixed>}> */
    public static function starter(): array
    {
        return [
            self::chapter('open_cases'),
            self::chapter('shelter'),
            self::chapter('papers'),
        ];
    }

    /** @return list<array{key: string, type: string, options: array<string, mixed>}> */
    public static function operationsStarter(): array
    {
        return array_map(
            fn (string $type) => self::chapter($type),
            ['open_cases', 'shelter', 'papers', 'texts', 'support', 'notices', 'activity']
        );
    }

    /** @return array{key: string, type: string, options: array<string, mixed>} */
    public static function chapter(string $type): array
    {
        return [
            'key' => (string) Str::uuid(),
            'type' => $type,
            'options' => self::defaults($type),
        ];
    }

    /** @return array<string, mixed> */
    public static function defaults(string $type): array
    {
        return match ($type) {
            'open_cases' => [
                'aor_scope' => 'aor_only',
                'barangay' => null,
                'agency_id' => null,
                'incident_type_id' => null,
            ],
            'response' => [
                'view_mode' => 'weekly',
                'compare' => false,
                'compare_from' => null,
                'compare_to' => null,
            ],
            'shelter' => ['guest_scope' => 'still_in'],
            'papers' => ['document_type' => 'all', 'completion' => 'missing'],
            'texts' => ['status' => 'failed'],
            'support' => ['status' => 'new', 'category' => 'all', 'source' => 'all'],
            'notices' => ['published' => 'yes'],
            'activity' => ['log_name' => 'all'],
            'one_case' => ['tracking_number' => null],
            default => [],
        };
    }

    public static function title(string $type): string
    {
        return self::TITLES[$type] ?? 'Chapter';
    }
}
