<?php

/**
 * Lê um CSV exportado do Nubank e insere cada registro no banco de dados.
 *
 * Espera-se que o arquivo tenha as colunas: data, título e valor.
 *
 * @param string $path Caminho para o arquivo CSV.
 * @return array Lista de registros inseridos.
 */
function parse_nubank_csv(string $path): array
{
    require_once __DIR__ . '/banco.php';

    if (!file_exists($path)) {
        throw new InvalidArgumentException("Arquivo não encontrado: {$path}");
    }

    $handle = fopen($path, 'r');
    if ($handle === false) {
        throw new RuntimeException("Não foi possível abrir o arquivo: {$path}");
    }

    // Ignora o cabeçalho
    $headers = fgetcsv($handle);

    $registros = [];
    while (($row = fgetcsv($handle)) !== false) {
        // Assume que as colunas são [data, titulo, valor]
        $dataBruta = trim($row[0] ?? '');
        $titulo = trim($row[1] ?? '');
        $valorBruto = trim($row[2] ?? '0');

        // Converte data para YYYY-MM-DD
        $dataObj = \DateTime::createFromFormat('d/m/Y', $dataBruta);
        if ($dataObj === false) {
            // Tenta formato alternativo
            $dataObj = new \DateTime($dataBruta);
        }
        $data = $dataObj->format('Y-m-d');

        // Converte valor para decimal negativo
        $valorLimpo = preg_replace('/[^0-9,.-]/', '', $valorBruto);
        if (str_contains($valorLimpo, ',')) {
            // Formato brasileiro: milhar com ponto e decimal com vírgula
            $valorLimpo = str_replace(['.', ','], ['', '.'], $valorLimpo);
        }
        $valor = -abs((float)$valorLimpo);

        // Detecta parcelas no título
        $parcelaAtual = null;
        $parcelaTotal = null;
        if (preg_match('/Parcela\s+(\d+)\/(\d+)/i', $titulo, $matches)) {
            $parcelaAtual = (int)$matches[1];
            $parcelaTotal = (int)$matches[2];
        }

        // Insere no banco de dados
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

?>
