import {escapeHTML as esc,icon,loadingSpinner} from './ui.js?v=3.2.0';

// Efeitos sob demanda; o spinner gira apenas enquanto a tela está carregando.
export class GameFX {
 constructor(settings){
  this.settings=settings;this.flightTimer=null;this.rewardTimer=null;this.introOpen=false;
  this.flight=document.createElement('div');this.flight.className='fox-transition';this.flight.setAttribute('aria-hidden','true');this.flight.hidden=true;document.body.append(this.flight);
  this.reward=document.createElement('div');this.reward.className='game-reward';this.reward.setAttribute('aria-hidden','true');this.reward.hidden=true;document.body.append(this.reward);
  this.intro=document.createElement('dialog');this.intro.className='phase-intro';this.intro.setAttribute('aria-labelledby','intro-title');document.body.append(this.intro);
 }
 motion(){return this.settings()?.motion!==false&&!window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;}
 fly(back=false){
  if(!this.motion())return;
  clearTimeout(this.flightTimer);this.flight.hidden=false;
  this.flight.innerHTML=`<img class="flying-fox ${back?'fly-back':''}" src="assets/img/fox-fly${back?'-back':''}.webp" alt="" width="360" height="320">`;
  this.flightTimer=setTimeout(()=>{this.flight.hidden=true;this.flight.replaceChildren();},850);
 }
 celebrate(feedback){
  if(!feedback.correct||!this.motion())return;
  clearTimeout(this.rewardTimer);this.reward.hidden=false;
  this.reward.innerHTML=`<span class="reward-pop">+${Number(feedback.xp)||0} XP ${icon('star')}</span>${Array.from({length:10},(_,i)=>`<i style="--i:${i};--x:${(i-4.5)*24}px;--y:${-35-(i%3)*26}px">${i%2?'★':'●'}</i>`).join('')}`;
  this.rewardTimer=setTimeout(()=>{this.reward.hidden=true;this.reward.replaceChildren();},1150);
 }
 clearReward(){clearTimeout(this.rewardTimer);this.reward.hidden=true;this.reward.replaceChildren();}
 async prepare(operation,{title,subtitle,free=false,lives=3}){
  const d=this.intro,opener=document.activeElement;let cancelled=false,ready=false,choiceResolve;
  const choice=new Promise(resolve=>choiceResolve=resolve);
  this.introOpen=true;
  d.innerHTML=`<div class="intro-scene"><span class="intro-label">MATHFUND</span><h2 id="intro-title">${esc(title)}</h2><p class="intro-subtitle">${esc(subtitle)}</p><div class="intro-track" aria-hidden="true"><span class="intro-ground"></span><img src="assets/img/fox-fly.webp" alt="" width="200" height="180"><span class="intro-star star-one">★</span><span class="intro-star star-two">★</span><span class="intro-star star-three">★</span></div>${loadingSpinner()}<p class="intro-status" role="status">Carregando a fase…</p><p class="intro-tip">${free?'Treine à vontade, sem perder vidas.':`${Array.from({length:lives},()=>icon('heart')).join('')} <span>${lives} vidas para encarar o desafio!</span>`}</p><button type="button" class="intro-start" disabled><span class="start-word">START</span><span>Jogar!</span>${icon('play')}</button><button type="button" class="intro-back">Voltar</button></div>`;
  const cancel=()=>{cancelled=true;choiceResolve(false);if(d.open)d.close();};
  const onCancel=e=>{e.preventDefault();cancel();};
  const onClose=()=>{if(!ready)cancelled=true;choiceResolve(false);};
  const onHash=()=>cancel();
  d.querySelector('.intro-back').onclick=cancel;
  d.querySelector('.intro-start').onclick=()=>{if(ready&&!cancelled)choiceResolve(true);};
  d.addEventListener('cancel',onCancel);d.addEventListener('close',onClose);window.addEventListener('hashchange',onHash);
  try{
   d.classList.remove('ready');d.setAttribute('aria-busy','true');d.showModal();d.querySelector('.intro-back').focus();
   // O pedido ao PHP e a abertura visual acontecem juntos.
   await Promise.all([Promise.resolve().then(operation),new Promise(resolve=>setTimeout(resolve,3500))]);
   if(cancelled)return false;
   ready=true;d.setAttribute('aria-busy','false');d.classList.add('ready');d.querySelector('.intro-status').textContent='Tudo pronto. Vamos jogar?';
   const start=d.querySelector('.intro-start');start.disabled=false;start.focus();
   return await choice;
  }finally{
   d.removeEventListener('cancel',onCancel);d.removeEventListener('close',onClose);window.removeEventListener('hashchange',onHash);
   if(d.open)d.close();d.removeAttribute('aria-busy');d.replaceChildren();this.introOpen=false;
   if(opener?.isConnected)opener.focus();
  }
 }
}
