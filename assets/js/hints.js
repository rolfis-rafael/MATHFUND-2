// Uma dica curta no mouse e no foco do teclado, sem atrapalhar o toque.
export function installHints(){
 const box=document.createElement('div');box.id='quick-hint';box.className='quick-hint';box.role='tooltip';box.hidden=true;document.body.append(box);
 let active=null,timer,previousDescription,previousTitle;
 const actions={campaign:'Jogue esta fase.',practice:'Treine sem perder vidas.',review:'Tente de novo as perguntas que errou.',daily:'Jogue o desafio de hoje.',endless:'Veja quantas perguntas consegue acertar.',resume:'Continue de onde parou.',pause:'Guarde a partida para depois.',select:'Escolha esta resposta.',answer:'Confirme sua resposta.',next:'Veja a próxima pergunta.',hint:'Abra uma dica para esta pergunta.',notebook:'Abra sua folha de rascunho.',finish:'Encerre esta partida.',revive:'Use 20 moedas para ganhar uma vida.',replay:'Tente esta partida outra vez.',journey:'Volte ao mapa das fases.',learn:'Aprenda com a raposinha.',welcome:'Converse com a raposinha.', 'boss-help':'Aprenda a enfrentar o chefe.','result-next':'Veja a próxima conquista.','result-back':'Veja o valor anterior.',register:'Crie uma conta para guardar o progresso.',login:'Entre na sua conta.',logout:'Saia desta conta.',export:'Baixe uma cópia do progresso.',grade:'Troque de unidade.',zoom:'Veja a imagem maior.'};
 const routes={inicio:'Volte ao início.',jornada:'Escolha uma fase para jogar.',perfil:'Mude seu nome e suas preferências.',mais:'Veja outros modos de jogar.',ajuda:'Aprenda cada modo com a raposinha.',ranking:'Veja as pontuações dos jogadores.',praticar:'Escolha um assunto para treinar.',revisao:'Aprenda com seus erros.',conquistas:'Veja suas estrelas e conquistas.'};
 const notes={pen:'Desenhe suas contas.',eraser:'Apague uma parte do desenho.',undo:'Retire o último traço.',redo:'Coloque o traço de volta.',clear:'Apague esta folha.',download:'Guarde uma imagem do rascunho.',close:'Feche e volte à pergunta.'};
 const selectors='button,a,select,input,textarea,summary,[data-hint],#coin-stat,#streak-stat,.game-lives,.game-progress-row';
 function message(el){
  if(el.closest('.title-screen'))return '';
  if(el.disabled&&el.dataset.action==='campaign')return 'Passe a fase anterior para abrir esta.';
  if(el.dataset.hint)return el.dataset.hint;
  if(el.id==='grade-select'||el.name==='grade')return 'Escolha seu ano ou série.';
  if(el.id==='theme-toggle'||el.name==='theme')return 'Escolha fundo claro ou escuro.';
  if(el.id==='menu-toggle')return 'Abra ou feche o menu.';
  if(el.id==='coin-stat')return 'Suas moedas para pedir dicas.';
  if(el.id==='streak-stat')return 'Dias seguidos aprendendo.';
  if(el.matches('.game-lives'))return 'Cada erro tira um coração.';
  if(el.matches('.game-progress-row'))return 'Veja quanto falta nesta partida.';
  if(el.id==='note-text')return 'Escreva suas contas e ideias.';
  if(el.id==='pen-width')return 'Escolha a grossura do lápis.';
  if(el.dataset.color)return 'Troque a cor do lápis.';
  if(el.dataset.note)return notes[el.dataset.note]||'';
  if(el.dataset.action)return actions[el.dataset.action]||el.getAttribute('aria-label')||'';
  if(el.dataset.guide)return {next:'Continue a conversa.',back:'Volte uma explicação.',skip:'Feche. Você pode rever depois.'}[el.dataset.guide]||'';
  if(el.dataset.demo)return 'Experimente aqui. Não gasta vidas.';
  if(el.dataset.lessonStep!==undefined)return 'Reveja esta explicação.';
  if(el.matches('a'))return routes[(el.getAttribute('href')||'').slice(1)]||el.getAttribute('aria-label')||'';
  if(el.name==='motion')return 'Ligue ou desligue os efeitos decorativos.';
  if(el.name==='ranking')return 'Escolha se quer aparecer no ranking.';
  if(el.name==='goal')return 'Escolha quantas perguntas quer fazer por dia.';
  if(el.matches('input,select,textarea'))return el.getAttribute('aria-label')||'Preencha este campo.';
  if(el.matches('summary'))return 'Clique para ver a explicação.';
  if(el.type==='submit')return el.textContent.includes('Salvar')?'Guarde suas alterações.':'Confirme os dados preenchidos.';
  return el.getAttribute('aria-label')||el.getAttribute('title')||'';
 }
 function hide(){clearTimeout(timer);box.hidden=true;if(active){if(previousTitle!==null)active.setAttribute('title',previousTitle);if(previousDescription)active.setAttribute('aria-describedby',previousDescription);else active.removeAttribute('aria-describedby');}active=null;}
 function show(el){hide();const text=message(el);if(!text)return;active=el;previousTitle=el.getAttribute('title');el.removeAttribute('title');previousDescription=el.getAttribute('aria-describedby');timer=setTimeout(()=>{if(!el.isConnected)return hide();(el.closest('dialog[open]')||document.body).append(box);box.textContent=text;box.hidden=false;el.setAttribute('aria-describedby',[previousDescription,'quick-hint'].filter(Boolean).join(' '));const r=el.getBoundingClientRect(),w=box.offsetWidth,h=box.offsetHeight;box.style.left=Math.max(8,Math.min(window.innerWidth-w-8,r.left+r.width/2-w/2))+'px';box.style.top=(r.bottom+h+14<window.innerHeight?r.bottom+9:Math.max(8,r.top-h-9))+'px';},350);}
 document.addEventListener('pointerover',e=>{if(e.pointerType==='touch')return;const el=e.target.closest(selectors);if(el&&el!==active)show(el);});
 document.addEventListener('pointerout',e=>{if(active&&!active.contains(e.relatedTarget))hide();});
 document.addEventListener('focusin',e=>{const el=e.target.closest(selectors);if(el)show(el);});document.addEventListener('focusout',hide);
 document.addEventListener('pointerdown',hide);document.addEventListener('scroll',hide,true);window.addEventListener('resize',hide);
 document.addEventListener('keydown',e=>{if(e.key==='Escape')hide();});
 new MutationObserver(()=>{if(active&&!active.isConnected)hide();}).observe(document.body,{childList:true,subtree:true});
}
