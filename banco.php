<?php

/**
 * Conexão com banco de dados MySQL usando PDO.
 */
function get_pdo(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        // Credenciais do banco de dados fornecidas pelo HostGator
        $host = getenv('HG_DB_HOST') ?: 'seu_host';
        $dbname = getenv('HG_DB_NAME') ?: 'seu_banco';
        $user = getenv('HG_DB_USER') ?: 'seu_usuario';
        $pass = getenv('HG_DB_PASS') ?: 'sua_senha';

        $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    return $pdo;
}

/**
 * Insere uma transação na tabela `transactions`.
 */
function insert_transaction(
    int $accountId,
    string $date,
    string $description,
    float $amount,
    string $type,
    ?int $categoryId = null,
    ?string $arquivoOriginal = null
): void {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('INSERT INTO transactions (account_id, data, descricao, valor, tipo, category_id, arquivo_original)
        VALUES (:account_id, :data, :descricao, :valor, :tipo, :category_id, :arquivo_original)');
    $stmt->execute([
        ':account_id' => $accountId,
        ':data' => $date,
        ':descricao' => $description,
        ':valor' => $amount,
        ':tipo' => $type,
        ':category_id' => $categoryId,
        ':arquivo_original' => $arquivoOriginal,
    ]);
}

function set_category_limit(int $categoriaId, int $mes, int $ano, float $valorLimite): void {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('INSERT INTO limites_categoria (categoria_id, mes, ano, valor_limite)
        VALUES (:categoria_id, :mes, :ano, :valor_limite)
        ON DUPLICATE KEY UPDATE valor_limite = :valor_limite');
    $stmt->execute([
        ':categoria_id' => $categoriaId,
        ':mes' => $mes,
        ':ano' => $ano,
        ':valor_limite' => $valorLimite,
    ]);
}

function get_spent_by_category(int $categoriaId, int $mes, int $ano): float {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT SUM(valor) AS total FROM transactions
        WHERE category_id = :categoria_id
        AND MONTH(data) = :mes
        AND YEAR(data) = :ano');
    $stmt->execute([
        ':categoria_id' => $categoriaId,
        ':mes' => $mes,
        ':ano' => $ano,
    ]);
    $total = (float)$stmt->fetchColumn();
    return abs($total);
}

function check_limits_and_alert(int $mes, int $ano): array {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT categoria_id, valor_limite FROM limites_categoria WHERE mes = :mes AND ano = :ano');
    $stmt->execute([
        ':mes' => $mes,
        ':ano' => $ano,
    ]);

    $alerts = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $gasto = get_spent_by_category((int)$row['categoria_id'], $mes, $ano);
        if ($gasto > (float)$row['valor_limite']) {
            $alerts[] = "Limite excedido para categoria {$row['categoria_id']}: gasto {$gasto} > limite {$row['valor_limite']}";
        }
    }

    return $alerts;
}

?>
