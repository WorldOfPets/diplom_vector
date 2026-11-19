<?php

namespace App\Support;

final class Chart
{
    /**
     * Подбирает верхнюю границу радиальной шкалы под фактический максимум,
     * чтобы «паутинка» не сжималась в точку при значениях-долях.
     *
     * @param  array<int, float|int|string|null>  $values
     */
    public static function radarMax(array $values): int
    {
        $peak = 0.0;

        foreach ($values as $value) {
            if (is_numeric($value)) {
                $peak = max($peak, (float) $value);
            }
        }

        if ($peak <= 0) {
            return 10;
        }

        foreach ([10, 20, 25, 30, 40, 50, 60, 70, 80, 90, 100] as $step) {
            if ($peak <= $step) {
                return $step;
            }
        }

        return (int) ceil($peak / 10) * 10;
    }
}
