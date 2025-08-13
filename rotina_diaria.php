<?php
require_once __DIR__ . '/banco.php';
require_once __DIR__ . '/email.php';
require_once __DIR__ . '/whatsapp_service.php';


const DIA_VENCIMENTO_FATURA = 5;
const DIAS_AVISO_FATURA = 5;

$mes = (int)date('m');
$ano = (int)date('Y');

$alerts = check_limits_and_alert($mes, $ano);
foreach ($alerts as $alert) {
    enviar_email(SMTP_USER, 'Alerta de limite excedido', $alert);
    enviar_mensagem(WHATSAPP_DEFAULT_NUMBER, $alert);
    echo $alert . PHP_EOL;
}

$diaAtual = (int)date('d');
$diaLembrete = DIA_VENCIMENTO_FATURA - DIAS_AVISO_FATURA;
if ($diaAtual === $diaLembrete) {
    $dataVencimento = sprintf('%02d/%02d/%04d', DIA_VENCIMENTO_FATURA, $mes, $ano);
    $mensagemFatura = "Lembrete: sua fatura vence em " . DIAS_AVISO_FATURA . " dias (" . $dataVencimento . ").";
    enviar_email(SMTP_USER, 'Lembrete de fatura', $mensagemFatura);
    enviar_mensagem(WHATSAPP_DEFAULT_NUMBER, $mensagemFatura);
    echo $mensagemFatura . PHP_EOL;
}
