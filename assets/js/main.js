// assets/js/main.js

// ====== Util ======
const fmtRp = n => 'Rp ' + Number(n || 0).toLocaleString('id-ID');

// ====== Nav (burger) ======
(function setupNav(){
  const btn = document.getElementById('hamburgerBtn');
  const nav = document.getElementById('mainNav');
  if (!btn || !nav) return;
  btn.addEventListener('click', () => nav.classList.toggle('open'));
})();

// ====== Auth UI (login/logout/admin) ======
async function refreshAuthUI(){
  try {
    const r = await fetch('backend/api/auth/check_auth.php', { credentials: 'include' });
    const j = await r.json();

    const loginBtn   = document.getElementById('loginBtn');
    const logoutBtn  = document.getElementById('logoutButton');
    const adminBtn   = document.getElementById('adminBtn');
    const greet      = document.getElementById('userGreeting');

    const user = j?.data?.user || null;

    if (user) {
      loginBtn && (loginBtn.style.display = 'none');
      logoutBtn && (logoutBtn.style.display = 'inline-block');
      greet && (greet.textContent = `Hi, ${user.name}`);
      adminBtn && (adminBtn.style.display = (user.role === 'admin' ? 'inline-block' : 'none'));
    } else {
      loginBtn && (loginBtn.style.display = 'inline-block');
      logoutBtn && (logoutBtn.style.display = 'none');
      greet && (greet.textContent = '');
      adminBtn && (adminBtn.style.display = 'none');
    }

    // logout action
    if (logoutBtn && !logoutBtn._bound) {
      logoutBtn._bound = true;
      logoutBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        await fetch('backend/api/auth/logout.php', { credentials: 'include' });
        await refreshAuthUI();
      });
    }
  } catch (e) {
    console.warn('Auth check fail', e);
  }
}

// ====== Render daftar villa ke #villaList ======
async function renderVillas(){
  const wrap = document.getElementById('villaList');
  if (!wrap) return;

  // Placeholder inline (anti-404, tidak butuh file)
  const PLACEHOLDER = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="800" height="450"><rect width="100%" height="100%" fill="%23101735"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="%23bcd4ff" font-family="Inter,Arial" font-size="28">No Image</text></svg>';

  wrap.innerHTML = `<div class="villa-card" style="padding:16px;border:1px solid rgba(255,255,255,.08);border-radius:12px;background:#101735">Memuat villa…</div>`;

  try{
    const res = await fetch('backend/api/villas/list.php', { credentials: 'include' });
    const json = await res.json();
    if(!json.ok) throw new Error(json.error||'Gagal memuat data');

    wrap.innerHTML = '';
    json.data.forEach(v=>{
      const path = (v.url_gambar||'').trim();           // path dari DB
      const imgSrc = path || PLACEHOLDER;               // jika kosong -> inline placeholder

      const card = document.createElement('article');
      card.className = 'villa-card';
      card.innerHTML = `
        <div class="villa-image"><img class="thumb" src="${imgSrc}" alt="${v.nama_villa}"></div>
        <div class="villa-content">
          <h3 class="villa-title">${v.nama_villa}</h3>
          <p class="villa-desc">${(v.deskripsi||'').slice(0,120)}${(v.deskripsi||'').length>120?'…':''}</p>
          <div class="villa-meta">
            <span class="villa-price">Rp ${Number(v.harga_per_malam).toLocaleString('id-ID')}/malam</span>
            <a class="btn btn-primary" href="detail.html?villa=${v.id}">Lihat detail</a>
          </div>
        </div>
      `;
      const imgEl = card.querySelector('img');
      imgEl.onerror = ()=>{ imgEl.onerror=null; imgEl.src = PLACEHOLDER; }; // kalau path DB salah → fallback inline

      wrap.appendChild(card);
    });

    if(json.data.length===0){
      wrap.innerHTML = `<div class="villa-card" style="padding:16px;border:1px solid rgba(255,255,255,.08);border-radius:12px;background:#101735">Belum ada data villa.</div>`;
    }
  }catch(e){
    console.error(e);
    wrap.innerHTML = `<div class="villa-card" style="padding:16px;border:1px solid rgba(255,255,255,.08);border-radius:12px;background:#101735">Terjadi kesalahan memuat data.</div>`;
  }
}

// ====== Smooth scroll tombol "Jelajahi Villa Kami" ======
(function smoothScroll(){
  document.querySelectorAll('a[href^="#"]').forEach(a=>{
    a.addEventListener('click', (ev)=>{
      const id = a.getAttribute('href');
      if (!id || id === '#') return;
      const el = document.querySelector(id);
      if (el) {
        ev.preventDefault();
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });
})();

// ====== Bootstrap page ======
document.addEventListener('DOMContentLoaded', async ()=>{
  await refreshAuthUI();
  await renderVillas();
});
