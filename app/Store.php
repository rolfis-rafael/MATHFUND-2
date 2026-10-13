<?php
declare(strict_types=1);

require_once __DIR__.'/Achievements.php';

final class Store {
    private array $config;
    private ?PDO $pdo = null;

    public function __construct(array $config) {
        $this->config = $config;
        if (!is_dir($config['storage']) && !mkdir($config['storage'], 0700, true)) {
            throw new RuntimeException('A pasta storage precisa de permissão de escrita.');
        }
        if ($config['driver'] === 'mysql') {
            $m = $config['mysql'];
            $this->pdo = new PDO("mysql:host={$m['host']};port={$m['port']};dbname={$m['database']};charset=utf8mb4", $m['user'], $m['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }
    }

    private function path(string $id): string {
        if (!preg_match('/^[a-f0-9]{32,64}$/D', $id)) throw new InvalidArgumentException('Perfil inválido.');
        return $this->config['storage'] . '/player-' . $id . '.json';
    }

    public function get(string $id): ?array {
        if ($this->pdo) {
            $s = $this->pdo->prepare('SELECT state FROM mf_players WHERE id = ?'); $s->execute([$id]);
            $raw = $s->fetchColumn();
            return $raw ? json_decode($raw, true, 512, JSON_THROW_ON_ERROR) : null;
        }
        $lock = fopen($this->config['storage'] . '/store.lock', 'c');
        if (!$lock || !flock($lock, LOCK_SH)) throw new RuntimeException('Não foi possível ler o progresso.');
        try { $file = $this->path($id); return is_file($file) ? json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR) : null; }
        finally { flock($lock, LOCK_UN); fclose($lock); }
    }

    public function findEmail(string $email): ?array {
        if ($this->pdo) {
            $s = $this->pdo->prepare('SELECT state FROM mf_players WHERE email = ?'); $s->execute([$email]);
            $raw = $s->fetchColumn(); return $raw ? json_decode($raw, true, 512, JSON_THROW_ON_ERROR) : null;
        }
        $index = $this->config['storage'] . '/email-' . hash('sha256', $email) . '.idx';
        return is_file($index) ? $this->get(trim(file_get_contents($index))) : null;
    }

    // A resposta e a recompensa são gravadas juntas para impedir créditos duplicados.
    public function change(string $id, callable $fn): array {
        if ($this->pdo) {
            $this->pdo->beginTransaction();
            try {
                $s = $this->pdo->prepare('SELECT state FROM mf_players WHERE id = ? FOR UPDATE'); $s->execute([$id]);
                $raw = $s->fetchColumn(); $player = $raw ? json_decode($raw, true, 512, JSON_THROW_ON_ERROR) : null;
                $player = $fn($player);
                $values=[$player['email'],json_encode($player,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),$player['xp'],(int)($player['email'] && $player['settings']['ranking']),$id];
                // Não usar UPSERT: um e-mail duplicado deve falhar, nunca atualizar outra conta.
                $s = $this->pdo->prepare($raw ? 'UPDATE mf_players SET email=?,state=?,xp=?,listed=? WHERE id=?' : 'INSERT INTO mf_players (email,state,xp,listed,id) VALUES (?,?,?,?,?)');
                $s->execute($values);
                $this->pdo->commit(); return $player;
            } catch (Throwable $e) { if ($this->pdo->inTransaction()) $this->pdo->rollBack(); throw $e; }
        }
        $lock = fopen($this->config['storage'] . '/store.lock', 'c');
        if (!$lock || !flock($lock, LOCK_EX)) throw new RuntimeException('Não foi possível salvar. Tente novamente.');
        try {
            $path = $this->path($id);
            $p = is_file($path) ? json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR) : null;
            $p = $fn($p);
            if ($p['email']) {
                $index = $this->config['storage'] . '/email-' . hash('sha256', $p['email']) . '.idx';
                if (is_file($index) && trim(file_get_contents($index)) !== $id) throw new DomainException('Este e-mail já está cadastrado.');
            }
            $tmp = $path . '.tmp';
            if (file_put_contents($tmp,json_encode($p,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)) === false || !rename($tmp,$path)) throw new RuntimeException('Falha ao salvar o progresso.');
            if (isset($index) && file_put_contents($index,$id) === false) throw new RuntimeException('Falha ao salvar o cadastro.');
            return $p;
        } finally { flock($lock,LOCK_UN); fclose($lock); }
    }

    public function questions(): ?array {
        if (!$this->pdo) return null;
        try {$raw=$this->pdo->query('SELECT payload FROM mf_questions ORDER BY grade, question_number, id')->fetchAll(PDO::FETCH_COLUMN);}
        catch (PDOException $e) {throw new DomainException('Importe database.sql no phpMyAdmin para criar o banco de perguntas.');}
        if (!$raw) throw new DomainException('O banco de perguntas está vazio. Importe database.sql no phpMyAdmin.');
        return array_map(fn($q)=>json_decode($q,true,512,JSON_THROW_ON_ERROR),$raw);
    }
    public function saveQuestion(array $q): bool {
        if (!$this->pdo) return false;
        $s=$this->pdo->prepare('INSERT INTO mf_questions (id,grade,question_number,topic,difficulty,payload) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE grade=VALUES(grade),question_number=VALUES(question_number),topic=VALUES(topic),difficulty=VALUES(difficulty),payload=VALUES(payload)');
        $s->execute([$q['id'],$q['grade'],$q['number'],$q['topic'],$q['difficulty'],json_encode($q,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);return true;
    }
    public function ranking(): array {
        if ($this->pdo) $states=$this->pdo->query('SELECT state FROM mf_players WHERE listed = 1')->fetchAll(PDO::FETCH_COLUMN);
        else {$states=[];foreach(glob($this->config['storage'].'/player-*.json')?:[] as $file)$states[]=file_get_contents($file);}
        $players=[];
        foreach($states as $raw){
            $p=json_decode($raw,true);if(!$p||!$p['email']||!$p['settings']['ranking'])continue;
            $p=Achievements::sync($p);$summary=Achievements::summary($p);
            $players[]=['id'=>$p['id'],'name'=>$p['name'],'avatar'=>$p['avatar'],'xp'=>$p['xp'],'best'=>$p['best'],'streak'=>$p['streak'],
                'trophySummary'=>$summary,'average'=>Achievements::grades($p)['average']];
        }
        usort($players,fn($a,$b)=>($b['trophySummary']['points']<=>$a['trophySummary']['points'])?:($b['average']<=>$a['average'])?:($b['xp']<=>$a['xp'])?:strcmp($a['id'],$b['id']));
        return array_slice($players,0,50);
    }

    public function allowAttempt(string $key): bool {
        $file=$this->config['storage'].'/rate-'.hash('sha256',$key).'.json';
        $f=fopen($file,'c+');
        if (!$f || !flock($f,LOCK_EX)) return false;
        try {
            $data=json_decode(stream_get_contents($f),true) ?: ['start'=>time(),'count'=>0];
            if (time()-$data['start']>900) $data=['start'=>time(),'count'=>0];
            if ($data['count']>=15) return false;
            $data['count']++; rewind($f);ftruncate($f,0);fwrite($f,json_encode($data));return true;
        } finally {flock($f,LOCK_UN);fclose($f);}
    }
}
