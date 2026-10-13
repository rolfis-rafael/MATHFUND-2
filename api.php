<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
try {
    require __DIR__.'/app/bootstrap.php';
    $store=new Store($config);$game=new Game($store);
    $action=(string)($_GET['action']??'bootstrap');
    $readOnly=in_array($action,['bootstrap','ranking','admin-list'],true);
    $input=[];
    if (!$readOnly) {
        if ($_SERVER['REQUEST_METHOD']!=='POST') {http_response_code(405);throw new DomainException('Use POST para esta operação.');}
        if ((int)($_SERVER['CONTENT_LENGTH']??0)>50000) {http_response_code(413);throw new DomainException('Conteúdo muito grande.');}
        if (!hash_equals($_SESSION['csrf'],$_SERVER['HTTP_X_CSRF_TOKEN']??'')) {http_response_code(403);throw new DomainException('Sua sessão mudou. Atualize a página e tente novamente.');}
        $input=json_decode(file_get_contents('php://input'),true,32,JSON_THROW_ON_ERROR);
        if (!is_array($input)) throw new DomainException('Dados inválidos.');
    }
    $p=currentPlayer($store);$previousTrophies=array_keys($p['trophies']??[]);$extra=[];
    switch ($action) {
        case 'bootstrap': $extra=['catalog'=>$game->catalog(),'date'=>date('Y-m-d'),'trophyCatalog'=>Achievements::catalog()];break;
        case 'playtime':
            $p=$store->change($p['id'],function($u)use($input){
                if(!$u['active']||$u['active']['id']!==($input['run']??''))return $u;
                return Achievements::tick($u,($input['running']??false)===true);
            });
            echo json_encode(['ok'=>true,'playedSeconds'=>$p['playedSeconds']??0]);exit;
        case 'ranking': $extra=['ranking'=>$store->ranking()];break;
        case 'onboarding':
            $step=(string)($input['step']??'');
            if (!in_array($step,['welcome','boss'],true)) throw new DomainException('Tutorial inválido.');
            $p=$store->change($p['id'],function($u)use($step){$u['onboarding'][$step]=true;if($step==='boss')$u['onboarding']['bossVersion']=2;return $u;});break;
        case 'register':
            if ($p['email']) throw new DomainException('Você já está conectado.');
            if (!$store->allowAttempt('register:'.($_SERVER['REMOTE_ADDR']??'local'))) throw new DomainException('Muitas tentativas. Aguarde 15 minutos.');
            $name=inputText($input,'nome',2,60);$email=strtolower(inputText($input,'email',3,190));$pass=inputText($input,'senha',8,128);
            if (!filter_var($email,FILTER_VALIDATE_EMAIL)) throw new DomainException('Digite um e-mail válido.');
            if ($store->findEmail($email)) throw new DomainException('Este e-mail já está cadastrado. Entre na conta.');
            $hash=password_hash($pass,PASSWORD_DEFAULT);
            $p=$store->change($p['id'],function($u)use($name,$email,$hash){$u['name']=$name;$u['email']=$email;$u['password']=$hash;return $u;});
            session_regenerate_id(true);$_SESSION['user']=$p['id'];$_SESSION['csrf']=bin2hex(random_bytes(32));break;
        case 'login':
            if (!$store->allowAttempt('login:'.($_SERVER['REMOTE_ADDR']??'local'))) throw new DomainException('Muitas tentativas. Aguarde 15 minutos.');
            $email=strtolower(inputText($input,'email',3,190));$pass=inputText($input,'senha',1,128);
            $user=$store->findEmail($email);
            if (!$user || !password_verify($pass,$user['password'])) throw new DomainException('E-mail ou senha incorretos.');
            session_regenerate_id(true);$_SESSION['user']=$user['id'];$_SESSION['csrf']=bin2hex(random_bytes(32));$p=$user;$previousTrophies=array_keys($p['trophies']??[]);break;
        case 'logout':
            $_SESSION=[];session_regenerate_id(true);$_SESSION['csrf']=bin2hex(random_bytes(32));unset($_COOKIE['mathfund_guest']);$p=currentPlayer($store);break;
        case 'profile':
            $name=inputText($input,'nome',2,60);$grade=(string)($input['grade']??$p['grade']);$avatar=(string)($input['avatar']??$p['avatar']);
            if (!array_key_exists($grade,Game::GRADES) || !in_array($avatar,['fox','owl','cat','frog','rocket'],true)) throw new DomainException('Perfil inválido.');
            $p=$store->change($p['id'],function($u)use($name,$grade,$avatar,$input){
                $u['name']=$name;$u['grade']=$grade;$u['avatar']=$avatar;
                foreach (['sound','motion','ranking'] as $key) if (isset($input[$key])) $u['settings'][$key]=(bool)$input[$key];
                if (isset($input['goal'])) $u['settings']['goal']=in_array((int)$input['goal'],[5,10,15],true)?(int)$input['goal']:5;
                return $u;
            });break;
        case 'password':
            if (!$p['email']) throw new DomainException('Entre em uma conta para trocar a senha.');
            if (!$store->allowAttempt('password:'.$p['id'])) throw new DomainException('Muitas tentativas. Aguarde 15 minutos.');
            $old=inputText($input,'senhaAtual',1,128);$new=inputText($input,'novaSenha',8,128);
            if (!password_verify($old,$p['password'])) throw new DomainException('Senha atual incorreta.');
            $p=$store->change($p['id'],function($u)use($new){$u['password']=password_hash($new,PASSWORD_DEFAULT);return $u;});break;
        case 'start':
            $p=$store->change($p['id'],fn($u)=>$game->start($u,(string)($input['mode']??''),(string)($input['grade']??''),(int)($input['lesson']??0),(string)($input['topic']??'')));break;
        case 'answer':
            $p=$store->change($p['id'],fn($u)=>$game->answer($u,(string)($input['run']??''),(int)($input['index']??-1),(int)($input['choice']??-1)));break;
        case 'hint':
            $p=$store->change($p['id'],fn($u)=>$game->hint($u,(string)($input['run']??''),(int)($input['index']??-1)));break;
        case 'next':
        case 'revive':
        case 'finish':
            $p=$store->change($p['id'],function($u)use($game,$action,$input){
                // Uma repetição de rede não avança duas questões nem encerra a partida seguinte.
                if (!$u['active'] || $u['active']['id']!==($input['run']??'') || $u['active']['index']!==($input['index']??-1)) return $u;
                if ($action==='next') return $game->next($u);
                if ($action==='revive') return $game->revive($u);
                return $game->finish($u,true);
            });break;
        case 'admin-list':
            if ($p['role']!=='admin' || !$p['email']) {http_response_code(403);throw new DomainException('Acesso restrito ao administrador.');}
            $extra=['questions'=>array_values($game->questions)];break;
        case 'admin-save':
            if ($p['role']!=='admin' || !$p['email']) {http_response_code(403);throw new DomainException('Acesso restrito ao administrador.');}
            $id=(string)($input['id']??'');
            if ($id!=='' && !isset($game->questions[$id])) throw new DomainException('Questão não encontrada.');
            $q=$id!==''?$game->questions[$id]:['id'=>'custom-'.bin2hex(random_bytes(6)),'images'=>[],'optionImages'=>[],'number'=>0,'page'=>0,'source'=>'Questão cadastrada no MathFund'];
            $q['grade']=(string)($input['grade']??'');
            if (!array_key_exists($q['grade'],Game::GRADES)) throw new DomainException('Série inválida.');
            foreach (['stem','topic','hint','explanation'] as $key) $q[$key]=inputText($input,$key,2,$key==='topic'?60:6000);
            $q['options']=$input['options']??[];
            if (!is_array($q['options']) || count($q['options'])<2 || count($q['options'])>5) throw new DomainException('Use de 2 a 5 alternativas.');
            foreach ($q['options'] as $o) if (!is_string($o) || trim($o)==='' || strlen($o)>2500) throw new DomainException('Confira as alternativas.');
            $q['correct']=(int)($input['correct']??-1);$q['difficulty']=max(1,min(3,(int)($input['difficulty']??2)));
            if ($q['correct']<0 || $q['correct']>=count($q['options'])) throw new DomainException('Selecione o gabarito.');
            if ($store->saveQuestion($q)) {$extra=['saved'=>$q['id'],'catalog'=>(new Game($store))->catalog()];break;}
            $path=__DIR__.'/data/custom.json';$lock=fopen(__DIR__.'/data/custom.lock','c');
            if (!$lock || !flock($lock,LOCK_EX)) throw new RuntimeException('Não foi possível editar as questões.');
            try {
                $all=is_file($path)?json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR):[];
                $all=array_column($all,null,'id');$all[$q['id']]=$q;
                if (file_put_contents($path.'.tmp',json_encode(array_values($all),JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR))===false || !rename($path.'.tmp',$path)) throw new RuntimeException('A pasta data precisa de permissão de escrita.');
            } finally {flock($lock,LOCK_UN);fclose($lock);}
            $extra=['saved'=>$q['id'],'catalog'=>(new Game())->catalog()];break;
        default: http_response_code(404);throw new DomainException('Operação não encontrada.');
    }
    if (Achievements::sync($p)!==$p) $p=$store->change($p['id'],fn($u)=>Achievements::sync($u));
    $newTrophies=array_diff(array_keys($p['trophies']??[]),$previousTrophies);
    $extra['earnedTrophies']=array_values(array_filter(Achievements::catalog(),fn($t)=>in_array($t['id'],$newTrophies,true)));
    echo json_encode(array_merge(['ok'=>true,'csrf'=>$_SESSION['csrf'],'date'=>date('Y-m-d'),'player'=>$game->publicPlayer($p)],$extra),JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
} catch (DomainException|InvalidArgumentException $e) {
    if (http_response_code()<400) http_response_code(400);
    echo json_encode(['ok'=>false,'message'=>$e->getMessage()],JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('MathFund: '.$e->getMessage());http_response_code(500);
    echo json_encode(['ok'=>false,'message'=>'Não foi possível concluir. Confira a configuração do banco e as permissões de storage. O erro detalhado está no log do PHP.'],JSON_UNESCAPED_UNICODE);
}
