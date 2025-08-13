<?php
/**
 * Detecta o tipo de arquivo CSV com base no cabeçalho.
 *
 * Retorna 'conta' para arquivos de conta corrente
 * ou 'cartao' para arquivos de cartão de crédito.
 *
 * @param string $path Caminho para o arquivo CSV.
 * @return string Tipo detectado ('conta' ou 'cartao').
 * @throws RuntimeException Se o formato não for reconhecido.
 */
function detectar_tipo_csv(string $path): string
{
    if (!file_exists($path)) {
        throw new InvalidArgumentException("Arquivo não encontrado: {$path}");
    }

    $handle = fopen($path, 'r');
    if ($handle === false) {
        throw new RuntimeException("Não foi possível abrir o arquivo: {$path}");
    }

    $headers = fgetcsv($handle, 0, ',', '"', "\\");
    fclose($handle);

    if ($headers === false) {
        throw new RuntimeException('CSV vazio ou inválido.');
    }

    $cols = array_map(function ($h) {
        return strtolower(trim($h));
    }, $headers);

    // Arquivo padrão de conta corrente: exatamente data,titulo,valor
    if (count($cols) === 3 && $cols[0] === 'data' && $cols[1] === 'titulo' && $cols[2] === 'valor') {
        return 'conta';
    }

    // Arquivo de cartão de crédito: deve possuir data, valor e alguma coluna de descrição
    $hasData  = in_array('data', $cols, true);
    $hasValor = in_array('valor', $cols, true) || in_array('amount', $cols, true);
    $hasDesc  = in_array('descricao', $cols, true) || in_array('descrição', $cols, true)
        || in_array('description', $cols, true) || in_array('titulo', $cols, true) || in_array('título', $cols, true);

    if ($hasData && $hasValor && $hasDesc) {
        return 'cartao';
    }

    throw new RuntimeException('Formato de CSV não reconhecido. Verifique o cabeçalho.');
}
?>
