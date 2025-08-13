<?php
require_once __DIR__ . '/banco.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoriaId = (int)($_POST['categoria_id'] ?? 0);
    $mes = (int)($_POST['mes'] ?? date('m'));
    $ano = (int)($_POST['ano'] ?? date('Y'));
    $valorLimite = (float)($_POST['valor_limite'] ?? 0);
    set_category_limit($categoriaId, $mes, $ano, $valorLimite);
    $message = 'Limite salvo com sucesso!';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Limites por Categoria</title>
</head>
<body>
<h1>Definir limite por categoria</h1>
<?php if ($message): ?>
    <p><?= htmlspecialchars($message) ?></p>
<?php endif; ?>
<form method="post">
    <label>Categoria ID: <input type="number" name="categoria_id" required></label><br>
    <label>Mês: <input type="number" name="mes" min="1" max="12" value="<?= date('m') ?>" required></label><br>
    <label>Ano: <input type="number" name="ano" min="2000" value="<?= date('Y') ?>" required></label><br>
    <label>Valor Limite: <input type="number" step="0.01" name="valor_limite" required></label><br>
    <button type="submit">Salvar</button>
</form>
</body>
</html>
