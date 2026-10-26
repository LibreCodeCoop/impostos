<?php

declare(strict_types=1);

use Impostos\Service\IRPF;
use PHPUnit\Framework\TestCase;

final class IRPFRegressionTest extends TestCase
{
    public function testHistoricalInssCapsMatchPublishedOfficialValues(): void
    {
        self::assertEqualsWithDelta(1501.498, (new \Impostos\Service\INSS(2023))->calcula(10000), 0.001);
        self::assertEqualsWithDelta(1557.204, (new \Impostos\Service\INSS(2024))->calcula(10000), 0.001);
        self::assertEqualsWithDelta(1695.11, (new \Impostos\Service\INSS(2026))->calcula(10000), 0.001);
    }

    public function testFebruary2024AppliesUpdatedTableNotJanuaryTable(): void
    {
        self::assertSame(0.075, (new IRPF(2024, 1))->getFaixa(2200.00)['aliquota']);
        self::assertSame(0, (new IRPF(2024, 2))->getFaixa(2200.00)['aliquota']);
    }

    public function testSimplifiedDiscountDoesNotAddToSocialSecurityContributions(): void
    {
        $irpf = new IRPF(2026, 5);
        $base = $irpf->calculaBase(4000.00, 400.00, 0);
        self::assertEqualsWithDelta(3392.80, $base, 0.001);
        self::assertSame('simplificada', $irpf->getTipoDeducao());
    }

    public function testJanuary2025RetainsOlderBracket(): void
    {
        self::assertSame(0.075, (new IRPF(2025, 1))->getFaixa(2300.00)['aliquota']);
    }

    public function testMay2025UsesUpdatedOfficialBracket(): void
    {
        self::assertSame(0, (new IRPF(2025, 5))->getFaixa(2300.00)['aliquota']);
        self::assertEqualsWithDelta(182.16, (new IRPF(2025, 5))->getFaixa(2700.00)['deducao'], 0.00001);
        self::assertEqualsWithDelta(607.20, 2428.80 * 0.25, 0.00001);
    }

    public function testTraditionalDeductionWinsWhenDependentsExceedSimplifiedDiscount(): void
    {
        $irpf = new IRPF(2026, 1);
        self::assertEqualsWithDelta(4248.82, $irpf->calculaBase(5200.00, 572.00, 2), 0.00001);
        self::assertSame('tradicional', $irpf->getTipoDeducao());
    }

    public function testSimplifiedDeductionWinsWithoutDependents(): void
    {
        $irpf = new IRPF(2026, 1);
        self::assertEqualsWithDelta(4592.80, $irpf->calculaBase(5200.00, 572.00, 0), 0.00001);
        self::assertSame('simplificada', $irpf->getTipoDeducao());
    }

    public function test2026ReductionMatchesFederalRevenueExample(): void
    {
        $irpf = new IRPF(2026, 1);
        $base = $irpf->calculaBase(6000.00, 649.60, 0);
        self::assertEqualsWithDelta(5350.40, $base, 0.00001);
        self::assertEqualsWithDelta(382.88, round($irpf->calcula($base, 0), 2), 0.01);
    }
}
