<?php

namespace Tests\Unit;

use App\Services\ZScoreService;
use PHPUnit\Framework\TestCase;

class ZScoreServiceTest extends TestCase
{
    public function test_haycock_bsa_matches_hand_computed_value(): void
    {
        // 100 cm, 16 kg: 0.024265 * 100^0.3964 * 16^0.5378 ≈ 0.67 m²
        $this->assertEqualsWithDelta(0.67, ZScoreService::bsaHaycock(100, 16), 0.01);
    }

    public function test_bsa_is_null_for_non_positive_inputs(): void
    {
        $this->assertNull(ZScoreService::bsaHaycock(0, 16));
        $this->assertNull(ZScoreService::bsaHaycock(100, -1));
    }

    public function test_z_score_is_zero_at_predicted_mean(): void
    {
        // Pettersen LVIDd at BSA 0.67: ln(M) = 0.105 + 2.859·B − 2.119·B² + 0.552·B³
        $bsa = 0.67;
        $lnMean = 0.105 + 2.859 * $bsa - 2.119 * $bsa ** 2 + 0.552 * $bsa ** 3;
        $mean = exp($lnMean);

        $this->assertEqualsWithDelta(0.0, ZScoreService::zScore('lvidd', $mean, $bsa), 0.01);
    }

    public function test_z_score_null_for_unknown_measurement(): void
    {
        $this->assertNull(ZScoreService::zScore('ef', 60, 0.67));
        $this->assertNull(ZScoreService::zScore('nonsense', 1, 0.67));
    }

    public function test_z_score_null_below_neonatal_bsa_threshold(): void
    {
        $this->assertNull(ZScoreService::zScore('lvidd', 1.5, 0.14));
        $this->assertNotNull(ZScoreService::zScore('lvidd', 1.5, 0.16));
    }

    public function test_z_score_null_for_invalid_inputs(): void
    {
        $this->assertNull(ZScoreService::zScore('lvidd', 0, 0.67));
        $this->assertNull(ZScoreService::zScore('lvidd', 3.5, 0));
    }
}
