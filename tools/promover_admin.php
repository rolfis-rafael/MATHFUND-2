<?php
// Execute pelo terminal. Este arquivo não funciona pelo navegador.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require __DIR__.'/../app/Store.php';
$config=require __DIR__.'/../config.php';$store=new Store($config);
$email=strtolower(trim($argv[1]??''));$p=$store->findEmail($email);
if (!$p) {fwrite(STDERR,"Cadastre essa conta no MathFund primeiro. Uso: php tools/promover_admin.php email\n");exit(1);}
$store->change($p['id'],function($u){$u['role']='admin';return $u;});
echo "Conta promovida. Atualize a página para acessar Questões.\n";
