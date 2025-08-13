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
function insert_transaction(
    string $date,
    string $title,
    float $amount,
    ?int $parcelaAtual = null,
    ?int $parcelaTotal = null
): void {
    $pdo = new PDO('sqlite:' . __DIR__ . '/nubank.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec('CREATE TABLE IF NOT EXISTS transacoes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        date TEXT NOT NULL,
        title TEXT NOT NULL,
        amount REAL NOT NULL,
        parcela_atual INTEGER NULL,
        parcela_total INTEGER NULL
    )');

    $stmt = $pdo->prepare('INSERT INTO transacoes (date, title, amount, parcela_atual, parcela_total)
        VALUES (:date, :title, :amount, :parcela_atual, :parcela_total)');
    $stmt->execute([
        ':date' => $date,
        ':title' => $title,
        ':amount' => $amount,
        ':parcela_atual' => $parcelaAtual,
        ':parcela_total' => $parcelaTotal,
    ]);
}

?>
