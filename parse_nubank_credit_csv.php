<?php

/**
 * Parse a CSV export from the Nubank credit card and insert each
 * transaction into the database.
 *
 * The function is tolerant to slight variations of the header names
 * (e.g. "descricao" vs "Descrição") and always stores the values as
 * negative numbers, since credit card charges represent expenses.
 *
 * @param string $path      Path to the CSV file.
 * @param int    $accountId Related account ID.
 * @param string $tipo      Transaction type.
 *
 * @return array List of parsed transactions.
 */
function parse_nubank_credit_csv(string $path, int $accountId, string $tipo): array
{
    require_once __DIR__ . '/banco.php';

    if (!file_exists($path)) {
        throw new InvalidArgumentException("Arquivo não encontrado: {$path}");
    }

    $handle = fopen($path, 'r');
    if ($handle === false) {
        throw new RuntimeException("Não foi possível abrir o arquivo: {$path}");
    }

    $headers = fgetcsv($handle);
    if ($headers === false) {
        fclose($handle);
        return [];
    }

    // Mapeia índices das colunas, normalizando para minúsculas.
    $map = [];
    foreach ($headers as $idx => $name) {
        $map[strtolower(trim($name))] = $idx;
    }

    $colDate  = $map['data']      ?? $map['date']      ?? null;
    $colDesc  = $map['descricao'] ?? $map['descrição'] ?? $map['description'] ?? $map['titulo'] ?? $map['título'] ?? null;
    $colValue = $map['valor']     ?? $map['amount']    ?? null;

    if ($colDate === null || $colDesc === null || $colValue === null) {
        fclose($handle);
        throw new RuntimeException('Colunas obrigatórias ausentes no CSV.');
    }

    $registros = [];
    while (($row = fgetcsv($handle)) !== false) {
        $rawDate   = trim($row[$colDate]  ?? '');
        $descricao = trim($row[$colDesc]  ?? '');
        $rawValue  = trim($row[$colValue] ?? '0');

        if ($rawDate === '' && $descricao === '' && $rawValue === '') {
            continue; // pula linhas vazias
        }

        $dataObj = \DateTime::createFromFormat('d/m/Y', $rawDate)
            ?: \DateTime::createFromFormat('Y-m-d', $rawDate);
        if (!$dataObj) {
            $dataObj = new \DateTime($rawDate);
        }
        $data = $dataObj->format('Y-m-d');

        $valorLimpo = preg_replace('/[^0-9,.-]/', '', $rawValue);
        if (str_contains($valorLimpo, ',')) {
            $valorLimpo = str_replace(['.', ','], ['', '.'], $valorLimpo);
        }
        $valor = (float)$valorLimpo;
        // Garante valor negativo (despesa)
        $valor = $valor > 0 ? -$valor : $valor;

        insert_transaction($accountId, $data, $descricao, $valor, $tipo, null, $path);

        $registros[] = [
            'date'   => $data,
            'title'  => $descricao,
            'amount' => $valor,
        ];
    }

    fclose($handle);

    return $registros;
}

?>
