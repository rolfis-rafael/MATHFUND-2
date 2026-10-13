// A preferência de aparência fica neste navegador e é aplicada antes do CSS.
(()=>{
 const key='mathfund-theme';let selected='dark';
 try{if(localStorage.getItem(key)==='light')selected='light';}catch{}
 function apply(theme){
  selected=theme==='light'?'light':'dark';document.documentElement.dataset.theme=selected;
  document.documentElement.style.colorScheme=selected;
  try{localStorage.setItem(key,selected);}catch{}
  const b=document.getElementById('theme-toggle');
  if(b){b.setAttribute('aria-pressed',String(selected==='light'));b.setAttribute('aria-label',selected==='light'?'Usar modo escuro':'Usar modo claro');b.title=selected==='light'?'Modo escuro':'Modo claro';b.querySelector('span').textContent=selected==='light'?'☾':'☀';}
  const select=document.querySelector('[name="theme"]');if(select)select.value=selected;
 }
 window.MathFundTheme={get:()=>selected,set:apply,toggle:()=>apply(selected==='light'?'dark':'light')};
 apply(selected);document.addEventListener('DOMContentLoaded',()=>apply(selected));
 document.addEventListener('click',e=>{if(e.target.closest('#theme-toggle'))window.MathFundTheme.toggle();});
})();
