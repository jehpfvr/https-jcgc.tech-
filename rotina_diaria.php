<?php
require_once __DIR__ . '/banco.php';
require_once __DIR__ . '/email.php';

$mes = (int)date('m');
$ano = (int)date('Y');

$alerts = check_limits_and_alert($mes, $ano);
foreach ($alerts as $alert) {
    enviar_email(SMTP_USER, 'Alerta de limite excedido', $alert);
    echo $alert . PHP_EOL;
}
