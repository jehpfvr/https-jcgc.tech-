<?php

require_once __DIR__ . '/banco.php';

/**
 * Faz a leitura de um CSV de cartão de crédito do Nubank e insere as
 * transações no banco de dados.
 *
 * Espera-se que o arquivo possua as colunas: date, title e amount.
 * Valores positivos representam despesas; valores negativos, créditos.
 *
 * @param string $path Caminho para o arquivo CSV.
 * @return array Lista de transações inseridas.
 */
function parse_nubank_credit_csv(string $path): array
{
    if (!file_exists($path)) {
        throw new InvalidArgumentException("Arquivo não encontrado: {$path}");
    }

    $handle = fopen($path, 'r');
    if ($handle === false) {
        throw new RuntimeException("Não foi possível abrir o arquivo: {$path}");
    }

    // Ignora o cabeçalho
    fgetcsv($handle);

    $registros = [];
    while (($row = fgetcsv($handle)) !== false) {
        $dataBruta = trim($row[0] ?? '');
        $titulo = trim($row[1] ?? '');
        $valorBruto = trim($row[2] ?? '0');

        // Converte data para YYYY-MM-DD
        $dataObj = \DateTime::createFromFormat('Y-m-d', $dataBruta);
        if ($dataObj === false) {
            $dataObj = \DateTime::createFromFormat('d/m/Y', $dataBruta) ?: new \DateTime($dataBruta);
        }
        $data = $dataObj->format('Y-m-d');

        // Converte valor e ajusta sinal
        $valorLimpo = preg_replace('/[^0-9,.-]/', '', $valorBruto);
        if (str_contains($valorLimpo, ',')) {
            $valorLimpo = str_replace(['.', ','], ['', '.'], $valorLimpo);
        }
        $valorNumerico = (float)$valorLimpo;
        $valor = $valorNumerico > 0 ? -$valorNumerico : abs($valorNumerico);

        // Detecta parcelas
        $parcelaAtual = null;
        $parcelaTotal = null;
        if (preg_match('/Parcela\s+(\d+)\/(\d+)/i', $titulo, $matches)) {
            $parcelaAtual = (int)$matches[1];
            $parcelaTotal = (int)$matches[2];
        }

        insert_transaction($data, $titulo, $valor, $parcelaAtual, $parcelaTotal);

        $registros[] = [
            'date' => $data,
            'title' => $titulo,
            'amount' => $valor,
            'parcela_atual' => $parcelaAtual,
            'parcela_total' => $parcelaTotal,
        ];
    }

    fclose($handle);

    return $registros;
}

// Execução via linha de comando
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    if ($argc < 2) {
        fwrite(STDERR, "Uso: php parse_nubank_credit_csv.php <arquivo.csv>\n");
        exit(1);
    }

    try {
        $transacoes = parse_nubank_credit_csv($argv[1]);
        echo 'Importadas ' . count($transacoes) . " transações.\n";
    } catch (Exception $e) {
        fwrite(STDERR, 'Erro: ' . $e->getMessage() . "\n");
        exit(1);
    }
}

?>

