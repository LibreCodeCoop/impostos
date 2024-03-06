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

namespace Impostos\Service;

use DateTime;
use InvalidArgumentException;

class INSS
{
    private float $baseMaxima;
    private array $tabela;
    public function __construct(
        private int $ano = 0,
        private float $aliquota = 0.20,
        string $arquivoDaTabela = '',
    ) {
        if ($this->ano === 0) {
            $this->ano = (int) (new DateTime())->format('Y');
        }
        $this->loadTabela($arquivoDaTabela);
        $this->baseMaxima = $this->getBaseMaxima();
    }

    private function loadTabela(string $arquivoDaTabela): void
    {
        if (empty($arquivoDaTabela)) {
            $arquivoDaTabela = __DIR__ . DIRECTORY_SEPARATOR . 'modeloTabelaINSS.json';
        }
        if (!file_exists($arquivoDaTabela)) {
            throw new InvalidArgumentException('Arquivo da tabela INSS não encontrado: ' . $arquivoDaTabela);
        }
        $data = file_get_contents($arquivoDaTabela);
        if (!json_validate($data)) {
            throw new InvalidArgumentException('Conteúdo do arquivo da tabela INSS ser um JSON: ' . $arquivoDaTabela);
        }
        $this->tabela = json_decode($data, true);
    }

    private function getBaseMaxima(): float
    {
        if (!array_key_exists($this->ano, $this->tabela)) {
            throw new InvalidArgumentException('Ano inexistente: '. $this->ano . '. Corrija a tabela do INSS.');
        }
        if (!array_key_exists((string) $this->aliquota, $this->tabela[$this->ano])) {
            throw new InvalidArgumentException('Alíquota inexistente: '. $this->ano . '. Corrija a tabela do INSS.');
        }
        return $this->tabela[$this->ano][(string) $this->aliquota];
    }

    public function calcula(float $base): float
    {
        if ($base <= 0) {
            return 0;
        }
        if ($base > $this->baseMaxima) {
            return $this->baseMaxima * $this->aliquota;
        }
        return $base * $this->aliquota;
    }
}
