<?php

namespace App\Enums;

enum EvidenceType: string
{
    case Photo = 'photo';
    case Video = 'video';
    case Document = 'document';
    case Audio = 'audio';

    public function label(): string
    {
        return match ($this) {
            self::Photo => 'Photo',
            self::Video => 'Video',
            self::Document => 'Document',
            self::Audio => 'Audio',
        };
    }
}
