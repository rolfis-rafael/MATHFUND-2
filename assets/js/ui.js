export const escapeHTML = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const paths = {
 clock:'M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z M12 6v6l4 2',
 home:'M3 10 12 3l9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z',
 map:'m3 5 6-2 6 2 6-2v16l-6 2-6-2-6 2z M9 3v16 M15 5v16',
 target:'M21 12a9 9 0 1 1-9-9 M17 12a5 5 0 1 1-5-5 M12 12l9-9 M16 3h5v5',
 book:'M4 3h14a2 2 0 0 1 2 2v16H5a3 3 0 0 1-3-3V5a2 2 0 0 1 2-2Z M6 3v14 M2 17h18 M10 7h6 M10 11h4',
 trophy:'M7 3h10v5c0 4-2 6-5 6S7 12 7 8V3Z M7 5H3v3a4 4 0 0 0 5 4 M17 5h4v3a4 4 0 0 1-5 4 M12 14v5 M8 21h8 M9 19h6',
 chart:'M4 20h17 M6 16v-5 M12 16V4 M18 16V8',
 user:'M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z M4 21v-2a8 8 0 0 1 16 0v2',
 help:'M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z M9.1 9a3 3 0 0 1 5.8 1c0 2-3 2-3 4 M12 17h.01',
 arrow:'M4 12h16 M14 6l6 6-6 6',
 back:'M20 12H4 M10 6l-6 6 6 6',
 star:'m12 3 2.8 5.7 6.3.9-4.6 4.5 1.1 6.3-5.6-3-5.6 3 1.1-6.3L3 9.6l6.3-.9Z',
 bolt:'m13 2-9 12h7l-1 8 10-13h-7z',
 heart:'M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z',
 check:'m5 12 4 4L19 6',
 close:'m6 6 12 12 M6 18 18 6',
 lock:'M5 10h14v11H5z M8 10V6a4 4 0 0 1 8 0v4',
 bulb:'M9 18h6 M9 21h6 M8 14a6 6 0 1 1 8 0c-1 1-1 2-1 2H9s0-1-1-2',
 pencil:'m15 4 5 5 M4 15 16 3l5 5L9 20l-6 1z',
 eraser:'m16 3 6 6-11 12H7l-5-5z M8 10l7 7 M11 21h11',
 undo:'M9 4 3 10l6 6 M3 10h11a7 7 0 0 1 0 14',
 redo:'m15 4 6 6-6 6 M21 10H10a7 7 0 0 0 0 14',
 trash:'M3 6h18 M8 6V3h8v3 M5 6l1 15h12l1-15 M10 10v7 M14 10v7',
 download:'M12 3v12 M7 10l5 5 5-5 M4 15v6h16v-6',
 expand:'M8 3H3v5 M16 3h5v5 M21 16v5h-5 M3 16v5h5',
 sound:'M11 5 6 9H3v6h3l5 4z M15 8a6 6 0 0 1 0 8 M18 5a10 10 0 0 1 0 14',
 mute:'M11 5 6 9H3v6h3l5 4z M16 9l5 6 M16 15l5-6',
 sun:'M16 12a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z M12 2v2 M12 20v2 M2 12h2 M20 12h2 M5 5l1 1 M18 18l1 1 M5 19l1-1 M18 6l1-1',
 pause:'M8 4v16 M16 4v16',
 play:'m7 3 15 9-15 9z',
 leaf:'M20 3C5 1 1 9 7 16s15 0 13-13Z M4 21 16 9',
 flag:'M5 22V3c5-5 9 5 15 0v11c-6 5-10-5-15 0',
 refresh:'M20 8a8 8 0 1 0 0 8 M20 3v5h-5',
 settings:'M12 8a4 4 0 1 1 0 8 4 4 0 0 1 0-8Z M12 2v3 M12 19v3 M2 12h3 M19 12h3 M5 5l2 2 M17 17l2 2 M5 19l2-2 M17 7l2-2',
 code:'m8 5-6 7 6 7 M16 5l6 7-6 7 M14 3l-4 18'
};
export function icon(name, cls='') {return `<svg class="icon ${cls}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="${paths[name]||paths.star}"/></svg>`;}
export const avatars={fox:'🦊',owl:'🦉',cat:'🐱',frog:'🐸',rocket:'🚀'};
export const modes={campaign:'Aventura',practice:'Treino livre',review:'Revisão',daily:'Desafio diário',endless:'Sem fim'};
export function stars(n) {return `<span class="stars" aria-label="${n} de 3 estrelas">${[1,2,3].map(i=>icon('star',i<=n?'earned':'')).join('')}</span>`;}
export function progress(value,max=100,cls='') {const pct=max?Math.min(100,Math.max(0,100*value/max)):0;return `<div class="progress ${cls}" role="progressbar" aria-valuenow="${Math.round(pct)}" aria-valuemin="0" aria-valuemax="100"><span style="width:${pct}%"></span></div>`;}
export function badge(name,text,cls='') {return `<span class="badge ${cls}">${icon(name)} ${escapeHTML(text)}</span>`;}
export function figure(image) {return `<figure class="question-figure"><button type="button" data-action="zoom" data-src="${escapeHTML(image.src)}" data-alt="${escapeHTML(image.alt)}" aria-label="Ampliar figura"><img src="${escapeHTML(image.src)}" alt="${escapeHTML(image.alt)}" width="${image.width}" height="${image.height}" loading="lazy" decoding="async"></button><figcaption>Toque para ampliar</figcaption><details><summary>Descrição da figura</summary><p>${escapeHTML(image.alt)}</p></details></figure>`;}
export function paragraphs(text) {
 const chunks=String(text).split(/(?<=[.!?])\s+(?=[A-ZÀ-ÖØ-Þ])/);let result=[],p='';
 for(const s of chunks){p+=(p?' ':'')+s;if(p.length>260){result.push(p.trim());p='';}}
 if(p.trim())result.push(p.trim());
 return result.map(t=>`<p>${escapeHTML(t)}</p>`).join('');
}

export function loadingSpinner(){return '<div class="loading-spinner" aria-hidden="true"><span class="loading-ring"></span></div>';}
