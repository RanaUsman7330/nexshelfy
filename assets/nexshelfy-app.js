(()=>{
'use strict';
const BASE=location.pathname.startsWith('/nexshelfy')?'/nexshelfy':'';const API=BASE+'/api/';
const $=(s,r=document)=>r.querySelector(s),$$=(s,r=document)=>[...r.querySelectorAll(s)];
const read=(k,d)=>{try{return JSON.parse(localStorage.getItem(k)||JSON.stringify(d))}catch{return d}};
const esc=s=>String(s??'').replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));
function prettyTitleSafe(v){return String(v||'').replace(/[-_]+/g,' ').replace(/\b\w/g,m=>m.toUpperCase()).trim()}
function normalizeCategories(raw){if(!Array.isArray(raw))return[];return raw.map(c=>{if(!c)return null;if(typeof c==='string')return{name:c,items:[]};if(typeof c.name!=='string')return null;if(!Array.isArray(c.items))c.items=[];return c}).filter(Boolean)}
function normalizeBookmarks(raw){if(!Array.isArray(raw))return[];return raw.map(b=>{if(!b)return null;if(typeof b==='string')return{slug:b,title:prettyTitleSafe(b)};if(!b.slug)return null;if(typeof b.title!=='string')b.title=prettyTitleSafe(b.slug);return b}).filter(Boolean)}
let state={csrf:'',user:null,cart:[],wishlist:read('ns_wishlist',[]),resources:read('ns_resources',[]),bookmarks:normalizeBookmarks(read('ns_bookmarks',[])),categories:normalizeCategories(read('ns_saved_categories',[]))};
const visitorKey=(()=>{let v=localStorage.getItem('ns_visitor');if(!v){v=(crypto.randomUUID?crypto.randomUUID():Date.now()+'-'+Math.random());localStorage.setItem('ns_visitor',v)}return v})();

async function api(file,data=null,method='POST'){
 const opts={method,credentials:'include',cache:'no-store',headers:{Accept:'application/json'}};
 if(data&&method!=='GET'){opts.headers['Content-Type']='application/json';opts.headers['X-CSRF-Token']=state.csrf;opts.body=JSON.stringify(data)}
 const res=await fetch(API+file,opts);const text=await res.text();let json;
 try{json=JSON.parse(text)}catch{throw new Error('Server returned an invalid response. Please check PHP/database setup.')}
 if(!res.ok||json.ok===false)throw new Error(json.message||'Request failed');return json;
}
function toast(msg){$('.ns-toast')?.remove();const e=document.createElement('div');e.className='ns-toast';e.textContent=msg;document.body.append(e);setTimeout(()=>e.remove(),2600)}
function store(){localStorage.setItem('ns_wishlist',JSON.stringify(state.wishlist));localStorage.setItem('ns_resources',JSON.stringify(state.resources));localStorage.setItem('ns_bookmarks',JSON.stringify(state.bookmarks));localStorage.setItem('ns_saved_categories',JSON.stringify(state.categories));renderHeader();applySavedStates();renderSavedPage()}
function modal(html,cls=''){const b=document.createElement('div');b.className='ns-modal-backdrop';b.innerHTML=`<div class="ns-modal ${cls}"><button class="ns-modal-close" aria-label="Close">×</button>${html}</div>`;document.body.append(b);$('.ns-modal-close',b).onclick=()=>b.remove();b.onclick=e=>{if(e.target===b)b.remove()};return b}
function headerHost(){return document.body}

const navigation=[
 {href:'/',label:'Discover'}, {href:'/shop/',label:'Free Products'},
 {href:'/resources/',label:'Free Resources'}, {href:'/collections/',label:'Collections'},
 {href:'/blog/',label:'Blog'}, {href:'/creators/',label:'Creators'}
];
function normalizePrimaryNavigation(){
 const nav=$('.cp-site-header .cp-nav');if(!nav)return;
 const path=location.pathname.replace(/\/+$/,'')||'/';
 nav.innerHTML=navigation.map(item=>{const href=BASE+item.href;const active=(item.href==='/'?path==='/':path===item.href.replace(/\/$/,''));return `<a${active?' class="active"':''} href="${href}">${item.label}</a>`}).join('');
}
function validHex(value,fallback){return /^#[0-9a-f]{6}$/i.test(String(value||''))?String(value):fallback}
function applyGlobalTheme(settings){
 const root=document.documentElement.style;
 const primary=validHex(settings?.theme_primary,'#4f46e5');
 const secondary=validHex(settings?.theme_secondary,'#7c3aed');
 root.setProperty('--ns-accent',primary);root.setProperty('--cp-accent',primary);root.setProperty('--accent',primary);
 root.setProperty('--ns-accent-2',secondary);root.setProperty('--cp-accent-2',secondary);
 root.setProperty('--ns-theme-surface',validHex(settings?.theme_surface,'#f7f7ff'));
 root.setProperty('--ns-theme-ink',validHex(settings?.theme_ink,'#111827'));
}
async function loadGlobalTheme(){try{const r=await fetch(BASE+'/api/site-settings.php',{cache:'no-store'});const j=await r.json();if(j.ok)applyGlobalTheme(j.settings)}catch(e){}}

function authModal(){if($('.ns-modal-backdrop'))return;const ca=Math.floor(Math.random()*8)+2,cb=Math.floor(Math.random()*8)+2;const b=modal(`<div class="ns-modal-kicker">Customer account</div><h2>Welcome to NexShelfy</h2><p>Sign in to sync saved products, resources and articles across devices.</p><div class="ns-auth-tabs"><button class="active" data-tab="login">Sign in</button><button data-tab="register">Register</button></div><form data-auth><div class="ns-field ns-name" hidden><label>Name</label><input name="name" autocomplete="name"></div><div class="ns-field"><label>Email</label><input name="email" type="email" required autocomplete="email"></div><div class="ns-field"><label>Password</label><input name="password" type="password" required minlength="8" autocomplete="current-password"></div><div class="ns-field ns-captcha" hidden><label>Captcha: ${ca} + ${cb} = ?</label><input name="captcha_answer" inputmode="numeric"><input type="hidden" name="captcha_a" value="${ca}"><input type="hidden" name="captcha_b" value="${cb}"></div><button class="ns-btn" type="submit">Sign in</button></form>`);let mode='login';$$('[data-tab]',b).forEach(x=>x.onclick=()=>{mode=x.dataset.tab;$$('[data-tab]',b).forEach(t=>t.classList.toggle('active',t===x));$('.ns-name',b).hidden=mode==='login';$('.ns-captcha',b).hidden=mode==='login';$('button[type=submit]',b).textContent=mode==='login'?'Sign in':'Create account'});$('form',b).onsubmit=async e=>{e.preventDefault();const form=e.currentTarget;try{const j=await api(`${mode}.php`,Object.fromEntries(new FormData(form)));state.user=j.user;b.remove();await syncSaved();await syncCart();renderHeader();toast(j.message);const redirect=new URLSearchParams(location.search).get('redirect');if(redirect&&redirect.startsWith('/'))location.href=redirect}catch(x){toast(x.message)}}}
function renderMobileDock(){let dock=$('.ns-mobile-dock');if(!dock){dock=document.createElement('nav');dock.className='ns-mobile-dock';dock.setAttribute('aria-label','Mobile navigation');document.body.appendChild(dock)}const savedCount=state.wishlist.length+state.resources.length+state.bookmarks.length;dock.innerHTML=`<a href="${BASE}/"><span>⌂</span>Home</a><a href="${BASE}/shop/"><span>▦</span>Products</a><a href="${BASE}/collections/"><span>◇</span>Collections</a><button type="button" data-mobile-saved><span>♡<em>${savedCount}</em></span>Saved</button>${state.user?`<a href="${BASE}/dashboard/"><span>◉</span>Account</a>`:`<button type="button" data-mobile-login><span>◉</span>Sign in</button>`}`;const path=location.pathname.replace(/\/+$/,'')||'/';$$('a',dock).forEach(a=>{const target=new URL(a.href,location.origin).pathname.replace(/\/+$/,'')||'/';a.classList.toggle('active',target===path)});$('[data-mobile-saved]',dock).onclick=savedModal;const login=$('[data-mobile-login]',dock);if(login)login.onclick=authModal}
function renderHeader(){
 const staticAccount=$('header a[aria-label="Dashboard"]');
 if(staticAccount){staticAccount.hidden=true;staticAccount.setAttribute('aria-hidden','true')}
 let box=$('[data-ns-header-actions]')||$('.ns-header-actions');
 if(!box){
   const nativeActions=staticAccount?.parentElement||$('header .flex.items-center.gap-1')||$('header');
   box=document.createElement('div');
   box.className='ns-header-actions ns-header-inline';
   if(nativeActions) nativeActions.appendChild(box); else document.body.appendChild(box);
 }else{
   box.classList.add('ns-header-actions');
 }
 const savedCount=state.wishlist.length+state.resources.length+state.bookmarks.length;
 box.replaceChildren();
 const free=document.createElement('a');free.className='ns-header-btn ns-free-link';free.href=BASE+'/shop/';free.textContent='Browse Library';free.setAttribute('aria-label','Browse free library');
 const saved=document.createElement('button');saved.className='ns-header-btn ns-icon-action';saved.type='button';saved.setAttribute('aria-label','Saved items');saved.innerHTML=`<span class="ns-action-icon">♡</span><span class="ns-action-count">${savedCount}</span>`;saved.setAttribute('title', savedCount ? `${savedCount} saved items` : 'Save useful resources for later');saved.onclick=savedModal;
 box.append(free,saved);
 if(state.user){const a=document.createElement('a');a.className='ns-header-btn ns-account';a.href=BASE+'/dashboard/';a.textContent='Account';box.append(a)}else{const login=document.createElement('button');login.type='button';login.className='ns-header-btn ns-account';login.textContent='Sign in';login.onclick=authModal;box.append(login)}
 renderMobileDock();
}
function prettyTitle(v){return String(v||'').replace(/[-_]+/g,' ').replace(/\b\w/g,m=>m.toUpperCase()).trim()}
function savedModal(){
 const all=[...state.wishlist.map(slug=>({type:'product',key:slug,title:prettyTitle(slug),meta:'Free product',url:`${BASE}/shop/${encodeURIComponent(slug)}/`})),...state.resources.map(slug=>({type:'resource',key:slug,title:prettyTitle(slug),meta:'Free resource',url:`${BASE}/resources/#${encodeURIComponent(slug)}`})),...state.bookmarks.map(x=>({type:'blog',key:x.slug,title:x.title||prettyTitle(x.slug),meta:'Saved article',url:`${BASE}/blog/${encodeURIComponent(x.slug)}/`}))];
 const assignment=(type,key)=>{const c=state.categories.find(x=>(x.items||[]).some(i=>i.type===type&&i.key===key));return c?.name||''};
 const options=(type,key)=>`<select aria-label="Saved category" data-cat-type="${type}" data-cat-key="${esc(key)}"><option value="">Uncategorized</option>${state.categories.map(c=>`<option value="${esc(c.name)}" ${assignment(type,key)===c.name?'selected':''}>${esc(c.name)}</option>`).join('')}</select>`;
 const rows=all.map(item=>`<div class="ns-saved-row"><div><b>${esc(item.title)}</b><small>${esc(item.meta)}</small>${options(item.type,item.key)}</div><div class="ns-saved-actions"><a href="${item.url}">Open</a><button type="button" data-remove-saved="${item.type}" data-key="${esc(item.key)}">Remove</button></div></div>`).join('');
 const b=modal(`<div class="ns-modal-kicker">Your shelf</div><h2>Saved items</h2><p class="ns-modal-copy">Keep products and articles in simple categories so you can find them later.</p><form class="ns-category-create"><input placeholder="New category name" maxlength="80"><button class="ns-btn" type="submit">Create category</button></form><div class="ns-category-chips">${state.categories.map(c=>`<span>${esc(c.name)} <button type="button" data-del-cat="${esc(c.name)}">×</button></span>`).join('')}</div>${rows||'<div class="ns-empty ns-empty-large"><b>No saved items yet.</b><span>Tap the heart/save button on products or articles to build your shelf.</span></div>'}`,'ns-wide');
 $('.ns-category-create',b).onsubmit=async e=>{e.preventDefault();const input=$('input',e.currentTarget),name=input.value.trim();if(!name||state.categories.some(c=>c.name.toLowerCase()===name.toLowerCase()))return;state.categories.push({name,items:[]});store();if(state.user){try{await api('saved-categories.php',{action:'create',name})}catch(x){toast(x.message)}}b.remove();savedModal()};
 $$('[data-cat-key]',b).forEach(sel=>sel.onchange=async()=>{state.categories.forEach(c=>c.items=(c.items||[]).filter(i=>!(i.type===sel.dataset.catType&&i.key===sel.dataset.catKey)));const c=state.categories.find(x=>x.name===sel.value);if(c)c.items.push({type:sel.dataset.catType,key:sel.dataset.catKey});store();if(state.user&&c){try{const remote=await api('saved-categories.php',null,'GET');const rc=(remote.categories||[]).find(x=>x.name===c.name);if(rc)await api('saved-categories.php',{action:'assign',category_id:rc.id,item_type:sel.dataset.catType,item_key:sel.dataset.catKey})}catch(x){toast(x.message)}}});
 $$('[data-remove-saved]',b).forEach(btn=>btn.onclick=async()=>{if(btn.dataset.removeSaved==='product')await toggleWish({slug:btn.dataset.key,name:prettyTitle(btn.dataset.key)});else if(btn.dataset.removeSaved==='resource')await toggleResource({slug:btn.dataset.key,title:prettyTitle(btn.dataset.key)});else await toggleBookmark({slug:btn.dataset.key,title:prettyTitle(btn.dataset.key)});b.remove();savedModal()});
 $$('[data-del-cat]',b).forEach(btn=>btn.onclick=async()=>{state.categories=state.categories.filter(c=>c.name!==btn.dataset.delCat);store();b.remove();savedModal()});
}
function cartModal(){const rows=state.cart.map((x,i)=>`<div class="ns-cart-row"><div class="ns-cart-thumb">${esc((x.name||'N')[0])}</div><div class="ns-cart-info"><b>${esc(x.name)}</b><small>$${Number(x.price||0).toFixed(2)} each</small><div class="ns-qty"><button data-minus="${i}" aria-label="Decrease quantity">−</button><span>${Number(x.qty||1)}</span><button data-plus="${i}" aria-label="Increase quantity">+</button><button class="ns-remove" data-remove="${i}">Remove</button></div></div><strong>$${(Number(x.price||0)*Number(x.qty||1)).toFixed(2)}</strong></div>`).join('');const total=state.cart.reduce((a,x)=>a+Number(x.price||0)*Number(x.qty||1),0);const b=modal(`<div class="ns-modal-kicker">Shopping bag</div><h2>Your cart</h2><div class="ns-cart-list">${rows||'<div class="ns-empty">Your bag is empty.</div>'}</div><div class="ns-cart-summary"><span>Total</span><strong>$${total.toFixed(2)}</strong></div><div class="ns-cart-actions"><button class="ns-btn secondary" data-continue>Continue shopping</button><a class="ns-btn ns-link-btn ${state.cart.length?'':'is-disabled'}" href="${state.cart.length?BASE+'/checkout/':'#'}">Checkout</a></div>`,'ns-wide');$('[data-continue]',b).onclick=()=>b.remove();$$('[data-minus]',b).forEach(x=>x.onclick=()=>changeQty(+x.dataset.minus,-1,b));$$('[data-plus]',b).forEach(x=>x.onclick=()=>changeQty(+x.dataset.plus,1,b));$$('[data-remove]',b).forEach(x=>x.onclick=()=>removeCart(+x.dataset.remove,b))}
async function changeQty(i,d,b){if(!state.cart[i])return;state.cart[i].qty=Math.max(1,Math.min(10,Number(state.cart[i].qty||1)+d));await persistCart();b.remove();cartModal()}
async function removeCart(i,b){state.cart.splice(i,1);await persistCart();b.remove();cartModal()}
async function persistCart(){store();if(state.user){try{await api('cart.php',{action:'replace',items:state.cart})}catch(e){toast(e.message)}}}
async function syncCart(){if(!state.user)return;try{const j=await api('cart.php',null,'GET');const remote=j.items||[];if(state.cart.length)await api('cart.php',{action:'replace',items:state.cart});else state.cart=remote;store()}catch(e){toast(e.message)}}
async function syncSaved(){if(!state.user){store();return}try{await api('saved-sync.php',{wishlist:state.wishlist,resources:state.resources,bookmarks:state.bookmarks});const j=await api('saved-status.php',null,'GET');const titles=new Map(state.bookmarks.map(x=>[x.slug,x.title]));state.wishlist=[...new Set(j.wishlist||[])];state.resources=[...new Set(j.resources||[])];state.bookmarks=(j.bookmarks||[]).map(slug=>({slug,title:titles.get(slug)||slug.replaceAll('-',' ')}));store()}catch(e){toast(e.message)}}
function productFromCard(card){const a=$('a[href*="/shop/"]',card),h=$('h2,h3',card),price=[...card.querySelectorAll('*')].map(x=>x.textContent.trim()).find(t=>/^\$\d+(\.\d+)?$/.test(t));if(!a||!h)return null;const m=a.getAttribute('href').match(/\/shop\/([^/]+)/);return m?{slug:m[1],name:h.textContent.trim(),price:0,qty:1}:null}
function currentProduct(){const m=location.pathname.match(/\/shop\/([^/]+)\/?$/);if(!m)return null;const h=$('h1'),price=[...document.querySelectorAll('main *')].map(x=>x.textContent.trim()).find(t=>/^\$\d+(\.\d+)?$/.test(t));return{slug:m[1],name:(h?.textContent||m[1].replaceAll('-',' ')).trim(),price:0,qty:1}}
function currentArticle(){const m=location.pathname.match(/\/blog\/([^/]+)\/?$/);if(!m)return null;return{slug:m[1],title:($('h1')?.textContent||m[1].replaceAll('-',' ')).trim()}}
async function toggleWish(p){const exists=state.wishlist.includes(p.slug);state.wishlist=exists?state.wishlist.filter(x=>x!==p.slug):[...state.wishlist,p.slug];store();if(state.user){try{await api('wishlist-toggle.php',{slug:p.slug,saved:!exists})}catch(e){toast(e.message)}}applySavedStates();toast(exists?'Removed from saved items':'Saved to your shelf')}
async function toggleBookmark(a){const exists=state.bookmarks.some(x=>x.slug===a.slug);state.bookmarks=exists?state.bookmarks.filter(x=>x.slug!==a.slug):[...state.bookmarks,a];store();if(state.user){try{await api('bookmark-toggle.php',{...a,saved:!exists})}catch(e){toast(e.message)}}applySavedStates();toast(exists?'Bookmark removed':'Article bookmarked')}
async function toggleResource(r){const exists=state.resources.includes(r.slug);state.resources=exists?state.resources.filter(x=>x!==r.slug):[...state.resources,r.slug];store();if(state.user){try{await api('saved-resource-toggle.php',{slug:r.slug,saved:!exists})}catch(e){toast(e.message)}}applySavedStates();toast(exists?'Resource removed':'Resource saved')}
function heartSvg(saved){return `<svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="${saved?'currentColor':'none'}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg><span class="sr-only">${saved?'Remove from saved':'Save product'}</span>`}
function applySavedStates(){$$('[data-ns-product-save]').forEach(b=>{const saved=state.wishlist.includes(b.dataset.slug);b.classList.toggle('saved',saved);b.setAttribute('aria-pressed',String(saved));b.setAttribute('aria-label',saved?'Remove from saved':'Save product');b.title=saved?'Remove from saved':'Save product'; if(!b.querySelector('svg')) b.textContent=saved?'♥':'♡'});$$('[data-ns-resource-save]').forEach(b=>{const saved=state.resources.includes(b.dataset.slug);b.classList.toggle('saved',saved);b.setAttribute('aria-pressed',String(saved));b.textContent=saved?'Saved':'Save'});$$('[data-ns-bookmark]').forEach(b=>{const saved=state.bookmarks.some(x=>x.slug===b.dataset.slug);b.classList.toggle('saved',saved);b.setAttribute('aria-pressed',String(saved));b.textContent=saved?'Bookmarked ✓':'Bookmark article'})}
function bindCapture(el,handler){if(!el||el.dataset.nsBound)return;el.dataset.nsBound='1';el.addEventListener('click',e=>{e.preventDefault();e.stopPropagation();e.stopImmediatePropagation();handler(e)},true)}
function bindProductSaves(){
 $$('[data-ns-product-save]').forEach(button=>{
  const slug=(button.dataset.slug||'').trim();if(!slug||button.dataset.nsBound)return;
  const card=button.closest('article');const product=card?productFromCard(card):null;
  bindCapture(button,()=>toggleWish({slug,name:product?.name||button.dataset.title||slug.replaceAll('-',' ')}));
 });
}
function likeButton(type,key){const b=document.createElement('button');b.className='ns-mini-action';b.innerHTML='♡ <span>Like</span>';b.onclick=async()=>{const liked=b.classList.toggle('active');try{const j=await api(`content-like.php?type=${type}&key=${encodeURIComponent(key)}&visitor=${encodeURIComponent(visitorKey)}`,{liked,visitor:visitorKey});b.classList.toggle('active',j.liked);b.innerHTML=`${j.liked?'♥':'♡'} <span>${j.count}</span>`}catch(e){toast(e.message)}};fetch(`${API}content-like.php?type=${type}&key=${encodeURIComponent(key)}&visitor=${encodeURIComponent(visitorKey)}`,{credentials:'include',cache:'no-store'}).then(r=>r.json()).then(j=>{b.classList.toggle('active',j.liked);b.innerHTML=`${j.liked?'♥':'♡'} <span>${j.count||'Like'}</span>`}).catch(()=>{});return b}
function shareButton(url,title){const b=document.createElement('button');b.className='ns-mini-action';b.textContent='Share';b.onclick=async()=>{try{if(navigator.share)await navigator.share({title,url});else{await navigator.clipboard.writeText(url);toast('Link copied')}}catch(e){}};return b}
function enhanceProducts(){
 const detail=currentProduct();
 if(detail){document.body.classList.add('ns-product-detail-page');const h=$('h1'),pane=h?.parentElement||h?.closest('section');const cta=pane?$$('button,a',pane).find(b=>/get instant access|add to bag|add to cart|buy now|download/i.test((b.textContent||'').trim())):null;const heart=pane?$('button:has(svg.lucide-heart)',pane):null;
  if(cta){cta.dataset.nsFreeDownload='';cta.classList.add('ns-free-download');cta.innerHTML='<span>Get Free</span><b>↓</b>';bindCapture(cta,()=>gatedDownload(`${API}free-download.php?type=product&slug=${encodeURIComponent(detail.slug)}`,detail.name))}
  if(heart){heart.dataset.nsProductSave='';heart.dataset.slug=detail.slug;heart.classList.add('ns-detail-save');bindCapture(heart,()=>toggleWish(detail))}
  if(pane&&!$('.ns-detail-social',pane)){const row=document.createElement('div');row.className='ns-detail-social';row.append(likeButton('product',detail.slug),shareButton(location.href,detail.name));pane.append(row)}
  $$('main *').forEach(x=>{if(/^\$\d/.test(x.textContent.trim())||/AED\s?\d|secure checkout|payment|buy now/i.test(x.textContent.trim())){if(x.children.length===0)x.textContent=x.textContent.replace(/\$\d+(\.\d+)?|AED\s?\d+(\.\d+)?/g,'FREE').replace(/Secure checkout/ig,'No checkout required').replace(/Instant download/ig,'One-click download')}})
 }
 $$('article').forEach(card=>{const p=productFromCard(card);if(!p||card.dataset.nsFreeEnhanced)return;card.dataset.nsFreeEnhanced='1';const heart=$('button:has(svg.lucide-heart)',card);if(heart){heart.dataset.nsProductSave='';heart.dataset.slug=p.slug;heart.classList.add('ns-card-save');bindCapture(heart,()=>toggleWish(p))}$$('*',card).forEach(x=>{if(x.children.length===0&&/^\$\d/.test(x.textContent.trim()))x.textContent='FREE'});const body=$('h2,h3',card)?.parentElement;if(body&&!$('.ns-card-free-actions',body)&&!$('.ns-product-actions-v3',card)&&!$('.ns-card-footer-row',card)){const actions=document.createElement('div');actions.className='ns-card-free-actions';const dl=document.createElement('a');dl.href=`${API}free-download.php?type=product&slug=${encodeURIComponent(p.slug)}`;dl.textContent='Download';dl.className='ns-mini-action primary';actions.append(dl,likeButton('product',p.slug),shareButton(`${location.origin}${BASE}/shop/${p.slug}/`,p.name));body.append(actions);bindDownloadGate()}})
 bindProductSaves();
}
function enhanceArticle(){
 const a=currentArticle(); if(!a)return; const h=$('h1');
 if(h&&!$('.ns-detail-social')){const row=document.createElement('div');row.className='ns-detail-social';h.insertAdjacentElement('afterend',row)}
 $$('[data-ns-bookmark]').forEach(b=>{if(b.dataset.nsBookmarkBound)return;b.dataset.nsBookmarkBound='1';b.dataset.slug=b.dataset.slug||a.slug;b.dataset.title=b.dataset.title||a.title;b.classList.add('ns-bookmark-btn');b.addEventListener('click',e=>{e.preventDefault();e.stopPropagation();toggleBookmark({slug:b.dataset.slug,title:b.dataset.title||a.title})},true)});
 const row=$('.ns-detail-social');
 if(row&&!row.dataset.nsArticleActions){row.dataset.nsArticleActions='1';const b=document.createElement('button');b.dataset.nsBookmark='';b.dataset.slug=a.slug;b.dataset.title=a.title;b.className='ns-mini-action ns-bookmark-btn';b.textContent='Save';b.addEventListener('click',e=>{e.preventDefault();toggleBookmark(a)});row.append(b,likeButton('blog',a.slug),shareButton(location.href,a.title));}
 $$('[data-share]').forEach(b=>{if(!b.dataset.nsShareBound){b.dataset.nsShareBound='1';b.onclick=()=>shareButton(location.href,a.title).click()}});
 $$('[data-blog-like]').forEach(b=>{if(!b.dataset.nsLikeHydrated){const replacement=likeButton('blog',b.dataset.key||a.slug);replacement.className=b.className||'cp-btn';replacement.dataset.nsLikeHydrated='1';b.replaceWith(replacement)}});
 bindComments();applySavedStates();
}
function bindComments(){const form=$('[data-comment-form]');if(!form)return;const slug=form.dataset.slug,box=$('[data-comments]');const load=async()=>{try{const j=await api(`blog-comments.php?slug=${encodeURIComponent(slug)}`,null,'GET');box.innerHTML=(j.comments||[]).map(c=>`<div class="cp-comment"><b>${esc(c.name)}</b><p>${esc(c.comment)}</p><small>${esc(c.created_at)}</small></div>`).join('')||'<div class="ns-empty">Be the first to comment.</div>'}catch(e){}};form.onsubmit=async e=>{e.preventDefault();const f=e.currentTarget;try{await api(`blog-comments.php?slug=${encodeURIComponent(slug)}`,Object.fromEntries(new FormData(f)));if(f&&f.isConnected)f.reset();toast('Comment posted');load()}catch(x){toast(x.message)}};load()}
function addPasswordToggles(scope=document){
 $$('input[type="password"]',scope).forEach(input=>{
  if(input.dataset.nsPasswordReady)return;input.dataset.nsPasswordReady='1';
  const field=document.createElement('span');field.className='ns-password-field';
  input.before(field);field.append(input);
  const button=document.createElement('button');button.type='button';button.className='ns-password-toggle';button.setAttribute('aria-label','Show password');button.setAttribute('aria-pressed','false');button.textContent='Show';
  button.addEventListener('click',()=>{const reveal=input.type==='password';input.type=reveal?'text':'password';button.textContent=reveal?'Hide':'Show';button.setAttribute('aria-label',reveal?'Hide password':'Show password');button.setAttribute('aria-pressed',String(reveal));});field.append(button);
 });
}
function bindNewsletterForm(form){
 if(!form||form.dataset.nsNewsletterBound)return;const email=$('input[type="email"]',form);if(!email)return;form.dataset.nsNewsletterBound='1';
 let msg=$('.ns-inline-message',form); if(!msg){msg=document.createElement('div');msg.className='ns-inline-message';msg.setAttribute('role','status');form.appendChild(msg)}
 form.addEventListener('submit',async e=>{e.preventDefault();const value=email.value.trim();if(!value){msg.textContent='Please enter your email.';return}try{const j=await api('newsletter.php',{email:value});msg.textContent=j.message||'Subscribed successfully.';msg.classList.add('success');toast(j.message||'Subscribed successfully.');email.value=''}catch(x){msg.textContent=x.message;msg.classList.remove('success');toast(x.message)}})
}
function injectNewsletter(){let found=false;$$('form').forEach(f=>{const email=$('input[type="email"]',f);if(email&&/join|subscribe|newsletter/i.test((f.innerText||'')+' '+(f.className||''))){bindNewsletterForm(f);found=true}});if(found||$('.ns-newsletter'))return;const footer=$('footer');if(!footer)return;const s=document.createElement('section');s.className='ns-newsletter';s.innerHTML=`<div><span>THE NEXSHELFY LETTER</span><h2>One useful idea for your digital shelf.</h2><p>Get new templates, articles and product updates. No spam.</p></div><form><input type="email" name="email" required placeholder="you@example.com"><button type="submit">Subscribe</button></form>`;footer.before(s);bindNewsletterForm($('form',s))}

function gatedDownload(url,title='Free resource'){
 const remembered=localStorage.getItem('ns_download_email')||'';
 const recs=[['Personal Knowledge Base',BASE+'/shop/personal-knowledge-base/'],['Content Calendar',BASE+'/shop/content-calendar/'],['Finance Kit',BASE+'/shop/freelance-finance-kit/']];
 const b=modal(`<div class="ns-modal-kicker">Free download</div><h2>${esc(title||'Free resource')}</h2><p class="ns-modal-copy">Enter your email once. The download starts immediately and the lead is saved for future campaigns.</p><form class="ns-download-gate"><div class="ns-field"><label>Name <span>optional</span></label><input type="text" name="name" maxlength="120" placeholder="Your name"></div><div class="ns-field"><label>Email</label><input type="email" name="email" required value="${esc(remembered)}" placeholder="you@example.com"></div><button class="ns-btn" type="submit">Download free</button><div class="ns-inline-message" role="status"></div><div class="ns-recommendations"><b>You may also like</b>${recs.map(r=>`<a href="${r[1]}">${esc(r[0])}</a>`).join('')}</div></form>`);
 $('form',b).onsubmit=e=>{e.preventDefault();const email=$('input[name="email"]',b).value.trim();const name=$('input[name="name"]',b).value.trim();const msg=$('.ns-inline-message',b);if(!/^\S+@\S+\.\S+$/.test(email)){msg.textContent='Please enter a valid email.';return}localStorage.setItem('ns_download_email',email);try{const finalUrl=new URL(url,location.origin);finalUrl.searchParams.set('email',email);if(name)finalUrl.searchParams.set('name',name);msg.textContent='Starting download... Recommended resources are shown below.';msg.classList.add('success');setTimeout(()=>{location.href=finalUrl.toString()},220)}catch(_){toast('Download link is invalid.')}};
}
function bindDownloadGate(){
 $$('a[href*="/api/free-download.php"],a[href*="free-download.php"],button[data-ns-free-download],.ns-free-download').forEach(el=>{if(el.hasAttribute('data-ns-direct-download'))return;if(el.dataset.nsGateBound)return;el.dataset.nsGateBound='1';el.addEventListener('click',e=>{const url=el.href||el.dataset.href;if(!url)return;e.preventDefault();e.stopPropagation();gatedDownload(url,(el.closest('article')?.querySelector('h2,h3')?.textContent||el.closest('main')?.querySelector('h1')?.textContent||el.textContent||'Free resource').trim())},true)});
}
function bindResourceSaves(){
 $$('[data-ns-resource-save]').forEach(btn=>{if(btn.dataset.nsResourceBound)return;btn.dataset.nsResourceBound='1';btn.addEventListener('click',e=>{e.preventDefault();e.stopPropagation();toggleResource({slug:btn.dataset.slug,title:btn.dataset.title||prettyTitle(btn.dataset.slug)})},true)});
}
function renderSavedPage(){
 const host=$('[data-saved-page]');
 if(!host)return;
 const productRows=state.wishlist.map(slug=>({type:'Free Product',title:prettyTitle(slug),url:`${BASE}/shop/${encodeURIComponent(slug)}/`,key:slug}));
 const resourceRows=state.resources.map(slug=>({type:'Free Resource',title:prettyTitle(slug),url:`${BASE}/resources/#${encodeURIComponent(slug)}`,key:slug}));
 const blogRows=state.bookmarks.map(x=>({type:'Blog',title:x.title||prettyTitle(x.slug),url:`${BASE}/blog/${encodeURIComponent(x.slug)}/`,key:x.slug}));
 const rows=[...productRows,...resourceRows,...blogRows];
 host.innerHTML=rows.map(x=>`<article class="ns-saved-card"><span>${esc(x.type)}</span><h2>${esc(x.title)}</h2><a class="ns-mini-action primary" href="${x.url}">Open</a></article>`).join('')||'<div class="cp-empty">No saved items yet. Save products, resources or blog posts and they will appear here.</div>';
}
function bindCreatorApplication(){
 if(!/\/(creators|contact)\/?$/.test(location.pathname))return;
 $$('a,button').forEach(el=>{if(/become a creator|apply/i.test(el.textContent||'')){if(el.tagName==='A')el.href='#creator-application';el.onclick=e=>{e.preventDefault();creatorModal()}}});
}
function creatorModal(){const b=modal(`<div class="ns-modal-kicker">Creator application</div><h2>Become a creator</h2><p>Tell us why you want to join and how you can contribute.</p><form class="ns-creator-form"><div class="ns-grid-2"><input name="name" required placeholder="Full name"><input name="email" type="email" required placeholder="Email"><input name="age" placeholder="Age"><input name="gender" placeholder="Gender"><input name="qualification" placeholder="Qualifications"></div><textarea name="reason" required rows="4" placeholder="Why do you want to become a creator?"></textarea><textarea name="contribution" required rows="4" placeholder="How can you contribute to our work?"></textarea><button class="ns-btn" type="submit">Submit application</button></form>`,'ns-wide');$('form',b).onsubmit=async e=>{e.preventDefault();try{const j=await api('creator-application.php',Object.fromEntries(new FormData(e.currentTarget)));toast(j.message||'Application submitted successfully.');b.remove()}catch(x){toast(x.message)}}}
function fixCreatorsCards(){if(!/\/creators\/?$/.test(location.pathname))return;$$('a,button').forEach(el=>{if(/view profile/i.test(el.textContent||''))el.remove()})}
function paginateBlogCards(){if(!/\/blog\/?$/.test(location.pathname)||document.body.dataset.nsBlogPaged)return;const cards=$$('main article.cp-card, main article').filter(x=>!x.closest('#blogGrid'));if(cards.length<=9)return;document.body.dataset.nsBlogPaged='1';cards.forEach((c,i)=>{if(i>=9)c.classList.add('ns-page-hidden')});const btn=document.createElement('button');btn.className='ns-btn ns-load-more';btn.textContent='Load more posts';let shown=9;btn.onclick=()=>{shown+=9;cards.forEach((c,i)=>{if(i<shown)c.classList.remove('ns-page-hidden')});if(shown>=cards.length)btn.remove()};cards[cards.length-1].after(btn)}

function bindContactForm(){if(!/\/contact\/?$/.test(location.pathname))return;const form=$('main form');if(!form||form.dataset.nsContactBound)return;form.dataset.nsContactBound='1';const inputs=$$('input,select,textarea',form);if(inputs[0])inputs[0].name='name';if(inputs[1])inputs[1].name='email';if(inputs[2])inputs[2].name='subject';if(inputs[3])inputs[3].name='message';form.addEventListener('submit',async e=>{e.preventDefault();const payload=Object.fromEntries(new FormData(form));try{const j=await api('contact.php',payload);toast(j.message||'Message sent');inputs.forEach(i=>{if(i.tagName!=='SELECT')i.value=''})}catch(x){toast(x.message)}})}
function repairLinks(){
 const map={Blog:BASE+'/blog/',Shop:BASE+'/shop/',Collections:BASE+'/collections/',About:BASE+'/about/',Creators:BASE+'/creators/',Contact:BASE+'/contact/',Privacy:BASE+'/privacy/',Terms:BASE+'/terms/',Licenses:BASE+'/terms/'};
 $$('footer a[href="#"]').forEach(a=>{const key=a.textContent.trim();if(map[key])a.href=map[key]});
 $$('a[href^="/nexshelfy/"]').forEach(a=>{if(!BASE)a.setAttribute('href',a.getAttribute('href').replace(/^\/nexshelfy/,'')||'/')});
 $$('a[href^="/shop"],a[href^="/blog"],a[href^="/collections"],a[href^="/creators"],a[href^="/dashboard"],a[href^="/checkout"]').forEach(a=>{const href=a.getAttribute('href');if(BASE&&href&&!href.startsWith(BASE))a.setAttribute('href',BASE+href)});
}
function enhanceCollections(){if(!/\/collections\/?$/.test(location.pathname))return;if(document.body.classList.contains('ns-collections-premium-page'))return;const main=$('main');if(!main||$('.ns-collection-search'))return;const box=document.createElement('section');box.className='ns-collection-search';box.innerHTML='<div><span>EXPLORE BY TOPIC</span><h2>Choose what you want to learn.</h2></div><input placeholder="Search blog topics…"><div class="ns-topic-results">Loading topics…</div>';main.prepend(box);const input=$('input',box),results=$('.ns-topic-results',box);fetch(API+'blog-list.php',{credentials:'include'}).then(r=>r.json()).then(j=>{const posts=j.posts||[];const render=()=>{const q=input.value.toLowerCase();results.innerHTML=posts.filter(p=>(p.title+' '+p.category+' '+p.excerpt).toLowerCase().includes(q)).map(p=>`<a href="${BASE}/blog/${p.slug}/"><b>${esc(p.title)}</b><span>${esc(p.category||'Article')}</span></a>`).join('')||'<p>No matching topic.</p>'};input.oninput=render;render()}).catch(()=>{results.textContent='Topics are unavailable.'})}
function disableBagUI(){
 $$('a,button').forEach(el=>{
   const t=(el.textContent||'').trim();
   if(/^Bag(?:\s*\d+)?$/i.test(t)||/cart|bag/i.test(el.getAttribute('aria-label')||'')){
     el.hidden=true;el.setAttribute('aria-hidden','true');
   }
 });
}
function applyFreeCopy(){const replacements=[['Explore Shelf','Free Resources'],['Premium Resources','Free Resources'],['Shelf','Shop'],['Discover','Discover']];$$('h1,h2,h3,a,button,span').forEach(el=>{if(el.children.length)return;let t=el.textContent.trim();for(const [a,b] of replacements)if(t===a)t=b;if(/buy now|purchase|get instant access|add to bag|add to cart/i.test(t))t='Download free';el.textContent=t});disableBagUI();$$('main *').forEach(el=>{if(el.children.length===0&&/^\$\d+(\.\d+)?$/.test(el.textContent.trim()))el.textContent='FREE'})}

function hardFreePass(){
  $$('main *').forEach(el=>{
    if(el.children.length===0){
      const t=el.textContent.trim();
      if(/^\$\s*\d+(\.\d+)?$/.test(t)||/^AED\s*\d+(\.\d+)?$/i.test(t)) el.textContent='FREE';
      if(/premium resources/i.test(t)) el.textContent=t.replace(/Premium Resources/ig,'Free Resources');
      if(/explore shelf/i.test(t)) el.textContent=t.replace(/Explore Shelf/ig,'Free Resources');
      if(/add to bag|add to cart|buy now|get instant access|purchase/i.test(t)) el.textContent='Download free';
    }
  });
  $$('a,button').forEach(el=>{const t=(el.textContent||'').trim(); if(/^view profile$/i.test(t)&&/\/creators\/?$/.test(location.pathname)) el.remove();});
}
function watchHydration(){
  let ticking=false; const run=()=>{if(ticking)return;ticking=true;requestAnimationFrame(()=>{ticking=false;repairLinks();removeDuplicatePageNewsletters();applyFreeCopy();hardFreePass();enhanceProducts();enhanceArticle();bindDownloadGate();bindResourceSaves();fixCreatorsCards();paginateBlogCards();applySavedStates();});};
  try{new MutationObserver(run).observe(document.body,{childList:true,subtree:true,characterData:true});}catch(e){}
  setTimeout(run,400);setTimeout(run,1200);setTimeout(run,2500);
}


function removeDuplicatePageNewsletters(){
 const foot=$('footer'); if(!foot)return;
 $$('main > .ns-newsletter.ns-premium-newsletter, main .ns-newsletter.ns-premium-newsletter').forEach(n=>{if(!n.closest('footer')) n.remove();});
}
function enhanceCollectionSearch(){
 if(!/\/collections\/?$/.test(location.pathname))return;
 const cards=$$('.ns-outcome-grid a,.ns-collection-grid a');
 const input=$('.ns-collections-main input[name="q"],.ns-collections-main .ns-glass-search input');
 if(!input||input.dataset.nsCollectionFilter)return;
 input.dataset.nsCollectionFilter='1';
 input.addEventListener('input',()=>{const q=input.value.trim().toLowerCase();cards.forEach(c=>{c.classList.toggle('ns-collection-card-hidden',q && !(c.textContent||'').toLowerCase().includes(q));});});
 const form=input.closest('form'); if(form){form.addEventListener('submit',e=>{if(input.value.trim()){return;}e.preventDefault();input.focus();});}
}
function protectFooterFromSticky(){
 const sticky=$('.ns-sticky-download'); const footer=$('footer'); if(!sticky||!footer||sticky.dataset.nsFooterWatch)return; sticky.dataset.nsFooterWatch='1';
 const io=new IntersectionObserver(entries=>entries.forEach(en=>sticky.classList.toggle('is-near-footer',en.isIntersecting)),{threshold:.01}); io.observe(footer);
}

async function refreshAccountState(){try{const j=await api('status.php',null,'GET');state.csrf=j.csrf||state.csrf;state.user=j.user||null;if(state.user){const saved=await api('saved-status.php',null,'GET');const titles=new Map(state.bookmarks.map(x=>[x.slug,x.title]));state.wishlist=[...new Set(saved.wishlist||[])];state.resources=[...new Set(saved.resources||[])];state.bookmarks=(saved.bookmarks||[]).map(slug=>({slug,title:titles.get(slug)||slug.replaceAll('-',' ')}));}store()}catch(e){}}
window.addEventListener('storage',e=>{if(['ns_wishlist','ns_resources','ns_bookmarks','ns_cart'].includes(e.key)){state.cart=read('ns_cart',[]);state.wishlist=read('ns_wishlist',[]);state.resources=read('ns_resources',[]);state.bookmarks=read('ns_bookmarks',[]);renderHeader();applySavedStates();renderSavedPage()}});
document.addEventListener('visibilitychange',()=>{if(!document.hidden)refreshAccountState()});
window.addEventListener('focus',refreshAccountState);
async function boot(){normalizePrimaryNavigation();loadGlobalTheme();try{const j=await api('status.php',null,'GET');state.csrf=j.csrf||'';state.user=j.user||null;if(state.user){await syncSaved();try{const c=await api('saved-categories.php',null,'GET');state.categories=(c.categories||[]).map(x=>({name:x.name,items:x.items||[]}));store()}catch(e){}}}catch(e){}repairLinks();removeDuplicatePageNewsletters();applyFreeCopy();hardFreePass();renderHeader();disableBagUI();setTimeout(disableBagUI,250);enhanceProducts();enhanceArticle();enhanceCollections();enhanceCollectionSearch();removeDuplicatePageNewsletters();injectNewsletter();protectFooterFromSticky();bindContactForm();bindDownloadGate();bindResourceSaves();bindCreatorApplication();fixCreatorsCards();paginateBlogCards();addPasswordToggles();applySavedStates();renderSavedPage();watchHydration();const params=new URLSearchParams(location.search);if(params.get('auth')==='login'&&!state.user)authModal()}
document.addEventListener('DOMContentLoaded',boot);
})();

/* Removed duplicate global download gate. The main bindDownloadGate() above now owns download popups, preventing double modal/popup issues. */

// NexShelfy premium homepage micro-interactions 2026-07-10
(()=>{
  const $$=(s,r=document)=>Array.from(r.querySelectorAll(s));
  const compact=(n)=> n>=1000 ? (n>=10000?Math.round(n/1000)+'k+':n.toLocaleString()+'+') : String(n);
  function animateCounters(){
    $$('[data-ns-counter]').forEach(el=>{
      if(el.dataset.nsCounted) return; el.dataset.nsCounted='1';
      const target=parseInt(el.dataset.nsCounter,10)||0; const start=performance.now(); const duration=1100;
      const tick=(now)=>{const p=Math.min(1,(now-start)/duration);const eased=1-Math.pow(1-p,3);el.textContent=compact(Math.floor(target*eased)); if(p<1) requestAnimationFrame(tick); else el.textContent=compact(target)};
      requestAnimationFrame(tick);
    });
  }
  function reveal(){
    const nodes=$$('.ns-home-section,.ns-stats-strip,.ns-trust-band,.ns-newsletter');
    nodes.forEach(n=>n.classList.add('ns-reveal'));
    const io=new IntersectionObserver(entries=>entries.forEach(e=>{if(e.isIntersecting){e.target.classList.add('is-visible');io.unobserve(e.target)}}),{threshold:.12});
    nodes.forEach(n=>io.observe(n));
  }
  function scrollState(){document.body.classList.toggle('ns-scrolled',window.scrollY>8)}
  document.addEventListener('DOMContentLoaded',()=>{animateCounters();reveal();scrollState();window.addEventListener('scroll',scrollState,{passive:true});});
})();

// NexShelfy blog/shop premium interactions 2026-07-10
(()=>{
  const $$=(s,r=document)=>Array.from(r.querySelectorAll(s));
  const $=(s,r=document)=>r.querySelector(s);
  function syncInputs(a,b){ if(a&&b&&!a.dataset.synced){a.dataset.synced='1';a.addEventListener('input',()=>{b.value=a.value;b.dispatchEvent(new Event('input'))});b.addEventListener('input',()=>{a.value=b.value});}}
  function blogFilters(){
    const q=$('#blogSearch'), qh=$('#blogSearchHero'), c=$('#blogCategory'), ch=$('#blogCategoryHero'), s=$('#blogSortBottom'), sh=$('#blogSort');
    syncInputs(q,qh); if(c&&ch){ch.addEventListener('change',()=>{c.value=ch.value;filter()});c.addEventListener('change',()=>{ch.value=c.value;filter()});}
    if(s&&sh){sh.addEventListener('change',()=>{s.value=sh.value;filter()});s.addEventListener('change',()=>{sh.value=s.value;filter()});}
    function filter(){
      const term=(q?.value||qh?.value||'').toLowerCase(); const cat=(c?.value||ch?.value||''); const sort=(s?.value||sh?.value||'new');
      const grid=$('#blogGrid'); if(!grid)return; const cards=$$('#blogGrid article');
      const visible=cards.filter(card=>(card.dataset.title||'').includes(term)&&(!cat||(card.dataset.category||'').toLowerCase()===cat.toLowerCase()||(card.dataset.title||'').includes(cat.toLowerCase())));
      visible.sort((a,b)=> sort==='resources' ? ((+b.dataset.resources||0)-(+a.dataset.resources||0)) : sort==='popular' ? ((+b.dataset.index||0)-(+a.dataset.index||0)) : ((+a.dataset.index||0)-(+b.dataset.index||0)) );
      cards.forEach(x=>x.hidden=true); visible.forEach(x=>{x.hidden=false;grid.appendChild(x)});
    }
    [q,qh,c,ch,s,sh].forEach(el=>el&&el.addEventListener(el.tagName==='INPUT'?'input':'change',filter));
    $$('[data-blog-chips] button').forEach(btn=>btn.addEventListener('click',()=>{ $$('[data-blog-chips] button').forEach(b=>b.classList.remove('active')); btn.classList.add('active'); if(c){c.value=btn.dataset.topic||'';} if(ch){ch.value=btn.dataset.topic||'';} filter();}));
  }
  function productFilters(){
    const q=$('#productSearch'), t=$('#productType');
    function filter(){const term=(q?.value||'').toLowerCase(),type=t?.value||'';$$('#productGrid article').forEach(card=>{card.hidden=!((card.dataset.title||'').includes(term)&&(!type||card.dataset.type===type));});}
    q&&q.addEventListener('input',filter);t&&t.addEventListener('change',filter);
    $$('[data-product-topic]').forEach(btn=>btn.addEventListener('click',()=>{$$('[data-product-topic]').forEach(b=>b.classList.remove('active'));btn.classList.add('active');if(q){q.value=btn.dataset.productTopic||'';filter();}}));
  }
  function readingProgress(){const bar=$('.ns-reading-progress span');if(!bar)return;const run=()=>{const h=document.documentElement;const max=h.scrollHeight-innerHeight;bar.style.width=(max?Math.min(100,scrollY/max*100):0)+'%'};addEventListener('scroll',run,{passive:true});run();}
  function toc(){const nav=$('[data-toc]'),src=$('[data-toc-source]');if(!nav||!src)return;const heads=$$('h2',src).filter(h=>h.textContent.trim());nav.innerHTML=heads.map((h,i)=>{if(!h.id)h.id='section-'+(i+1);return `<a href="#${h.id}">${h.textContent.replace(/</g,'&lt;')}</a>`}).join('');const links=$$('a',nav);const mark=()=>{let active=0;heads.forEach((h,i)=>{if(h.getBoundingClientRect().top<160)active=i});links.forEach((a,i)=>a.classList.toggle('active',i===active));};addEventListener('scroll',mark,{passive:true});mark();}
  function shareButtons(){ $$('[data-share]').forEach(btn=>{ if(btn.dataset.nsShareBound)return; btn.dataset.nsShareBound='1'; btn.addEventListener('click',async()=>{try{await navigator.clipboard.writeText(location.href); btn.textContent='Copied'; setTimeout(()=>btn.textContent=btn.classList.contains('cp-btn')?'Share':'Copy link',1200)}catch(e){location.href='mailto:?subject=NexShelfy&body='+encodeURIComponent(location.href)}});});}
  document.addEventListener('DOMContentLoaded',()=>{blogFilters();productFilters();readingProgress();toc();shareButtons();});
})();
