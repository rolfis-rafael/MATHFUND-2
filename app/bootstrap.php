<?php
declare(strict_types=1);
require_once __DIR__.'/Store.php';
require_once __DIR__.'/Game.php';
$config=require __DIR__.'/../config.php';
date_default_timezone_set($config['timezone']);
$cookiePath=rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/index.php')),'/').'/';
$secure=(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off');
session_name('mathfund_session');
session_set_cookie_params(['lifetime'=>0,'path'=>$cookiePath,'httponly'=>true,'samesite'=>'Lax','secure'=>$secure]);
session_start();
if (!isset($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
header('Referrer-Policy: same-origin');

function guestCookie(string $id): void {
    global $cookiePath,$secure;
    setcookie('mathfund_guest',$id,['expires'=>time()+15552000,'path'=>$cookiePath,'httponly'=>true,'samesite'=>'Lax','secure'=>$secure]);
    $_COOKIE['mathfund_guest']=$id;
}
function currentPlayer(Store $store): array {
    if (isset($_SESSION['user'])) {
        $p=$store->get($_SESSION['user']);if ($p && $p['email']) return $p;
        unset($_SESSION['user']);
    }
    $id=$_COOKIE['mathfund_guest']??'';
    if (preg_match('/^[a-f0-9]{64}$/D',$id)) {
        $p=$store->get($id);if ($p && !$p['email']) return $p;
    }
    $id=bin2hex(random_bytes(32));guestCookie($id);
    return $store->change($id,fn()=>Game::fresh($id));
}
function inputText(array $input,string $key,int $min,int $max): string {
    $s=trim((string)($input[$key]??''));
    if (strlen($s)<$min || strlen($s)>$max) throw new DomainException("Confira o campo $key (de $min a $max caracteres). ");
    return $s;
}
