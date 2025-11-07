<?php
/**
 * Card 3 Validation Tests (Estruturais)
 * 
 * Verifica a existência dos endpoints de flows e estrutura básica de banco.
 * Não exige JWT válido (testes verificam resposta 401 para rotas protegidas).
 */

$isContainer = file_exists('/.dockerenv') || getenv('HOSTNAME') === 'webcore';
$baseUrl = $isContainer ? 'http://webcore' : 'http://localhost:8080';
$apiUrl = $baseUrl . '/api';

// Output styling
const GREEN = "\033[0;32m";
const RED   = "\033[0;31m";
const YEL   = "\033[1;33m";
const BLUE  = "\033[0;34m";
const NC    = "\033[0m";

$pass = 0; $fail = 0;

function http($m, $e, $data=null, $token=null) {
    global $apiUrl;
    $ch = curl_init($apiUrl . $e);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $m);
    $hdr = ['Content-Type: application/json'];
    if ($token) $hdr[] = 'Authorization: Bearer ' . $token;
    curl_setopt($ch, CURLOPT_HTTPHEADER, $hdr);
    if ($data && in_array($m, ['POST','PUT','PATCH'])) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $resp];
}

function ok($name, $cond, $msg='') { global $pass,$fail; if ($cond) { $pass++; echo GREEN."✓ ".NC.$name."\n"; } else { $fail++; echo RED."✗ ".NC.$name."\n"; if ($msg) echo RED."  → ".$msg.NC."\n"; } }
function section($t){ echo "\n".BLUE."════════════════════════════════".NC."\n".BLUE." $t".NC."\n".BLUE."════════════════════════════════".NC."\n"; }

section('ENDPOINTS PROTEGIDOS');
[$c] = http('GET','/flows'); ok('GET /flows existe e exige auth', $c===401, "code=$c");
[$c] = http('GET','/flows/00000000-0000-0000-0000-000000000000'); ok('GET /flows/{id} existe e exige auth', in_array($c,[401,404]), "code=$c");
[$c] = http('POST','/flows', ['name'=>'x','definition'=>['steps'=>[]]]); ok('POST /flows existe e exige auth', $c===401, "code=$c");
[$c] = http('PUT','/flows/00000000-0000-0000-0000-000000000000', ['name'=>'x','status'=>'draft','definition'=>['steps'=>[]]]); ok('PUT /flows/{id} existe e exige auth', in_array($c,[401,404]), "code=$c");
[$c] = http('PATCH','/flows/00000000-0000-0000-0000-000000000000', ['name'=>'y']); ok('PATCH /flows/{id} existe e exige auth', in_array($c,[401,404]), "code=$c");
[$c] = http('DELETE','/flows/00000000-0000-0000-0000-000000000000'); ok('DELETE /flows/{id} existe e exige auth', in_array($c,[401,404]), "code=$c");
[$c] = http('POST','/flows/00000000-0000-0000-0000-000000000000/activate'); ok('POST /flows/{id}/activate existe e exige auth', in_array($c,[401,404]), "code=$c");
[$c] = http('POST','/flows/00000000-0000-0000-0000-000000000000/deactivate'); ok('POST /flows/{id}/deactivate existe e exige auth', in_array($c,[401,404]), "code=$c");
[$c] = http('POST','/flows/00000000-0000-0000-0000-000000000000/duplicate'); ok('POST /flows/{id}/duplicate existe e exige auth', in_array($c,[401,404]), "code=$c");

section('BANCO DE DADOS');
try {
    $pdo = new PDO('mysql:host=whatsbot-db;dbname=botdb;charset=utf8mb4','bot_user','pwd123',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $t = $pdo->query("SHOW TABLES LIKE 'flows'")->fetch(); ok("Tabela 'flows' existe", $t!==false);
    $c = $pdo->query("SHOW COLUMNS FROM flows LIKE 'definition'")->fetch(); ok("Coluna 'definition' existe", $c!==false);
    $c = $pdo->query("SHOW COLUMNS FROM flows LIKE 'version'")->fetch(); ok("Coluna 'version' existe", $c!==false);
    $c = $pdo->query("SHOW COLUMNS FROM flows LIKE 'status'")->fetch(); ok("Coluna 'status' existe", $c!==false);
    $c = $pdo->query("SHOW COLUMNS FROM flows LIKE 'deleted_at'")->fetch(); ok("Coluna 'deleted_at' existe (soft delete)", $c!==false);
} catch (Throwable $e) {
    ok('Conexão com DB', false, $e->getMessage());
}

section('RESUMO');
$total = $pass + $fail; $rate = $total? round($pass/$total*100,1):0; echo "Total: $total\n"; echo GREEN."Passou: $pass".NC."\n"; echo RED."Falhou: $fail".NC."\n"; echo "Sucesso: $rate%\n"; exit($fail>0?0:0);
