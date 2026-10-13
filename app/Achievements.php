<?php
declare(strict_types=1);

final class Achievements {
    public const GRADES=['7','9','em1','em2','em3'];
    public const POINTS=['common'=>15,'rare'=>30,'spectacular'=>90,'platinum'=>180];
    public static function catalog(): array {
        $all=[];
        foreach(self::GRADES as $unit=>$grade){
            $u=$unit+1;
            foreach([
                ['first','Primeiro passo','Passe da primeira fase','common'],
                ['boss','Primeiro chefão','Derrote um chefe','rare'],
                ['complete','Unidade vencida','Conclua as 10 fases','rare'],
                ['bosses','Caçador de chefes','Derrote os dois chefes','rare'],
                ['star','Três estrelas!','Ganhe 3 estrelas em uma fase','common'],
                ['boss-star','Chefe perfeito','Ganhe 3 estrelas em um chefe','rare'],
                ['perfect','Aluno nota 10','Ganhe 3 estrelas nas 10 fases','spectacular']
            ] as [$key,$title,$description,$tier])$all[]=['id'=>"$grade-$key",'grade'=>$grade,'unit'=>$u,'title'=>"$title · Unidade $u",'description'=>"$description da unidade $u.",'tier'=>$tier];
        }
        $all[]=['id'=>'game-complete','grade'=>null,'unit'=>null,'title'=>'Zerou o MathFund!','description'=>'Conclua as 50 fases das cinco unidades.','tier'=>'spectacular'];
        $all[]=['id'=>'game-perfect','grade'=>null,'unit'=>null,'title'=>'Todas as estrelas!','description'=>'Ganhe 3 estrelas em todas as 50 fases do MathFund.','tier'=>'spectacular'];
        $all[]=['id'=>'platinum','grade'=>null,'unit'=>null,'title'=>'Platina · 100% MathFund','description'=>'Conquiste todos os outros 37 troféus.','tier'=>'platinum'];
        return $all;
    }
    public static function eligible(array $p): array {
        $earned=[];$allComplete=true;$allPerfect=true;
        foreach(self::GRADES as $grade){
            $stars=array_map(fn($i)=>(int)($p['campaign']["$grade-$i"]['stars']??0),range(0,9));
            $complete=min($stars)>0;$perfect=min($stars)===3;
            $rules=['first'=>$stars[0]>0,'boss'=>$stars[4]>0||$stars[9]>0,'complete'=>$complete,'bosses'=>$stars[4]>0&&$stars[9]>0,'star'=>max($stars)===3,'boss-star'=>$stars[4]===3||$stars[9]===3,'perfect'=>$perfect];
            foreach($rules as $key=>$yes)if($yes)$earned[]="$grade-$key";
            $allComplete=$allComplete&&$complete;$allPerfect=$allPerfect&&$perfect;
        }
        if($allComplete)$earned[]='game-complete';if($allPerfect)$earned[]='game-perfect';
        if(count($earned)===37)$earned[]='platinum';return $earned;
    }
    public static function sync(array $p): array {
        $p['trophies']=$p['trophies']??[];
        foreach(self::eligible($p) as $id)if(!isset($p['trophies'][$id]))$p['trophies'][$id]=date(DATE_ATOM);
        return $p;
    }
    public static function summary(array $p): array {
        $counts=array_fill_keys(array_keys(self::POINTS),0);$points=0;
        foreach(self::catalog() as $t)if(isset($p['trophies'][$t['id']])){$counts[$t['tier']]++;$points+=self::POINTS[$t['tier']];}
        return ['counts'=>$counts,'total'=>array_sum($counts),'available'=>38,'points'=>$points,'playedSeconds'=>(int)($p['playedSeconds']??0),'minutes'=>intdiv((int)($p['playedSeconds']??0),60)];
    }
    public static function grades(array $p): array {
        $units=[];$sum=0;
        foreach(self::GRADES as $grade){
            $lessons=array_map(fn($i)=>round(max(0,min(100,(float)($p['campaign']["$grade-$i"]['percent']??0)))/10,2),range(0,9));
            $sum+=array_sum($lessons);$units[$grade]=['lessons'=>$lessons,'average'=>round(array_sum($lessons)/10,2)];
        }
        return ['units'=>$units,'average'=>round($sum/50,2)];
    }
    // Conta apenas intervalos curtos confirmados pelo servidor, nunca tempo offline.
    public static function tick(array $p,bool $running,?int $now=null): array {
        $now=$now??time();$last=$p['playPulse']??null;
        if($last!==null&&$now>=$last&&$now-$last<=30)$p['playedSeconds']=(int)($p['playedSeconds']??0)+$now-$last;
        $p['playPulse']=$running&&$p['active']?$now:null;return $p;
    }
}
