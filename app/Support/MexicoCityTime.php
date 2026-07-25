<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class MexicoCityTime
{
    public const TIMEZONE = 'America/Mexico_City';

    public static function timezone(): string
    {
        return config('app.timezone', self::TIMEZONE) ?: self::TIMEZONE;
    }

    public static function toLocal($date): ?CarbonInterface
    {
        if (!$date) {
            return null;
        }

        if ($date instanceof CarbonInterface) {
            return $date->copy()->timezone(self::timezone());
        }

        return Carbon::parse($date)->timezone(self::timezone());
    }

    public static function dateTime($date, string $fallback = 'Sin fecha', string $format = 'd/m/Y H:i'): string
    {
        $localDate = self::toLocal($date);

        return $localDate ? $localDate->format($format) : $fallback;
    }

    public static function shortDateTime($date, string $fallback = 'Sin fecha'): string
    {
        return self::dateTime($date, $fallback, 'd/m H:i');
    }

    public static function date($date, string $fallback = 'Sin fecha'): string
    {
        return self::dateTime($date, $fallback, 'd/m/Y');
    }

    public static function remaining($dueAt, string $fallback = 'Pendiente'): string
    {
        $localDueAt = self::toLocal($dueAt);

        if (!$localDueAt) {
            return $fallback;
        }

        $minutes = (int) Carbon::now(self::timezone())->diffInMinutes($localDueAt, false);

        if ($minutes <= 0) {
            return 'Vencido';
        }

        $days = intdiv($minutes, 1440);
        $hours = intdiv($minutes % 1440, 60);
        $remainingMinutes = $minutes % 60;

        if ($days > 0) {
            return $days . ' día' . ($days === 1 ? '' : 's') . ($hours > 0 ? ' ' . $hours . ' h' : '');
        }

        if ($hours > 0) {
            return $hours . ' h' . ($remainingMinutes > 0 ? ' ' . $remainingMinutes . ' min' : '');
        }

        return max(1, $remainingMinutes) . ' min';
    }
}
