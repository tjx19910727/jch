<?php

$env = parse_ini_file('.env', true);
$db = $env['DATABASE'];
$pdo = new PDO(
    "mysql:host={$db['HOSTNAME']};port={$db['HOSTPORT']};dbname={$db['DATABASE']};charset=utf8",
    $db['USERNAME'],
    $db['PASSWORD'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$tables = ['revenue_pay_channel', 'revenue_payee_config', 'revenue_rule', 'revenue_rule_machine', 'revenue_order'];
$result = [];
foreach ($tables as $table) {
    $statement = $pdo->prepare(
        'SELECT COLUMN_NAME,COLUMN_TYPE,COLUMN_DEFAULT,IS_NULLABLE
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA=? AND TABLE_NAME=?
         ORDER BY ORDINAL_POSITION'
    );
    $statement->execute([$db['DATABASE'], $table]);
    $result['columns'][$table] = $statement->fetchAll();
    $result['counts'][$table] = (int)$pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
}
$result['rule_modes'] = $pdo->query(
    'SELECT rule_mode,status,COUNT(*) count FROM revenue_rule GROUP BY rule_mode,status ORDER BY rule_mode,status'
)->fetchAll();
$result['machine_rule_modes'] = $pdo->query(
    'SELECT rr.rule_mode,rrm.status,COUNT(*) count
     FROM revenue_rule_machine rrm
     JOIN revenue_rule rr ON rr.rr_id=rrm.rr_id
     GROUP BY rr.rule_mode,rrm.status
     ORDER BY rr.rule_mode,rrm.status'
)->fetchAll();
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
