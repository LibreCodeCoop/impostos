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

use InvalidArgumentException;

/**
 * Calculadora de teste: https://www27.receita.fazenda.gov.br/simulador-irpf/
 * Fonte de dados: https://www.gov.br/receitafederal/pt-br/assuntos/meu-imposto-de-renda/tabelas
 */
class IRPF
{
    private array $tabelaProgressiva;
    private array $tabela;
    private string $tipoDeducao = '';

    public function __construct(
        private int $anoBase,
        private int $mes,
        string $arquivoDaTabelaIRPF = '',
    ) {
        $this->loadTabelaProgressiva($arquivoDaTabelaIRPF);
        $this->tabela = $this->filtraTabelaProgressiva();
    }

    private function loadTabelaProgressiva(string $arquivoDaTabelaIRPF): void
    {
        if (empty($arquivoDaTabelaIRPF)) {
            $arquivoDaTabelaIRPF = __DIR__ . DIRECTORY_SEPARATOR . 'modeloTabelaIRPF.json';
        }
        if (!file_exists($arquivoDaTabelaIRPF)) {
            throw new InvalidArgumentException('Arquivo da tabela progressiva do IRPF não encontrado: ' . $arquivoDaTabelaIRPF);
        }
        $data = file_get_contents($arquivoDaTabelaIRPF);
        if (!json_validate($data)) {
            throw new InvalidArgumentException('Conteúdo do arquivo da tabela progressiva do IRPF ser um JSON: ' . $arquivoDaTabelaIRPF);
        }
        $this->tabelaProgressiva = json_decode($data, true);
    }

    private function filtraTabelaProgressiva(): array
    {
        if (!array_key_exists($this->anoBase, $this->tabelaProgressiva)) {
            throw new InvalidArgumentException('Ano base inexistente: '. $this->anoBase . '. Corrija a tabela progressiva do IRPF.');
        }
        $tabelasDoAnoBase = $this->tabelaProgressiva[$this->anoBase];
        $aliquotasDoMes = array_filter(
            $tabelasDoAnoBase,
            fn (array $t) => $this->isOnMonthInterval($t)
        );
        return current($aliquotasDoMes);
    }

    private function isOnMonthInterval(array $row): bool
    {
        if ($this->mes >= $row['mes_inicio']) {
            if ($this->mes <= $row['mes_fim'] || is_null($row['mes_fim'])) {
                return true;
            }
        }
        return false;
    }

    public function getFaixa(float $base): array
    {
        if ($base < 0) {
            $base = 0;
        }
        foreach ($this->tabela['aliquotas'] as $aliquota) {
            if ($base >= $aliquota['min']) {
                if ($base <= $aliquota['max'] || is_null($aliquota['max'])) {
                    return $aliquota;
                }
            }
        }
        throw new InvalidArgumentException('Valor base não encontrado na tabela progressiva do IRPF');
    }

    public function calculaBase(float $bruto, float $inss, int $dependentes): float
    {
        if ($this->anoBase >= 2023 && $this->mes >= 5) {
            $deducao = $this->calculaDeducaoFavoravel($inss, $dependentes);
        } else {
            $this->tipoDeducao = 'tradicional';
            $deducao = $this->calculaDeducaoTradicional($inss, $dependentes);
        }
        $base = $bruto - $deducao;
        if ($base < 0) {
            $base = 0;
        }
        return $base;
    }

    private function calculaDeducaoFavoravel(float $inss, int $dependentes): float
    {
        $simplificada = $this->calculaDeducaoSimplificada($inss);
        $tradicional = $this->calculaDeducaoTradicional($inss, $dependentes);
        if ($simplificada <= $this->tabela['aliquotas'][0]['max'] * 0.25) {
            $this->tipoDeducao = 'simplificada';
            return $simplificada;
        }
        $this->tipoDeducao = 'tradicional';
        return $tradicional;
    }

    public function getTipoDeducao(): string
    {
        return $this->tipoDeducao;
    }

    private function calculaDeducaoSimplificada(float $inss): float
    {
        $deducaoAliquotaPrimeiraFaixa = $this->tabela['aliquotas'][0]['max'] * 0.25;
        if ($deducaoAliquotaPrimeiraFaixa > $inss) {
            return $deducaoAliquotaPrimeiraFaixa;
        }
        return $inss;
    }

    private function calculaDeducaoTradicional(float $inss, int $dependentes): float
    {
        return $inss + $dependentes * $this->tabela['deducao_por_dependente'];
    }

    public function calcula(float $base, int $dependentes): float
    {
        $faixa = $this->getFaixa($base);
        return $base * $faixa['aliquota'] - ($faixa['deducao']);
    }
}
