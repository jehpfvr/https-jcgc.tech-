<?php

/**
 * Lê um CSV exportado do Nubank e insere cada registro no banco de dados.
 *
 * Espera-se que o arquivo tenha as colunas: data, título e valor.
 *
 * @param string $path Caminho para o arquivo CSV.
 * @param int $accountId ID da conta associada.
 * @param string $tipo Tipo da transação (ex: crédito, débito).
 * @return array Lista de registros inseridos.
 */
function parse_nubank_csv(string $path, int $accountId, string $tipo): array
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

        // Insere no banco de dados
        insert_transaction($accountId, $data, $titulo, $valor, $tipo, null, $path);

        $registros[] = [
            'date' => $data,
            'title' => $titulo,
            'amount' => $valor,
        ];
    }

    fclose($handle);

    return $registros;
}

?>
