<?php

namespace App\Enums;

enum IncidentDocumentType: string
{
    case CallTakerForm = 'call_taker_form';
    case DispatchForm = 'dispatch_form';
    case NarrativeReport = 'narrative_report';
    case EndorsementSheet = 'endorsement_sheet';

    public function label(): string
    {
        return match ($this) {
            self::CallTakerForm => 'Call Taker Form',
            self::DispatchForm => 'Dispatch Form',
            self::NarrativeReport => 'Narrative Report',
            self::EndorsementSheet => 'Endorsement Sheet',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
