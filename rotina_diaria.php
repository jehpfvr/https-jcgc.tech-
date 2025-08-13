<?php
require_once __DIR__ . '/banco.php';

$mes = (int)date('m');
$ano = (int)date('Y');

$alerts = check_limits_and_alert($mes, $ano);
foreach ($alerts as $alert) {
    echo $alert . PHP_EOL;
}
