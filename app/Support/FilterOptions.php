<?php

namespace App\Support;

use BackedEnum;

class FilterOptions
{
    /**
     * Options from $catalog that occur in $used, plus the value already selected.
     *
     * @param  array<string, mixed>  $catalog
     * @return array<string, mixed>
     */
    public static function present(array $catalog, iterable $used, mixed $selected = null): array
    {
        $usedValues = [];
        foreach ($used as $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $usedValues[] = $value instanceof BackedEnum ? (string) $value->value : (string) $value;
        }

        $selectedValue = $selected instanceof BackedEnum ? (string) $selected->value : (is_scalar($selected) ? (string) $selected : null);
        if ($selectedValue === null || $selectedValue === '' || $selectedValue === 'all') {
            $selectedValue = null;
        }

        $options = [];
        foreach ($catalog as $value => $label) {
            $key = (string) $value;
            if (in_array($key, $usedValues, true) || $key === $selectedValue) {
                $options[$key] = $label;
            }
        }

        return $options;
    }

    /**
     * @param  list<BackedEnum>  $cases
     * @return array<string, string>
     */
    public static function enums(array $cases, iterable $used, mixed $selected = null): array
    {
        $catalog = [];
        foreach ($cases as $case) {
            $catalog[(string) $case->value] = method_exists($case, 'label') ? $case->label() : $case->name;
        }

        return self::present($catalog, $used, $selected);
    }
}
