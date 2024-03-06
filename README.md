# Cálculo de impostos

Impostos suportados:

* INSS
* IRPF

## Informações úteis

### INSS
* **TODO**: Identificar qual a fonte dos dados da tabela do INSS e corrigir os cálculos.

### IRPF
* Calculadora de teste: https://www27.receita.fazenda.gov.br/simulador-irpf/
* Fonte de dados: https://www.gov.br/receitafederal/pt-br/assuntos/meu-imposto-de-renda/tabelas

## Exemplo

```php
$bruto = 5000;
$ano = 2024;
$mes = 1;
$dependentes = 0;

$inss = (new INSS($ano))->calcula($bruto);
$IRPF = new IRPF($ano, $mes);
$base = $IRPF->calculaBase($bruto, $inss, $dependentes);
$paraReter = $IRPF->calcula($base, $dependentes);
$tipoDeducaoAtual = $IRPF->getTipoDeducao();

echo "INSS: R$ $inss\n";
echo "Base de cálculo IRPF: R$ $base\n";
echo "Tipo de dedução atual: $tipoDeducaoAtual\n";
echo "Será retido o valor de: R$ $paraReter\n";
```
Saída:

```
INSS: R$ 1000
Base de cálculo IRPF: R$ 4000
Tipo de dedução atual: tradicional
Será retido o valor de: R$ 248.27
```