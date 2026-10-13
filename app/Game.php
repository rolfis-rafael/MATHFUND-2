<?php
declare(strict_types=1);

require_once __DIR__.'/Achievements.php';

final class Game {
    public array $questions;
    public const LESSON_COUNT = 10;
    public const GRADES = ['7'=>'7º ano','9'=>'9º ano','em1'=>'1ª série','em2'=>'2ª série','em3'=>'3ª série'];
    public function __construct(?Store $store=null) {
        $list = $store?->questions();
        $fromDatabase=$list!==null;
        if (!$fromDatabase) $list=json_decode(file_get_contents(__DIR__.'/../data/questions.json'),true,512,JSON_THROW_ON_ERROR);
        $extra = __DIR__.'/../data/custom.json';
        if (!$fromDatabase && is_file($extra)) $list = array_merge($list,json_decode(file_get_contents($extra),true,512,JSON_THROW_ON_ERROR));
        $this->questions = array_column($list,null,'id');
    }
    public static function fresh(string $id): array {
        return ['id'=>$id,'name'=>'Explorador','email'=>null,'password'=>null,'role'=>'student','avatar'=>'fox','grade'=>'7',
            'xp'=>0,'coins'=>30,'answers'=>0,'correct'=>0,'best'=>0,'streak'=>0,'lastDay'=>null,'days'=>[],
            'mastery'=>[],'mistakes'=>[],'campaign'=>[],'daily'=>[],'history'=>[],'active'=>null,
            'settings'=>['sound'=>false,'motion'=>true,'ranking'=>false,'goal'=>5],
            'trophies'=>[],'playedSeconds'=>0,'playPulse'=>null,
            'onboarding'=>['welcome'=>false,'boss'=>false],'created'=>date(DATE_ATOM)];
    }
    public function ids(string $grade): array {
        return array_keys(array_filter($this->questions,fn($q)=>$q['grade']===$grade));
    }
    public function lesson(string $grade,int $lesson): array {
        $ids=array_values(array_filter($this->ids($grade),fn($id)=>$this->questions[$id]['number']>0)); $n=count($ids);
        $count=$grade==='7'?2:3;
        if (in_array($lesson,[4,9],true)) {
            usort($ids,fn($a,$b)=>($this->questions[$b]['difficulty']<=>$this->questions[$a]['difficulty']) ?: strcmp($a,$b));
            $offset=$lesson===4?$count:0;
            return array_slice($ids,$offset,$count);
        }
        // Distribuição curta; o banco completo segue disponível nos outros modos.
        $offset=$lesson*$count;
        return array_map(fn($i)=>$ids[($offset+$i)%$n],range(0,$count-1));
    }
    public function rules(int $lesson,string $grade='7'): array {
        $boss=in_array($lesson,[4,9],true);
        return ['boss'=>$boss,'bossName'=>$boss?($lesson===9?'Guardião da unidade':'Guardião do caminho'):'',
            'livesMax'=>$boss?2:3,'passPercent'=>$boss?100:($grade==='7'?50:66),
            'notebookLimit'=>$boss?['strokes'=>$lesson===9?8:12,'chars'=>$lesson===9?120:160]:null];
    }
    public function catalog(): array {
        $out=[];
        foreach (self::GRADES as $grade=>$label) {
            $grade=(string)$grade; $topics=[];
            foreach ($this->ids($grade) as $id) $topics[$this->questions[$id]['topic']]=($topics[$this->questions[$id]['topic']]??0)+1;
            $out[]=['id'=>$grade,'label'=>$label,'count'=>count($this->ids($grade)),'ids'=>$this->ids($grade),'topics'=>$topics,'lessons'=>array_map(fn($i)=>array_merge(['number'=>$i,'count'=>count($this->lesson($grade,$i))],$this->rules($i,$grade)),range(0,self::LESSON_COUNT-1))];
        }
        return $out;
    }
    public function start(array $p,string $mode,string $grade,int $lesson=0,string $topic=''): array {
        if ($p['active']) throw new DomainException('Você tem uma partida em andamento. Continue ou encerre antes de começar outra.');
        if (!array_key_exists($grade,self::GRADES)) throw new DomainException('Escolha uma série válida.');
        if (!in_array($mode,['campaign','practice','review','daily','endless'],true)) throw new DomainException('Modo inválido.');
        $ids=$this->ids($grade);
        if ($mode==='campaign') {
            if ($lesson<0 || $lesson>=self::LESSON_COUNT) throw new DomainException('Fase inválida.');
            if ($lesson>0 && ($p['campaign']["$grade-".($lesson-1)]['stars']??0)<1) throw new DomainException('Conclua a fase anterior para desbloquear esta.');
            $ids=$this->lesson($grade,$lesson);
        } elseif ($mode==='daily') {
            if (isset($p['daily'][date('Y-m-d').'-'.$grade])) throw new DomainException('Desafio de hoje concluído nesta série. Você pode continuar no treino livre.');
            usort($ids,fn($a,$b)=>strcmp(hash('sha256',date('Y-m-d').$a),hash('sha256',date('Y-m-d').$b)));
            $ids=array_slice($ids,0,5);
        } else {
            if ($mode==='review') $ids=array_values(array_filter($ids,fn($id)=>isset($p['mistakes'][$id])));
            if ($mode==='practice' && $topic!=='') $ids=array_values(array_filter($ids,fn($id)=>$this->questions[$id]['topic']===$topic));
            if (!$ids) throw new DomainException($mode==='review'?'Nenhuma questão para revisar nesta série. Que tal treinar?':'Não há questões nesse filtro.');
            shuffle($ids);
            if ($mode==='practice') { usort($ids,fn($a,$b)=>(($p['mastery'][$a]??0)<=>($p['mastery'][$b]??0))); $ids=array_slice($ids,0,8); }
            if ($mode==='review') $ids=array_slice($ids,0,8);
        }
        $p['grade']=$grade;
        $rules=$this->rules($mode==='campaign'?$lesson:0,$grade);
        $p['active']=array_merge($rules,['id'=>bin2hex(random_bytes(8)),'mode'=>$mode,'grade'=>$grade,'lesson'=>$lesson,'topic'=>$topic,'ids'=>$ids,
            'index'=>0,'lives'=>in_array($mode,['campaign','endless'],true)?$rules['livesMax']:null,'combo'=>0,'maxCombo'=>0,'correct'=>0,'answered'=>0,
            'xp'=>0,'coins'=>0,'hint'=>false,'revived'=>false,'feedback'=>null,'log'=>[],'started'=>time(),'day'=>date('Y-m-d')]);
        return $p;
    }
    public function answer(array $p,string $run,int $index,int $choice): array {
        $r=$p['active'];
        if (!$r || $r['id']!==$run || $r['index']!==$index) throw new DomainException('Esta questão mudou. Atualize a partida.');
        if ($r['feedback']) return $p;
        if ($r['lives']!==null && $r['lives']<=0) throw new DomainException('A partida terminou.');
        $q=$this->questions[$r['ids'][$r['index']]];
        if ($choice<0 || $choice>=count($q['options'])) throw new DomainException('Escolha uma alternativa.');
        $ok=$choice===$q['correct']; $r['answered']++; $p['answers']++;
        $xp=0;$coins=0;
        if ($ok) {
            $r['correct']++;$p['correct']++;$r['combo']++;$r['maxCombo']=max($r['maxCombo'],$r['combo']);
            $multiplier=min(3,1+intdiv($r['combo']-1,3));
            $xp=($p['mastery'][$q['id']]??0)>0 ? 5 : (20*$multiplier);
            if ($r['hint']) $xp=max(2,(int)floor($xp/2));
            $coins=($p['mastery'][$q['id']]??0)>0?1:5;
            $p['mastery'][$q['id']]=($p['mastery'][$q['id']]??0)+1;
            unset($p['mistakes'][$q['id']]);
        } else {
            $r['combo']=0;
            if ($r['lives']!==null) $r['lives']--;
            $p['mistakes'][$q['id']]=date(DATE_ATOM);
        }
        $p['xp']+=$xp;$p['coins']+=$coins;$r['xp']+=$xp;$r['coins']+=$coins;
        $today=date('Y-m-d');
        if ($p['lastDay']!==$today) {
            $p['streak']=$p['lastDay']===date('Y-m-d',strtotime('-1 day'))?$p['streak']+1:1;
            $p['lastDay']=$today;
        }
        $p['days'][$today]=($p['days'][$today]??0)+1;$p['days']=array_slice($p['days'],-90,null,true);
        $r['feedback']=['correct'=>$ok,'choice'=>$choice,'answer'=>$q['correct'],'explanation'=>$q['explanation'],'xp'=>$xp,'coins'=>$coins,'hintUsed'=>$r['hint']];
        $r['log'][]=['id'=>$q['id'],'choice'=>$choice,'correct'=>$ok];
        // O histórico do sem-fim guarda apenas as últimas 100 respostas para não crescer indefinidamente.
        $r['log']=array_slice($r['log'],-100); $p['active']=$r;
        return $p;
    }
    public function hint(array $p,string $run,int $index): array {
        $r=$p['active'];
        if (!$r || $r['id']!==$run || $r['index']!==$index) throw new DomainException('Esta questão mudou.');
        if ($r['hint'] || $r['feedback']) return $p;
        $cost=in_array($r['mode'],['practice','review'],true)?0:10;
        if ($p['coins']<$cost) throw new DomainException('Você precisa de 10 moedas. No treino e na revisão, as dicas são gratuitas.');
        $p['coins']-=$cost;$p['active']['hint']=true;return $p;
    }
    public function revive(array $p): array {
        $r=$p['active'];
        if ($r['boss']??false) throw new DomainException('Chefes não têm resgate. Tente a fase novamente!');
        if (!$r || $r['lives']!==0 || $r['revived']) throw new DomainException('O resgate só pode ser usado uma vez, quando as vidas acabarem.');
        if ($p['coins']<20) throw new DomainException('Você precisa de 20 moedas para o resgate.');
        $p['coins']-=20;$p['active']['lives']=1;$p['active']['revived']=true;return $p;
    }
    public function next(array $p): array {
        $r=$p['active'];
        if (!$r || !$r['feedback']) throw new DomainException('Responda à questão antes de continuar.');
        if ($r['lives']===0 || ($r['mode']!=='endless' && $r['index']+1>=count($r['ids']))) return $this->finish($p,false);
        $r['index']++;
        if ($r['index']>=count($r['ids'])) {
            $last=end($r['ids']);$r['ids']=$this->ids($r['grade']);shuffle($r['ids']);
            if (count($r['ids'])>1 && $r['ids'][0]===$last) [$r['ids'][0],$r['ids'][1]]=[$r['ids'][1],$r['ids'][0]];
            $r['index']=0;
        }
        $r['hint']=false;$r['feedback']=null;$p['active']=$r;return $p;
    }
    public function finish(array $p,bool $abandoned): array {
        $r=$p['active']; if (!$r) throw new DomainException('Não há partida em andamento.');
        $total=$r['mode']==='endless'?$r['answered']:count($r['ids']);
        $percent=$total?(int)round($r['correct']/$total*100):0;
        $complete=!$abandoned && $r['answered']===$total;
        $pass=$r['passPercent']??70;$boss=$r['boss']??false;
        $stars=$complete && $percent>=$pass?($percent===100?3:($percent>=($boss?90:80)?2:1)):0;
        $bonus=0;
        if ($r['mode']==='campaign' && $complete) {
            $key=$r['grade'].'-'.$r['lesson'];$old=$p['campaign'][$key]['stars']??0;
            $bonus=max(0,$stars-$old)*10;
            $p['campaign'][$key]=['stars'=>max($old,$stars),'percent'=>max($p['campaign'][$key]['percent']??0,$percent)];
        }
        if ($r['mode']==='daily' && $complete) {
            $key=$r['day'].'-'.$r['grade'];
            if (!isset($p['daily'][$key])) { $bonus=20;$p['daily'][$key]=['correct'=>$r['correct'],'total'=>$total]; }
            $p['daily']=array_slice($p['daily'],-90,null,true);
        }
        if ($r['mode']==='endless') $p['best']=max($p['best'],$r['correct']);
        $p['coins']+=$bonus;
        $summary=['id'=>$r['id'],'mode'=>$r['mode'],'grade'=>$r['grade'],'lesson'=>$r['lesson'],'correct'=>$r['correct'],'total'=>$total,
            'answered'=>$r['answered'],'percent'=>$percent,'stars'=>$stars,'xp'=>$r['xp'],'coins'=>$r['coins']+$bonus,
            'maxCombo'=>$r['maxCombo'],'complete'=>$complete,'abandoned'=>$abandoned,'date'=>date(DATE_ATOM),'log'=>$r['log'],
            'boss'=>$boss,'passPercent'=>$pass];
        array_unshift($p['history'],$summary);$p['history']=array_slice($p['history'],0,20);$p=Achievements::tick($p,false);$p['active']=null;return Achievements::sync($p);
    }
    public function publicPlayer(array $p): array {
        $p=Achievements::sync($p);$p['trophySummary']=Achievements::summary($p);$p['reportCard']=Achievements::grades($p);
        unset($p['password'],$p['playPulse']);
        $r=$p['active'];
        if ($r) {
            $q=$this->questions[$r['ids'][$r['index']]];unset($q['correct'],$q['explanation']);
            if (!$r['hint']) unset($q['hint']);
            $r['total']=$r['mode']==='endless'?null:count($r['ids']);$r['question']=$q;
            unset($r['ids'],$r['log']);$p['active']=$r;
        }
        $p['mastered']=count($p['mastery']);$p['level']=1+intdiv($p['xp'],200);
        return $p;
    }
}
