<?php
/**
 * @copyright Copyright (c) 2023, Vitor Mattos <vitor@php.rio>
 *
 * @author Vitor Mattos <vitor@php.rio>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Impostos\Service\INSS;
use PHPUnit\Framework\Attributes\DataProvider;

final class INSSTest extends TestCase
{
    #[DataProvider('providerCalcula')]
    public function testCalcula(float $base, int $ano, float $aliquota, float $expected): void
    {
        $INSS = new INSS($ano, $aliquota);
        $actual = $INSS->calcula($base);
        /**
         * @todo remover o round
         */
        $this->assertEquals($expected, round($actual, 2));
    }

    public static function providerCalcula(): array
    {
        $ano = 2023;
        $aliquota = 0.2;
        return [
            [-100, $ano, $aliquota, 0],
            [-50, $ano, $aliquota, 0],
            [0, $ano, $aliquota, 0],
            [1000, $ano, $aliquota, 200],
            [7000, $ano, $aliquota, 1400],
            [7087.16, $ano, $aliquota, 1417.43],
            [7087.17, $ano, $aliquota, 1417.43],
            // Arredondamentos de centavos da base máxima começa aqui
            [7087.18, $ano, $aliquota, 1417.44],
            [7087.19, $ano, $aliquota, 1417.44],
            [7088, $ano, $aliquota, 1417.44],
            [8000, $ano, $aliquota, 1417.44],
            [100000, $ano, $aliquota, 1417.44],
        ];
    }
}
