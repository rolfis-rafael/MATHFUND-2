import {escapeHTML as esc} from './ui.js?v=3.2.0';

// Dialogs nativos mantêm o foco no guia e impedem respostas acidentais.
export class FoxGuide {
 constructor(){this.active=false;}
 close(){this.finish?.(false);}
 async welcome(){
  const pages=[
   ['fox-talk','Oi! Eu sou a raposinha do MathFund. Vou acompanhar você nesta aventura!'],
   ['fox-point','Escolha sua série lá em cima. Em “Fases do jogo”, comece pela fase 1. Uma estrela abre o próximo caminho!'],
   ['fox-listen','Leia a pergunta, escolha uma resposta e aperte “Responder”. Errou? Tudo bem! Eu mostro a explicação.'],
   ['fox-point','Precisa fazer contas? Aperte “Rabiscar”. Nas fases 5 e 10, um chefe espera por você. Vou ensinar tudo quando chegar lá!'],
   ['fox-talk','Vamos nessa? Você pode pausar quando quiser e continuar depois. Cada tentativa ajuda a aprender!']
  ];
  return this.dialog('fox-dialog',async(d,finish)=>{
   let index=0;
   const paint=()=>{const [face,text]=pages[index];d.innerHTML=`<div class="rpg-scene"><img class="rpg-portrait" src="assets/img/${face}.webp" alt="Raposinha ${index===2?'curiosa':'conversando com você'}"><section class="rpg-balloon"><span class="rpg-name">Raposinha</span><span class="rpg-page">${index+1} / ${pages.length}</span><h2 id="guide-title" class="sr-only">Boas-vindas ao MathFund</h2><p class="rpg-text" aria-live="polite">${esc(text)}</p><div class="rpg-controls"><button class="button secondary" data-guide="back" ${index===0?'disabled':''}>Voltar</button><button class="button" data-guide="next">${index===pages.length-1?'Vamos jogar!':'Continuar ▸'}</button></div></section></div>`;d.querySelector('[data-guide="next"]').focus();};
   d.addEventListener('click',e=>{const action=e.target.closest('[data-guide]')?.dataset.guide;if(action==='back'&&index>0){index--;paint();}if(action==='next'){if(index===pages.length-1)finish(true);else{index++;paint();}}});
   // Escape apenas fecha; a conversa pode ser revista em Mais.
   paint();
  });
 }
 async boss(run){return this.learn('boss',run);}
 async learn(mode='campaign',run=null){
  const names={campaign:'Fases',boss:'Chefes',practice:'Treino',review:'Revisão',daily:'Desafio do dia',endless:'Desafio sem fim',notebook:'Caderninho'};
  const rules={campaign:'Escolha sua série e uma fase aberta. Ganhe uma estrela para abrir a próxima!',boss:run?.boss?`Este chefe tem ${run.total} perguntas. Acerte ${run.passPercent===100?'todas':`pelo menos ${Math.ceil(run.total*run.passPercent/100)}`}! Você tem 2 vidas e não pode comprar resgate.`:'Os chefes ficam nas fases 5 e 10. Acerte todas as perguntas! Você tem 2 vidas e um caderninho com espaço limitado.',practice:'Escolha um assunto para treinar. Não perde vidas, e as dicas são grátis!',review:'Aqui você tenta de novo as perguntas que errou. Acertou? Ela sai da revisão!',daily:'São 5 perguntas por série, uma vez por dia. Termine para ganhar moedas extras!',endless:'Responda enquanto tiver vidas. Você começa com 3. Cada erro tira uma.',notebook:'Toque em Rabiscar durante a pergunta. O caderninho abre para você fazer suas contas.'};
  const isNote=mode==='notebook';
  const pages=isNote?[
   ['Abrir',rules.notebook,'open'],['Desenhar','Escolha o lápis e arraste na folha. Aqui, toque em “Testar lápis” para ver um exemplo.','draw'],['Apagar','Use a borracha para apagar uma parte. “Desfazer” retira o último traço. Teste os botões!','erase'],['Guardar','Seus rabiscos ficam salvos neste navegador. Feche o caderno para voltar à pergunta. Nos chefes, confira o contador da folha.','saved']
  ]:[['Conhecer',rules[mode]||rules.campaign,'mode'],['Escolher','Vamos experimentar? Quanto é 2 + 1? Toque na resposta certa.','choose'],['Responder','Escolheu? Agora toque em “Responder”. É esse botão que confirma sua resposta!','confirm'],['Continuar',mode==='boss'?`No caderninho deste chefe: ${run?.notebookLimit?.strokes||12} traços e ${run?.notebookLimit?.chars||160} caracteres por pergunta${run?.boss?'':'; no chefe final, 8 traços e 120 caracteres'}. Desfaça ou limpe para liberar espaço.`:'Depois de responder, leia a explicação. Toque em “Próxima questão” para continuar. Você já sabe jogar!','done']];
  return this.dialog('fox-dialog lesson-dialog',async(d,finish)=>{
   let step=0,choice=null,confirmed=false,drawn=false,opened=false;
   const paint=()=>{
    const [label,copy,demo]=pages[step];let sample='';
    if(demo==='mode')sample=`<div class="lesson-card"><b>${esc(names[mode])}</b><p>${mode==='boss'?'♥ ♥ · Acerte todas':mode==='endless'?'♥ ♥ ♥ · Quanto mais longe, melhor':mode==='practice'||mode==='review'?'Sem perder vidas · Dicas grátis':mode==='daily'?'5 perguntas · Um desafio por dia':'Escolha a fase 1 para começar'}</p></div>`;
    if(demo==='choose'||demo==='confirm')sample=`<div class="lesson-card"><b>Quanto é 2 + 1?</b><div class="demo-answers">${[2,3,4].map(n=>`<button class="button secondary ${choice===n?'demo-selected':''}" data-demo="answer" data-value="${n}" ${demo==='confirm'?'disabled':''}>${n}</button>`).join('')}</div>${demo==='confirm'?`<button class="button demo-confirm" data-demo="confirm" ${confirmed?'disabled':''}>${confirmed?'Acertou!':'Responder'}</button>`:''}<p class="demo-feedback" role="status">${confirmed?'2 + 1 = 3. Viu como funciona?':choice===null?'':choice===3?'Isso! Agora vamos confirmar.':'Quase! Dois dedos mais um dedo dão três. Tente o 3.'}</p></div>`;
    if(demo==='open')sample=`<div class="lesson-card"><button class="button secondary" data-demo="open">Rabiscar</button><p role="status">${opened?'Caderninho aberto! Vamos conhecer o lápis.':'Toque no botão para experimentar.'}</p></div>`;
    if(demo==='draw'||demo==='erase')sample=`<div class="lesson-card"><div class="demo-paper" aria-label="Exemplo de folha">${drawn?'<svg viewBox="0 0 240 70" aria-label="Conta desenhada"><path d="M35 22 Q55 0 70 22 Q72 30 40 53 L72 53 M95 35 H125 M110 20 V50 M150 20 L150 53 M175 28 H210 M175 42 H210" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round"/></svg>':'Sua folha está limpa'}</div><div class="demo-answers"><button class="button secondary" data-demo="draw">Testar lápis</button>${demo==='erase'?'<button class="button secondary" data-demo="erase">Borracha</button><button class="button secondary" data-demo="erase">Desfazer</button>':''}</div></div>`;
    if(demo==='done'||demo==='saved')sample=`<div class="lesson-card"><b>${demo==='saved'?'Tudo pronto para rabiscar!':'Muito bem! Você aprendeu.'}</b><p>${demo==='saved'?'Use lápis, borracha e texto quando precisar.':'Este exemplo não gastou vidas nem moedas.'}</p></div>`;
    const ready=demo==='choose'?choice===3:demo==='confirm'?confirmed:demo==='open'?opened:true;
    d.innerHTML=`<div class="rpg-scene"><img class="rpg-portrait" src="assets/img/${['fox-talk','fox-point','fox-listen','fox-talk'][step]}.webp" alt="Raposinha ensinando"><section class="rpg-balloon"><span class="rpg-name">Raposinha</span><span class="rpg-page">${step+1} / ${pages.length}</span><h2 id="guide-title">Como jogar: ${esc(names[mode]||'Fases')}</h2><nav class="lesson-tabs" aria-label="Passos do tutorial">${pages.map(([name],i)=>`<button data-lesson-step="${i}" ${i>step?'disabled':''} aria-current="${i===step?'step':'false'}">${i+1}. ${name}</button>`).join('')}</nav><p class="rpg-text">${esc(copy)}</p><div class="lesson-example">${sample}</div><div class="rpg-controls"><button class="button secondary" data-guide="back" ${step===0?'disabled':''}>Voltar</button><button class="button" data-guide="next" ${ready?'':'disabled'}>${step===pages.length-1?'Entendi!':'Próximo ▸'}</button></div><button class="tour-skip" data-guide="skip">Fechar tutorial</button></section></div>`;
    d.querySelector(ready?'[data-guide="next"]':'[data-demo]')?.focus({preventScroll:true});
   };
   d.addEventListener('click',e=>{
    const b=e.target.closest('button');if(!b||b.disabled)return;
    const a=b.dataset.guide,act=b.dataset.demo;
    if(a==='skip'){finish(false);return;}
    if(a==='back'&&step>0)step--;
    if(a==='next'){if(step===pages.length-1){finish(true);return;}step++;}
    if(b.dataset.lessonStep!==undefined)step=Number(b.dataset.lessonStep);
    if(act==='answer')choice=Number(b.dataset.value);
    if(act==='confirm')confirmed=true;
    if(act==='draw')drawn=true;if(act==='erase')drawn=false;if(act==='open')opened=true;
    paint();
   });paint();
  });
 }
 dialog(className,setup){
  if(this.active)return Promise.resolve(false);
  this.active=true;const previous=document.activeElement,d=document.createElement('dialog'),controller=new AbortController();d.className=className;d.setAttribute('aria-labelledby','guide-title');document.body.append(d);
  return new Promise(resolve=>{
   let done=false;const finish=value=>{if(done)return;done=true;controller.abort();d.close();d.remove();this.active=false;this.finish=null;if(previous?.isConnected)previous.focus({preventScroll:true});resolve(value);};
   this.finish=finish;d.addEventListener('cancel',e=>{e.preventDefault();finish(false);});d.showModal();
   Promise.resolve(setup(d,finish,controller.signal)).catch(()=>finish(false));
  });
 }
}
