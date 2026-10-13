<?php
declare(strict_types=1);
require __DIR__.'/../app/Game.php';
require __DIR__.'/../app/Store.php';
date_default_timezone_set('America/Sao_Paulo');
$checks=0;
function check(bool $value,string $message): void {global $checks;if (!$value) throw new RuntimeException($message);$checks++;}
function mustFail(callable $fn,string $message): void {try{$fn();}catch(DomainException $e){check(true,$message);return;}throw new RuntimeException($message);}
$g=new Game();$p=Game::fresh(str_repeat('a',64));
check(count($g->questions)>=99,'Banco incompleto');
foreach ($g->questions as $q) {
    check(count($q['options'])>=2 && isset($q['options'][$q['correct']]),'Gabarito sem alternativa');
    check($q['hint']!=='' && $q['explanation']!=='','Falta dica/explicação');
}
mustFail(fn()=>$g->start($p,'campaign','7',1),'Fase bloqueada liberada');
$p=$g->start($p,'campaign','7',0);$r=$p['active'];$public=$g->publicPlayer($p);
check(!isset($public['active']['question']['correct'],$public['active']['question']['hint'],$public['active']['question']['explanation']),'Resposta vazou antes da tentativa');
$p=$g->hint($p,$r['id'],0);check($p['coins']===20,'Dica não cobrada');
$p=$g->hint($p,$r['id'],0);check($p['coins']===20,'Dica cobrada duas vezes');
$correct=$g->questions[$r['ids'][0]]['correct'];
$p=$g->answer($p,$r['id'],0,$correct);$once=$p;
$p=$g->answer($p,$r['id'],0,$correct);check($p===$once,'Resposta duplicada alterou progresso');
check($p['xp']===10,'Redução do XP com dica');
$p=$g->next($p);mustFail(fn()=>$g->answer($p,$r['id'],0,0),'Resposta de questão anterior aceita');
$p=$g->finish($p,true);check(!$p['campaign'],'Partida abandonada liberou fase');
// Percorre todas as fases reais, validando as transições e a conclusão.
$p=Game::fresh(str_repeat('b',64));$seen=[];$answerCount=0;
foreach (Game::GRADES as $grade=>$label) {
    for ($phase=0;$phase<Game::LESSON_COUNT;$phase++) {
        $p=$g->start($p,'campaign',(string)$grade,$phase);
        while ($p['active']) {
            $r=$p['active'];$id=$r['ids'][$r['index']];$seen[$id]=true;$answerCount++;
            $p=$g->answer($p,$r['id'],$r['index'],$g->questions[$id]['correct']);$p=$g->next($p);
        }
        check($p['campaign'][$grade.'-'.$phase]['stars']===3,'Fase perfeita sem 3 estrelas');
    }
}
check(count($seen)>50,'Campanha curta com pouca variedade');
check($p['correct']===$answerCount,'Contagem total de acertos');
check(count($p['history'])===20,'Histórico sem limite');
// Chefes: regras mais difíceis, bloqueio no servidor, notas e compatibilidade.
foreach ($g->catalog() as $unit) {
    check(count($unit['lessons'])===10,'Unidade sem 10 fases');
    check(count(array_filter($unit['lessons'],fn($l)=>$l['boss']))===2,'Unidade sem dois chefes');
    foreach ($unit['lessons'] as $l) check($l['count']===($unit['id']==='7'?2:3),'Quantidade por unidade incorreta');
    foreach ($unit['lessons'] as $l) check(count(array_unique($g->lesson($unit['id'],$l['number'])))===$l['count'],'Repetição dentro da fase');
}
foreach ([4,9] as $phase) {
    $b=Game::fresh(str_repeat('8',64));$b['campaign']['7-'.($phase-1)]=['stars'=>1,'percent'=>80];
    $b=$g->start($b,'campaign','7',$phase);$r=$b['active'];
    check($r['boss']&&$r['lives']===2&&$r['passPercent']===100,'Regras de chefe incorretas');
    check($r['notebookLimit']['strokes']===($phase===4?12:8),'Limite de traços incorreto');
    check($r['notebookLimit']['chars']===($phase===4?160:120),'Limite de caracteres incorreto');
    check(count($r['ids'])===2,'Tamanho do chefe incorreto');
    $loss=$b;
    for($i=0;$i<2;$i++){$r=$loss['active'];$q=$g->questions[$r['ids'][$r['index']]];$loss=$g->answer($loss,$r['id'],$r['index'],($q['correct']+1)%count($q['options']));if($i===0)$loss=$g->next($loss);}
    mustFail(fn()=>$g->revive($loss),'Resgate aceito no chefe');
    $loss=$g->next($loss);check($loss['history'][0]['stars']===0,'Chefe perdido liberou fase');
    while($b['active']){$r=$b['active'];$q=$g->questions[$r['ids'][$r['index']]];$choice=$r['index']===0?($q['correct']+1)%count($q['options']):$q['correct'];$b=$g->answer($b,$r['id'],$r['index'],$choice);$b=$g->next($b);}
    check($b['history'][0]['stars']===0&&$b['history'][0]['boss'],'Chefe com erro não pode liberar próxima fase');
}
$legacy=Game::fresh(str_repeat('7',64));$legacy=$g->start($legacy,'campaign','7',0);
foreach(['boss','bossName','passPercent','livesMax','notebookLimit'] as $key)unset($legacy['active'][$key]);
while($legacy['active']){$r=$legacy['active'];$legacy=$g->answer($legacy,$r['id'],$r['index'],$g->questions[$r['ids'][$r['index']]]['correct']);$legacy=$g->next($legacy);}
check($legacy['history'][0]['stars']===3,'Partida da versão anterior incompatível');
mustFail(fn()=>$g->start(Game::fresh(str_repeat('6',64)),'campaign','7',10),'Fase fora do catálogo aceita');
// Uma resposta errada ainda permite avançar nas fases comuns curtas.
foreach(['7','9'] as $grade){
    $short=$g->start(Game::fresh(str_repeat('5',64)),'campaign',$grade,0);
    while($short['active']){$r=$short['active'];$q=$g->questions[$r['ids'][$r['index']]];$choice=$r['index']===0?($q['correct']+1)%count($q['options']):$q['correct'];$short=$g->answer($short,$r['id'],$r['index'],$choice);$short=$g->next($short);}
    check($short['history'][0]['stars']===1,'Fase curta com um erro não liberou próxima');
}
// Falha, revisão, resgate e custo unitário.
$p=Game::fresh(str_repeat('c',64));$p['coins']=100;$p=$g->start($p,'endless','9');
for($i=0;$i<3;$i++){$r=$p['active'];$q=$g->questions[$r['ids'][$r['index']]];$p=$g->answer($p,$r['id'],$r['index'],($q['correct']+1)%count($q['options']));if($i<2)$p=$g->next($p);}
check($p['active']['lives']===0,'Vidas não acabaram');check(count($p['mistakes'])===3,'Erros não registrados');
$p=$g->revive($p);check($p['coins']===80&&$p['active']['lives']===1,'Resgate incorreto');
mustFail(fn()=>$g->revive($p),'Resgate repetido aceito');
$p=$g->finish($p,true);$p=$g->start($p,'review','9');$r=$p['active'];$id=$r['ids'][0];$p=$g->hint($p,$r['id'],0);check($p['coins']===80,'Revisão cobrou dica');
$p=$g->answer($p,$r['id'],0,$g->questions[$id]['correct']);check(!isset($p['mistakes'][$id]),'Acerto não removeu da revisão');
// Diário: mesma seleção no dia, bônus apenas uma vez.
$a=Game::fresh(str_repeat('d',64));$b=Game::fresh(str_repeat('e',64));$a=$g->start($a,'daily','em2');$b=$g->start($b,'daily','em2');
check($a['active']['ids']===$b['active']['ids'],'Diário não determinístico');
while($a['active']){$r=$a['active'];$id=$r['ids'][$r['index']];$a=$g->answer($a,$r['id'],$r['index'],$g->questions[$id]['correct']);$a=$g->next($a);}
check($a['coins']===75,'Bônus diário incorreto');mustFail(fn()=>$g->start($a,'daily','em2'),'Diário repetido aceito');
// Sem fim: ciclo sem repetições, inclusive na fronteira entre dois ciclos.
$p=Game::fresh(str_repeat('f',64));$p=$g->start($p,'endless','7');$first=$p['active']['ids'];
check(count(array_unique($first))===20,'Questão repetida dentro do ciclo');
for($i=0;$i<20;$i++){$r=$p['active'];$id=$r['ids'][$r['index']];$p=$g->answer($p,$r['id'],$r['index'],$g->questions[$id]['correct']);$p=$g->next($p);}
check($p['active']['index']===0 && $p['active']['answered']===20,'Ciclo sem fim não reiniciou corretamente');
check($p['active']['ids'][0]!==end($first),'Repetição na fronteira de ciclos');
$r=$p['active'];$id=$r['ids'][0];$before=$p['xp'];$p=$g->answer($p,$r['id'],0,$g->questions[$id]['correct']);check($p['xp']-$before===5,'Repetição deu XP de questão nova');
// Troféus, boletim, migração de progresso e tempo ativo.
$t=Game::fresh(str_repeat('3',64));
check(count(Achievements::catalog())===38,'Catálogo de troféus incompleto');
check(Achievements::summary($t)['total']===0,'Perfil novo já tem troféus');
$t['campaign']['7-0']=['stars'=>3,'percent'=>100];$t=Achievements::sync($t);
check(Achievements::summary($t)['counts']['common']===2,'Primeira fase perfeita deve dar dois comuns');
check(Achievements::sync($t)===$t,'Troféu repetido alterou data');
check(Achievements::grades($t)['units']['7']['average']===1.0,'Média da unidade incorreta');
check(Achievements::grades($t)['average']===0.2,'Média geral incorreta');
foreach(Achievements::GRADES as $grade)foreach(range(0,9) as $i)$t['campaign']["$grade-$i"]=['stars'=>1,'percent'=>70];
$t=Achievements::sync($t);check(isset($t['trophies']['game-complete'])&&!isset($t['trophies']['platinum']),'Platina liberada sem todas as estrelas');
foreach(Achievements::GRADES as $grade)foreach(range(0,9) as $i)$t['campaign']["$grade-$i"]=['stars'=>3,'percent'=>100];
$t=Achievements::sync($t);$ts=Achievements::summary($t);
check($ts['total']===38&&$ts['counts']===['common'=>10,'rare'=>20,'spectacular'=>7,'platinum'=>1],'Contagens da coleção completa incorretas');
check(Achievements::grades($t)['average']===10.0,'Aluno nota 10 não chegou a 10');
$legacyT=$t;unset($legacyT['trophies']);check(Achievements::summary(Achievements::sync($legacyT))['total']===38,'Troféus não reconheceram progresso anterior');
$timer=Game::fresh(str_repeat('4',64));$timer['active']=['id'=>'run'];
$timer=Achievements::tick($timer,true,1000);$timer=Achievements::tick($timer,true,1015);
check($timer['playedSeconds']===15,'Tempo ativo não contabilizado');
$timer=Achievements::tick($timer,true,1015);check($timer['playedSeconds']===15,'Sinal duplicado contou tempo extra');
$timer=Achievements::tick($timer,false,1020);$timer=Achievements::tick($timer,false,1028);check($timer['playedSeconds']===20,'Tempo pausado foi contabilizado');
$timer=Achievements::tick($timer,true,1040);$timer=Achievements::tick($timer,true,1500);check($timer['playedSeconds']===20,'Tempo offline foi contabilizado');
// Persistência: gravar/reabrir, unicidade de email e ranking privado.
$dir=sys_get_temp_dir().'/mathfund-test-'.bin2hex(random_bytes(4));
$store=new Store(['driver'=>'files','storage'=>$dir]);
$u=Game::fresh(str_repeat('1',64));$u['email']='teste@example.test';$u['password']=password_hash('test-password',PASSWORD_DEFAULT);$u['xp']=123;
$store->change($u['id'],fn()=>$u);check($store->get($u['id'])['xp']===123,'Estado não persistido');check($store->findEmail($u['email'])['id']===$u['id'],'Busca de login falhou');
check(count($store->ranking())===0,'Perfil privado no ranking');
$store->change($u['id'],function($x){$x['settings']['ranking']=true;return $x;});check(count($store->ranking())===1,'Perfil público não listado');
$v=Game::fresh(str_repeat('2',64));$v['email']=$u['email'];
mustFail(fn()=>$store->change($v['id'],fn()=>$v),'Email duplicado aceito');check($store->get($u['id'])['xp']===123,'Email duplicado sobrescreveu conta');
$v['email']='trofeus@example.test';$v['settings']['ranking']=true;$v['campaign']['7-0']=['stars'=>3,'percent'=>100];$store->change($v['id'],fn()=>$v);check($store->ranking()[0]['id']===$v['id'],'Ranking não priorizou troféus antes de XP');
foreach(glob($dir.'/*') as $file)unlink($file);rmdir($dir);
echo "OK: $checks verificações de conteúdo, jogo e persistência.\n";
