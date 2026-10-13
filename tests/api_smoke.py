"""Teste real do PHP via CGI, sem servidor HTTP. Uso: python tests/api_smoke.py /caminho/php-cgi"""
import json, os, subprocess, sys, tempfile, http.cookies
from pathlib import Path

ROOT=Path(__file__).resolve().parents[1]
PHP=str(Path(sys.argv[1]).resolve()) if len(sys.argv)>1 else 'php-cgi'
checks=0
def check(value,message):
    global checks
    assert value,message
    checks+=1

with tempfile.TemporaryDirectory(prefix='mathfund-api-') as temp:
    class Client:
        def __init__(self): self.cookies={};self.csrf=''
        def call(self,action,data=None,token=None):
            body=json.dumps(data).encode() if data is not None else b''
            env=os.environ.copy();env.update({
                'MATHFUND_STORAGE':temp+'/store','REDIRECT_STATUS':'200','REQUEST_METHOD':'POST' if data is not None else 'GET',
                'SCRIPT_FILENAME':str(ROOT/'api.php'),'SCRIPT_NAME':'/mathfund/api.php','DOCUMENT_ROOT':str(ROOT),
                'QUERY_STRING':'action='+action,'REQUEST_URI':'/mathfund/api.php?action='+action,'SERVER_NAME':'localhost',
                'HTTP_HOST':'localhost','SERVER_PROTOCOL':'HTTP/1.1','REMOTE_ADDR':'127.0.0.1',
                'CONTENT_TYPE':'application/json','CONTENT_LENGTH':str(len(body)),
                'HTTP_COOKIE':'; '.join(k+'='+v for k,v in self.cookies.items()),'HTTP_X_CSRF_TOKEN':self.csrf if token is None else token,
            })
            result=subprocess.run([PHP,'-d','session.save_path='+temp,'-d','display_errors=0'],input=body,env=env,capture_output=True)
            check(result.returncode==0,'PHP CGI falhou: '+result.stderr.decode())
            raw=result.stdout.decode();headers,content=raw.split('\r\n\r\n',1)
            for line in headers.splitlines():
                if line.lower().startswith('set-cookie:'):
                    cookie=http.cookies.SimpleCookie();cookie.load(line.split(':',1)[1])
                    for k,v in cookie.items():self.cookies[k]=v.value
            try: parsed=json.loads(content)
            except: raise AssertionError(content[:500]+'\n'+result.stderr.decode())
            if parsed.get('csrf'):self.csrf=parsed['csrf']
            return parsed
    a=Client();boot=a.call('bootstrap');check(len(boot['trophyCatalog'])==38,'Catálogo de troféus');check(boot['player']['reportCard']['average']==0,'Média inicial');check(boot['player']['trophySummary']['total']==0,'Troféus iniciais');check(boot['ok'],'Bootstrap');check(sum(g['count'] for g in boot['catalog'])>=99,'Catálogo')
    check(not a.call('profile',{'nome':'Intruso'},token='invalid')['ok'],'CSRF não bloqueou')
    check(not a.call('admin-list')['ok'],'Visitante acessou gabaritos')
    start=a.call('start',{'mode':'campaign','grade':'7','lesson':0});r=start['player']['active'];q=r['question']
    check('correct' not in q and 'explanation' not in q and 'hint' not in q,'Resposta vazou')
    qs={q['id']:q for q in json.loads((ROOT/'data/questions.json').read_text())}
    payload={'run':r['id'],'index':r['index'],'choice':qs[q['id']]['correct']}
    answered=a.call('answer',payload);xp=answered['player']['xp'];repeated=a.call('answer',payload)
    check(repeated['player']['xp']==xp,'Clique repetido ganhou XP')
    check(a.call('bootstrap')['player']['active']['feedback']['correct'],'Resposta não sobrevive ao reload')
    nxt=a.call('next',{'run':r['id'],'index':r['index']});idx=nxt['player']['active']['index']
    dup=a.call('next',{'run':r['id'],'index':r['index']});check(dup['player']['active']['index']==idx,'Clique duplo avançou duas questões')
    guest_id=dup['player']['id']
    reg=a.call('register',{'nome':'Aluno de teste','email':'aluno@example.test','senha':'senha-de-teste-2026'})
    check(reg['ok'] and reg['player']['id']==guest_id and reg['player']['xp']==xp,'Cadastro perdeu progresso')
    check('password' not in reg['player'],'Hash exposto')
    check(a.call('ranking')['ranking']==[],'Conta entrou no ranking sem consentimento')
    a.call('profile',{'nome':'Aluno de teste','grade':'7','avatar':'owl','ranking':True})
    board=a.call('ranking')['ranking'];check(len(board)==1 and 'email' not in board[0],'Ranking expôs email ou não salvou')
    check(not a.call('admin-save',{'id':'7-01'})['ok'],'Usuário comum editou banco')
    a.call('logout',{});check(a.call('bootstrap')['player']['email'] is None,'Logout falhou')
    check(not a.call('login',{'email':'aluno@example.test','senha':'errada'})['ok'],'Senha errada aceita')
    login=a.call('login',{'email':'aluno@example.test','senha':'senha-de-teste-2026'})
    check(login['ok'] and login['player']['xp']==xp and login['player']['active']['index']==1,'Login perdeu progresso')
    b=Client();b.call('bootstrap');b.cookies['mathfund_guest']=guest_id
    check(b.call('bootstrap')['player']['email'] is None,'Cookie visitante abriu conta registrada')
    check(not b.call('register',{'nome':'Outra pessoa','email':'aluno@example.test','senha':'senha-de-teste-2026'})['ok'],'Cadastro duplicado aceito')
    old=a.csrf;a.call('password',{'senhaAtual':'senha-de-teste-2026','novaSenha':'nova-senha-de-teste'});a.call('logout',{})
    check(not a.call('login',{'email':'aluno@example.test','senha':'senha-de-teste-2026'})['ok'],'Senha antiga ainda válida')
    check(a.call('login',{'email':'aluno@example.test','senha':'nova-senha-de-teste'})['ok'],'Nova senha inválida')
    active=a.call('bootstrap')['player']['active']
    pulse=a.call('playtime',{'run':active['id'],'running':True})
    check(pulse['ok'] and 'player' not in pulse,'Relógio não deve sobrescrever estado da interface')
    a.call('answer',{'run':active['id'],'index':active['index'],'choice':qs[active['question']['id']]['correct']})
    victory=a.call('next',{'run':active['id'],'index':active['index']})
    check({t['id'] for t in victory['earnedTrophies']}=={'7-first','7-star'},'Pop-up não recebeu os novos troféus')
    check(victory['player']['trophySummary']['counts']['common']==2,'Contagem de troféus na API')
    check(a.call('bootstrap')['earnedTrophies']==[],'Reload repetiu troféus já recebidos')
print(f'OK: {checks} verificações de API com PHP CGI real, sessões e cookies.')
