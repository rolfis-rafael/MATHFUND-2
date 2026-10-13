// Sem cronômetro do cliente: o PHP calcula o intervalo entre os sinais.
export class PlayClock {
 constructor(getState){this.getState=getState;this.run=null;this.timer=null;this.pending=false;this.leaving=false;document.addEventListener('visibilitychange',()=>this.sync());window.addEventListener('pagehide',()=>this.stop(true));window.addEventListener('pageshow',()=>this.sync());}
 async send(run,running,keepalive=false){
  const s=this.getState();if(!s.csrf||!run)return;
  try{await fetch('api.php?action=playtime',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':s.csrf},body:JSON.stringify({run,running}),credentials:'same-origin',keepalive});}catch{}
 }
 sync(){const s=this.getState(),desired=s.entered&&s.route==='jogar'&&!document.hidden?s.player?.active?.id:null;if(desired===this.run)return;this.stop();if(!desired)return;this.run=desired;this.send(desired,true);this.timer=window.setInterval(()=>{if(!this.pending&&this.run===desired){this.pending=true;this.send(desired,true).finally(()=>this.pending=false);}},15000);}
 stop(keepalive=false){window.clearInterval(this.timer);this.timer=null;const old=this.run;this.run=null;if(old)this.send(old,false,keepalive);}
}
