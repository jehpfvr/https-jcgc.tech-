<?php

/**
 * Insere uma transação no banco de dados SQLite.
 *
 * @param string $date Data no formato YYYY-MM-DD
 * @param string $title Descrição da transação
 * @param float $amount Valor negativo da transação
 * @param int|null $parcelaAtual Número da parcela atual, se existir
 * @param int|null $parcelaTotal Total de parcelas, se existir
 */
function get_pdo(): PDO {
    $pdo = new PDO('sqlite:' . __DIR__ . '/nubank.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}

function ensure_tables(PDO $pdo): void {
    $pdo->exec('CREATE TABLE IF NOT EXISTS transacoes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        date TEXT NOT NULL,
        title TEXT NOT NULL,
        amount REAL NOT NULL,
        parcela_atual INTEGER NULL,
        parcela_total INTEGER NULL,
        categoria_id INTEGER NULL
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS limites_categoria (
        categoria_id INTEGER NOT NULL,
        mes INTEGER NOT NULL,
        ano INTEGER NOT NULL,
        valor_limite REAL NOT NULL,
        PRIMARY KEY (categoria_id, mes, ano)
    )');
}

function insert_transaction(
    string $date,
    string $title,
    float $amount,
    ?int $parcelaAtual = null,
    ?int $parcelaTotal = null,
    ?int $categoriaId = null
): void {
    $pdo = get_pdo();
    ensure_tables($pdo);

    $stmt = $pdo->prepare('INSERT INTO transacoes (date, title, amount, parcela_atual, parcela_total, categoria_id)
        VALUES (:date, :title, :amount, :parcela_atual, :parcela_total, :categoria_id)');
    $stmt->execute([
        ':date' => $date,
        ':title' => $title,
        ':amount' => $amount,
        ':parcela_atual' => $parcelaAtual,
        ':parcela_total' => $parcelaTotal,
        ':categoria_id' => $categoriaId,
    ]);
}

function set_category_limit(int $categoriaId, int $mes, int $ano, float $valorLimite): void {
    $pdo = get_pdo();
    ensure_tables($pdo);
    $stmt = $pdo->prepare('INSERT INTO limites_categoria (categoria_id, mes, ano, valor_limite)
        VALUES (:categoria_id, :mes, :ano, :valor_limite)
        ON CONFLICT(categoria_id, mes, ano) DO UPDATE SET valor_limite = :valor_limite');
    $stmt->execute([
        ':categoria_id' => $categoriaId,
        ':mes' => $mes,
        ':ano' => $ano,
        ':valor_limite' => $valorLimite,
    ]);
}

function get_spent_by_category(int $categoriaId, int $mes, int $ano): float {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT SUM(amount) AS total FROM transacoes
        WHERE categoria_id = :categoria_id
        AND strftime("%m", date) = :mes
        AND strftime("%Y", date) = :ano');
    $stmt->execute([
        ':categoria_id' => $categoriaId,
        ':mes' => str_pad((string)$mes, 2, '0', STR_PAD_LEFT),
        ':ano' => (string)$ano,
    ]);
    $total = (float)$stmt->fetchColumn();
    return abs($total);
}

function check_limits_and_alert(int $mes, int $ano): array {
    $pdo = get_pdo();
    ensure_tables($pdo);
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
