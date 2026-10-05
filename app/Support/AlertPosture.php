<?php

namespace App\Support;

class AlertPosture
{
    /**
     * Municipal alert posture shown on the public portal.
     * Keys match the four levels used by Philippine LGU emergency desks.
     *
     * @return array<string, array{label: string, tone: string}>
     */
    public static function options(): array
    {
        return [
            'normal' => ['label' => 'Normal', 'tone' => 'ok'],
            'monitoring' => ['label' => 'Monitoring', 'tone' => 'watch'],
            'blue' => ['label' => 'Blue Alert', 'tone' => 'blue'],
            'red' => ['label' => 'Red Alert', 'tone' => 'red'],
        ];
    }

    /**
     * @return array{key: string, label: string, tone: string}
     */
    public static function resolve(?string $key): array
    {
        $options = self::options();
        $key = array_key_exists((string) $key, $options) ? (string) $key : 'normal';

        return [
            'key' => $key,
            'label' => $options[$key]['label'],
            'tone' => $options[$key]['tone'],
        ];
    }
}
