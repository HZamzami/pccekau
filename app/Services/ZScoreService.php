<?php

namespace App\Services;

/**
 * Pediatric echocardiographic z-scores.
 *
 * BSA: Haycock GB et al., J Pediatr 1978;93:62-66.
 *
 * Z-score regressions: Pettersen MD et al., "Regression equations for
 * calculation of z scores of cardiac structures in a large cohort of
 * healthy infants, children, and adolescents", J Am Soc Echocardiogr
 * 2008;21:922-934. Model: ln(mean) = a + b1·BSA + b2·BSA² + b3·BSA³,
 * z = (ln(measured) − ln(mean)) / sqrt(MSE). Measurements in cm, BSA in m².
 *
 * NOTE: coefficients transcribed from the literature — verify against the
 * original publication before clinical use.
 */
class ZScoreService
{
    /** measurement key => [a, b1, b2, b3, mse] */
    private const COEFFICIENTS = [
        'lvidd'      => [0.105, 2.859, -2.119, 0.552, 0.010],
        'lvids'      => [-0.371, 2.833, -2.081, 0.538, 0.016],
        'ivsd'       => [-1.242, 1.272, -0.762, 0.208, 0.046],
        'lvpwd'      => [-1.586, 1.849, -1.188, 0.313, 0.037],
        'la'         => [-0.208, 2.164, -1.597, 0.429, 0.023],
        'ao_annulus' => [-0.874, 2.708, -1.841, 0.452, 0.010],
        'ao_root'    => [-0.500, 2.537, -1.707, 0.420, 0.012],
    ];

    public static function bsaHaycock(float $heightCm, float $weightKg): ?float
    {
        if ($heightCm <= 0 || $weightKg <= 0) {
            return null;
        }

        return round(0.024265 * ($heightCm ** 0.3964) * ($weightKg ** 0.5378), 3);
    }

    /**
     * @param string $measurement key from COEFFICIENTS
     * @param float  $valueCm     measured dimension in cm
     * @param float  $bsa         body surface area in m²
     */
    public static function zScore(string $measurement, float $valueCm, float $bsa): ?float
    {
        if (! isset(self::COEFFICIENTS[$measurement]) || $valueCm <= 0 || $bsa <= 0) {
            return null;
        }

        // Pettersen regressions are unreliable below ~0.15 m² (preterm
        // neonates) — hide the z-score rather than show a misleading one.
        if ($bsa < 0.15) {
            return null;
        }

        [$a, $b1, $b2, $b3, $mse] = self::COEFFICIENTS[$measurement];

        $lnMean = $a + ($b1 * $bsa) + ($b2 * $bsa ** 2) + ($b3 * $bsa ** 3);

        return round((log($valueCm) - $lnMean) / sqrt($mse), 2);
    }

    public static function hasZScore(string $measurement): bool
    {
        return isset(self::COEFFICIENTS[$measurement]);
    }
}
