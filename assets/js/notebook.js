import {icon,escapeHTML} from './ui.js?v=3.2.0';
let dbPromise;
function db() {
 if(!dbPromise) dbPromise=new Promise((resolve,reject)=>{
   const r=indexedDB.open('mathfund-notebook-v2',1);
   r.onupgradeneeded=()=>r.result.createObjectStore('pages');
   r.onsuccess=()=>resolve(r.result);r.onerror=()=>reject(r.error);
 });
 return dbPromise;
}
async function record(key,value) {
 const d=await db();
 return new Promise((resolve,reject)=>{
   const t=d.transaction('pages',value===undefined?'readonly':'readwrite');const s=t.objectStore('pages');
   const req=value===undefined?s.get(key):s.put(value,key);
   let result;req.onsuccess=()=>{result=req.result;};t.oncomplete=()=>resolve(result);t.onerror=()=>reject(t.error);
 });
}
export class Notebook {
 constructor(container,toast,confirm) {this.root=container;this.toast=toast;this.confirm=confirm;this.key='';this.color='#274a38';this.width=3;this.tool='pen';this.strokes=[];this.redo=[];this.destroyed=false;}
 async mount(key,label,limit=null) {
  this.limit=limit;this.maxStrokes=limit?.strokes||250;this.maxChars=limit?.chars||5000;
  this.key=key;
  this.root.innerHTML=`<div class="notebook-top"><div>${icon('book')}<strong>Meu caderninho</strong></div><button class="icon-button note-close" data-note="close" aria-label="Fechar caderninho">${icon('close')}</button></div><p class="notebook-sub">Espaço para pensar, testar e errar.</p>${limit?`<div class="note-limit" role="status" aria-live="polite"><strong>Caderninho do chefe</strong><span class="note-usage"></span><small>Por pergunta. Desfaça ou limpe para liberar espaço.</small></div>`:''}<div class="notebook-toolbar" role="toolbar" aria-label="Ferramentas de desenho"><button class="tool active" data-note="pen" aria-label="Lápis" aria-pressed="true">${icon('pencil')}</button><button class="tool" data-note="eraser" aria-label="Borracha" aria-pressed="false">${icon('eraser')}</button><span class="tool-divider"></span>${['#274a38','#2478c5','#d5672f'].map((c,i)=>`<button class="color-tool ${i===0?'active':''}" data-color="${c}" style="--color:${c}" aria-label="Cor ${['verde','azul','laranja'][i]}" aria-pressed="${i===0}"></button>`).join('')}<label class="sr-only" for="pen-width">Espessura</label><select id="pen-width" class="pen-width" aria-label="Espessura do lápis"><option value="3">Fino</option><option value="6">Médio</option><option value="10">Grosso</option></select></div><div class="paper"><canvas aria-label="Folha para rabiscar com mouse, caneta ou toque"></canvas><span class="paper-label">${escapeHTML(label)}</span></div><div class="notebook-bottom"><div><button class="icon-button" data-note="undo" aria-label="Desfazer traço">${icon('undo')}</button><button class="icon-button" data-note="redo" aria-label="Refazer traço">${icon('redo')}</button></div><div><button class="icon-button" data-note="download" aria-label="Baixar rascunho como imagem">${icon('download')}</button><button class="icon-button" data-note="clear" aria-label="Limpar esta folha">${icon('trash')}</button></div></div><label class="note-label" for="note-text">Prefere escrever?</label><textarea id="note-text" placeholder="Anote as contas ou o seu raciocínio…" maxlength="${this.maxChars}" rows="3"></textarea><div class="note-status" aria-live="polite">Salvo neste navegador, por exercício.</div>`;
  this.canvas=this.root.querySelector('canvas');this.ctx=this.canvas.getContext('2d');this.area=this.root.querySelector('textarea');this.canvas.style.pointerEvents='none';this.area.disabled=true;
  this.controller=new AbortController();const opt={signal:this.controller.signal};
  this.root.addEventListener('click',e=>this.click(e),opt);
  this.root.addEventListener('keydown',e=>{if(e.key!=='Tab'||!this.root.classList.contains('open'))return;const items=[...this.root.querySelectorAll('button:not([disabled]),select,textarea')].filter(x=>x.getClientRects().length);const first=items[0],last=items.at(-1);if(e.shiftKey&&document.activeElement===first){e.preventDefault();last.focus();}else if(!e.shiftKey&&document.activeElement===last){e.preventDefault();first.focus();}},opt);
  this.root.querySelector('select').addEventListener('change',e=>this.width=Number(e.target.value),opt);
  this.area.addEventListener('input',()=>{this.area.value=this.area.value.slice(0,this.maxChars);this.buttons();this.scheduleSave();},opt);
  this.canvas.addEventListener('pointerdown',e=>this.down(e),opt);
  this.canvas.addEventListener('pointermove',e=>this.move(e),opt);
  this.canvas.addEventListener('pointerup',()=>this.up(),opt);
  this.canvas.addEventListener('pointercancel',()=>this.up(),opt);
  this.canvas.addEventListener('lostpointercapture',()=>this.up(),opt);
  this.resizeObserver=new ResizeObserver(()=>this.resize());this.resizeObserver.observe(this.canvas);
  this.beforeUnload=()=>this.save();window.addEventListener('pagehide',this.beforeUnload,opt);
  this.visibility=()=>{if(document.hidden){this.up();this.save();}};document.addEventListener('visibilitychange',this.visibility,opt);
  try {const saved=await record(key);if(this.destroyed)return;if(saved){this.strokes=(saved.strokes||[]).slice(0,this.maxStrokes);this.area.value=(saved.text||'').slice(0,this.maxChars);}}
  catch {this.storageUnavailable=true;this.root.querySelector('.note-status').textContent='O navegador bloqueou o salvamento. Baixe o rascunho antes de sair.';}
  if(!this.destroyed){this.loaded=true;this.canvas.style.pointerEvents='';this.area.disabled=false;this.resize();this.buttons();}
 }
 point(e){const r=this.canvas.getBoundingClientRect();return [Math.max(0,Math.min(1000,(e.clientX-r.left)*1000/r.width)),Math.max(0,Math.min(900,(e.clientY-r.top)*900/r.height))];}
 down(e){if(e.button!==0||this.stroke)return;if(this.strokes.length>=this.maxStrokes){this.toast(this.limit?'Você usou os traços desta folha. Desfaça ou limpe para continuar.':'Folha cheia. Baixe ou limpe o rascunho para continuar.');return;}e.preventDefault();this.canvas.setPointerCapture(e.pointerId);this.pointer=e.pointerId;this.redo=[];this.stroke={tool:this.tool,color:this.color,width:this.tool==='eraser'?this.width*5:this.width,points:[this.point(e)]};this.draw();}
 move(e){if(!this.stroke||e.pointerId!==this.pointer)return;e.preventDefault();const pt=this.point(e),last=this.stroke.points.at(-1);if(Math.hypot(pt[0]-last[0],pt[1]-last[1])<1)return;if(this.stroke.points.length>=1200)return;this.stroke.points.push(pt);if(!this.raf)this.raf=requestAnimationFrame(()=>{this.raf=null;this.draw();});}
 up(){if(!this.stroke)return;this.strokes.push(this.stroke);this.stroke=null;this.pointer=null;this.draw();this.buttons();this.save();}
 resize(){const r=this.canvas.getBoundingClientRect();if(!r.width)return;const d=Math.min(window.devicePixelRatio||1,2);this.canvas.width=Math.round(r.width*d);this.canvas.height=Math.round(r.width*.9*d);this.draw();}
 draw(){const c=this.ctx,w=this.canvas.width,h=this.canvas.height;if(!w||!h)return;c.setTransform(1,0,0,1,0,0);c.clearRect(0,0,w,h);c.setTransform(w/1000,0,0,h/900,0,0);c.lineCap='round';c.lineJoin='round';for(const s of [...this.strokes,...(this.stroke?[this.stroke]:[])]){c.globalCompositeOperation=s.tool==='eraser'?'destination-out':'source-over';c.strokeStyle=s.color;c.lineWidth=s.width;c.beginPath();c.moveTo(...s.points[0]);if(s.points.length===1)c.lineTo(s.points[0][0]+.1,s.points[0][1]+.1);else for(const p of s.points.slice(1))c.lineTo(...p);c.stroke();}c.globalCompositeOperation='source-over';}
 buttons(){this.root.querySelector('[data-note="undo"]').disabled=!this.strokes.length;this.root.querySelector('[data-note="redo"]').disabled=!this.redo.length||this.strokes.length>=this.maxStrokes;const usage=this.root.querySelector('.note-usage');if(usage)usage.textContent=`${this.strokes.length}/${this.maxStrokes} traços · ${this.area.value.length}/${this.maxChars} caracteres`;}
 async click(e){const b=e.target.closest('button');if(!b)return;const action=b.dataset.note;
  if(b.dataset.color){this.color=b.dataset.color;this.tool='pen';}
  else if(action==='pen'||action==='eraser')this.tool=action;
  else if(action==='undo'&&this.strokes.length){this.redo.push(this.strokes.pop());this.draw();this.save();}
  else if(action==='redo'&&this.redo.length&&this.strokes.length<this.maxStrokes){this.strokes.push(this.redo.pop());this.draw();this.save();}
  else if(action==='clear'){if(await this.confirm('Limpar esta folha?','Os rabiscos e as anotações deste exercício serão apagados.','Limpar')){this.strokes=[];this.redo=[];this.area.value='';this.draw();this.save();}}
  else if(action==='download')this.download();
  else if(action==='close'){this.root.classList.remove('open');document.body.classList.remove('note-open');document.querySelector('[data-action="notebook"]')?.focus();}
  this.root.querySelectorAll('[data-color]').forEach(x=>{x.classList.toggle('active',x.dataset.color===this.color);x.setAttribute('aria-pressed',String(x.dataset.color===this.color));});
  this.root.querySelectorAll('[data-note="pen"],[data-note="eraser"]').forEach(x=>{x.classList.toggle('active',x.dataset.note===this.tool);x.setAttribute('aria-pressed',String(x.dataset.note===this.tool));});this.buttons();
 }
 scheduleSave(){clearTimeout(this.saveTimer);this.saveTimer=setTimeout(()=>this.save(),350);}
 async save(){if(!this.loaded||!this.key||!this.area)return;clearTimeout(this.saveTimer);try{await record(this.key,{strokes:this.strokes,text:this.area.value,updated:Date.now()});}catch{if(!this.destroyed)this.root.querySelector('.note-status').textContent='Não foi possível salvar. Baixe uma cópia do rascunho.';}}
 download(){const c=document.createElement('canvas');c.width=1000;c.height=1100;const x=c.getContext('2d');x.fillStyle='#fffdf6';x.fillRect(0,0,1000,1100);x.strokeStyle='#e2e7dc';for(let i=0;i<=1000;i+=40){x.beginPath();x.moveTo(i,0);x.lineTo(i,900);x.stroke();}for(let i=0;i<=900;i+=40){x.beginPath();x.moveTo(0,i);x.lineTo(1000,i);x.stroke();}x.drawImage(this.canvas,0,0,1000,900);x.fillStyle='#274a38';x.font='20px sans-serif';const words=this.area.value.split(/\s+/);let line='',y=940;for(const w of words){if(x.measureText(line+w).width>920){x.fillText(line,30,y);y+=26;line='';if(y>1070)break;}line+=w+' ';}if(y<=1070)x.fillText(line,30,y);c.toBlob(blob=>{const u=URL.createObjectURL(blob);const a=document.createElement('a');a.href=u;a.download='mathfund-rascunho.png';a.click();setTimeout(()=>URL.revokeObjectURL(u),1000);},'image/png');}
 destroy(){this.up();this.save();this.destroyed=true;clearTimeout(this.saveTimer);if(this.raf)cancelAnimationFrame(this.raf);this.resizeObserver?.disconnect();this.controller?.abort();document.body.classList.remove('note-open');}
}
