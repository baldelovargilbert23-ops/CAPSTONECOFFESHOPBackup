<?php

namespace App\Support;

/**
 * Converts between compatible units (g<->kg, ml<->L, pcs).
 * Everything is converted through a "base" unit: g, ml or pcs.
 */
class UnitConverter
{
    public const UNITS = [
        'g'   => ['group' => 'weight', 'factor' => 1,    'label' => 'g — grams'],
        'kg'  => ['group' => 'weight', 'factor' => 1000, 'label' => 'kg — kilograms'],
        'ml'  => ['group' => 'volume', 'factor' => 1,    'label' => 'ml — milliliters'],
        'L'   => ['group' => 'volume', 'factor' => 1000, 'label' => 'L — liters'],
        'pcs' => ['group' => 'count',  'factor' => 1,    'label' => 'pcs — pieces'],
    ];

    public static function isValid(?string $unit): bool
    {
        return $unit !== null && isset(self::UNITS[$unit]);
    }

    public static function group(?string $unit): ?string
    {
        return self::UNITS[$unit]['group'] ?? null;
    }

    /** Can these two units be converted into each other? */
    public static function compatible(?string $a, ?string $b): bool
    {
        return self::isValid($a) && self::isValid($b) && self::group($a) === self::group($b);
    }

    /** Quantity expressed in the base unit (g / ml / pcs). */
    public static function toBase(float $qty, ?string $unit): float
    {
        return $qty * (self::UNITS[$unit]['factor'] ?? 1);
    }

    public static function baseUnit(?string $unit): string
    {
        return match (self::group($unit)) {
            'weight' => 'g',
            'volume' => 'ml',
            default  => 'pcs',
        };
    }

    /** Convert qty from one unit to another. Returns null when incompatible (e.g. g -> ml). */
    public static function convert(float $qty, ?string $from, ?string $to): ?float
    {
        if (! self::compatible($from, $to)) {
            return null;
        }

        return self::toBase($qty, $from) / self::UNITS[$to]['factor'];
    }

    /** Units that may be used for a recipe line of an ingredient stocked in $unit. */
    public static function compatibleUnits(?string $unit): array
    {
        $group = self::group($unit);

        return array_keys(array_filter(self::UNITS, fn ($u) => $u['group'] === $group));
    }

    /** Sensible default "low stock" level for a unit. */
    public static function defaultMinStock(?string $unit): float
    {
        return match ($unit) {
            'g', 'ml' => 1000,
            'kg', 'L' => 1,
            default   => 10,
        };
    }

    /** 9.99 -> "9.99", 10.0 -> "10", 1250.5 -> "1,250.5" */
    public static function format(float $qty, ?string $unit = null): string
    {
        $s = rtrim(rtrim(number_format($qty, 3, '.', ','), '0'), '.');
        if ($s === '' || $s === '-0') {
            $s = '0';
        }

        return $unit ? $s . ' ' . $unit : $s;
    }

    /** "10 kg" -> "10,000 g" (null if the unit is already a base unit). */
    public static function baseLabel(float $qty, ?string $unit): ?string
    {
        if (! self::isValid($unit) || self::UNITS[$unit]['factor'] === 1) {
            return null;
        }

        return self::format(self::toBase($qty, $unit), self::baseUnit($unit));
    }
}