<?php
// Card 4 - Testes estruturais do motor de conversação
$isContainer = file_exists('/.dockerenv') || getenv('HOSTNAME') === 'webcore';
$baseUrl = $isContainer ? 'http://webcore' : 'http://localhost:8080';
$apiUrl = $baseUrl . '/api';

const GREEN = "\033[0;32m"; const RED="\033[0;31m"; const BLUE="\033[0;34m"; const NC="\033[0m";
$pass=0;$fail=0;
function httpc($m,$e,$d=null){global $apiUrl;$ch=curl_init($apiUrl.$e);curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $m);curl_setopt($ch, CURLOPT_HTTPHEADER,['Content-Type: application/json']);if($d)curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($d));$r=curl_exec($ch);$c=curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);return [$c,$r];}
function ok($n,$c,$m=''){global $pass,$fail;if($c){$pass++;echo GREEN."✓ ".NC.$n."\n";}else{$fail++;echo RED."✗ ".NC.$n."\n";if($m)echo RED."  → $m".NC."\n";}}
function section($t){echo "\n".BLUE."==== $t ====".NC."\n";}

section('ROTAS PROTEGIDAS');
[$c]=httpc('POST','/conversations',['contact_phone'=>'+5511','contact_name'=>'X','flow_id'=>'00000000-0000-0000-0000-000000000000']); ok('POST /conversations exige auth', $c===401, "code=$c");
[$c]=httpc('GET','/conversations/00000000-0000-0000-0000-000000000000'); ok('GET /conversations/{id} exige auth', in_array($c,[401,404]), "code=$c");
[$c]=httpc('GET','/conversations'); ok('GET /conversations exige auth', $c===401, "code=$c");
[$c]=httpc('POST','/conversations/00000000-0000-0000-0000-000000000000/next'); ok('POST /conversations/{id}/next exige auth', in_array($c,[401,404]), "code=$c");
[$c]=httpc('PATCH','/conversations/00000000-0000-0000-0000-000000000000',['status'=>'abandoned']); ok('PATCH /conversations/{id} exige auth', in_array($c,[401,404]), "code=$c");
[$c]=httpc('GET','/conversations/active/phone/%2B5511999887766'); ok('GET /conversations/active/phone/{phone} exige auth', in_array($c,[401,404]), "code=$c");

section('ESTRUTURA DB');
try{$pdo=new PDO('mysql:host=whatsbot-db;dbname=botdb;charset=utf8mb4','bot_user','pwd123',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
 $t=$pdo->query("SHOW TABLES LIKE 'conversation_transitions'")->fetch(); ok("Tabela conversation_transitions existe", $t!==false);
 $c=$pdo->query("SHOW COLUMNS FROM conversations LIKE 'current_step'")->fetch(); ok("Coluna conversations.current_step existe", $c!==false);
 $c=$pdo->query("SHOW COLUMNS FROM conversations LIKE 'updated_at'")->fetch(); ok("Coluna conversations.updated_at existe", $c!==false);
}catch(Throwable $e){ ok('Conexão DB', false, $e->getMessage()); }

echo "\nTotal: ".($pass+$fail)."\n"; echo GREEN."Passou: $pass".NC."\n"; echo RED."Falhou: $fail".NC."\n"; exit(0);
