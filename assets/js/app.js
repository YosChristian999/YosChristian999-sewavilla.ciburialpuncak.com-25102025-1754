/* /assets/js/app.js  — v1.0 */
(() => {
  // ==== PATH (root + kompatibel jika nanti pakai /ciburial) ====
  const inSubdir = location.pathname.startsWith('/ciburial/');
  const ROOT_PREFIX = inSubdir ? '/ciburial' : '';
  const API_BASE    = `${ROOT_PREFIX}/backend/api`;
  const ASSETS_BASE = `${ROOT_PREFIX}/assets`;

  // Ekspos kalau butuh di file lain
  window.ROOT_PREFIX = ROOT_PREFIX;
  window.API_BASE    = API_BASE;
  window.ASSETS_BASE = ASSETS_BASE;

  // ==== Favikon fix (hindari literal ${ROOT_PREFIX}) ====
  const setFavicon = () => {
    const link = document.querySelector('link[rel="icon"]');
    if (link) link.setAttribute('href', '/favicon.ico');
  };

  // ==== Helper pilih elemen dengan fallback beberapa selector ====
  const pick = (...sels) => {
    for (const s of sels) { const el = document.querySelector(s); if (el) return el; }
    return null;
  };
  const fixHref = (el, href) => { if (el) el.setAttribute('href', href); };

  // ==== Navbar links absolut (hilangkan 404) ====
  const fixNavLinks = () => {
    fixHref(pick('#nav-home','a[href="/index.html"]','a[href="index.html"]','a.navbar-brand'), '/');
    fixHref(pick('#nav-about','a[href$="about.html"]'),   '/about.html');
    fixHref(pick('#nav-booking','a[href$="booking.html"]'), '/booking.html');
    // Section payment di halaman ini tetap anchor:
    fixHref(pick('#nav-payment','a[href="#payment"]'), '#payment');
    fixHref(pick('#nav-contact','a[href$="contact.html"]'), '/contact.html');
    fixHref(pick('#nav-login','a[href$="login.html"]'),   '/login.html');
    fixHref(pick('#nav-admin','a[href$="admin.html"]'),   '/admin.html');
  };

  // ==== Hero background (pastikan tampil) ====
  const ensureHeroBackground = () => {
    const hero = document.querySelector('.hero');
    if (hero) {
      // CSS sudah set via style.css, ini guard tambahan:
      hero.style.backgroundImage = `url('/assets/BGHero.jpg')`;
    }
  };

  // ==== Particles (di bawah konten, tidak menutupi modal/konten) ====
  const initParticles = async () => {
    if (!window.tsParticles) return;
    const holder = document.getElementById('heroParticles');
    if (!holder) return;

    await tsParticles.load({
      id: 'heroParticles',
      options: {
        fullScreen: { enable: false },
        detectRetina: true,
        background: { color: 'transparent' },
        particles: {
          number: { value: 45, density: { enable: true, area: 900 } },
          color: { value: ['#67e8f9','#60a5fa','#a78bfa'] },
          shape: { type: 'circle' },
          opacity: { value: 0.35, random: true },
          size: { value: { min: 1, max: 3 } },
          links: { enable: true, opacity: 0.15, distance: 120 },
          move: { enable: true, speed: 1.4, outModes: 'out' }
        },
        interactivity: {
          events: { onHover: { enable: true, mode: 'repulse' }, resize: true },
          modes: { repulse: { distance: 120, duration: 0.3 } }
        }
      }
    });
  };

  // ==== AOS (animasi scroll ringan) ====
  const initAOS = () => {
    if (window.AOS) AOS.init({ duration: 700, once: true, easing: 'ease-out-quart' });
  };

  // ==== Modal "Villa Kami" (fetch list ketika modal dibuka) ====
  function rupiah(n){ return 'Rp ' + Number(n||0).toLocaleString('id-ID'); }
  function basePath(){ return location.pathname.replace(/[^\/]+$/, ''); }

  const COVER_BY_ID = {
    1:'Villa1Arikokena.png',2:'Villa2Tiger.png',3:'Villa3Cahaya.png',
    4:'Villa4BataCCitra.png',5:'Villa5BataAgricon.png',6:'Villa6Dinar3.png',
    7:'Villa7Yangtiq.png',8:'Villa8HDO.jpg',9:'Villa9Archie.jpg',
    10:'Villa10Rick.png',11:'Villa11Zambrud.jpg'
  };
  function imgFallback(img){
    if(img.dataset.try1!=='1' && img.dataset.alt1){ img.dataset.try1='1'; img.src = img.dataset.alt1; return; }
    if(img.dataset.try2!=='1' && img.dataset.alt2){ img.dataset.try2='1'; img.src = img.dataset.alt2; return; }
    img.src = basePath() + 'assets/images/pleaceholder.jpg';
  }
  function coverCandidates(v){
    const c = [];
    if (v.url_gambar) {
      const p = String(v.url_gambar).replace(/^\/*/,'');
      c.push(basePath()+p);
    }
    if (COVER_BY_ID[v.id]) c.push(basePath()+'assets/images/VillaPic/'+COVER_BY_ID[v.id]);
    c.push(basePath()+'assets/images/pleaceholder.jpg');
    return c;
  }

  function renderSkeleton(){
    const wrap = document.getElementById('qmGrid');
    if (!wrap) return;
    wrap.innerHTML = Array.from({length:6}).map(()=>`
      <div class="col-12 col-md-6 col-lg-4">
        <article class="qm-card loading">
          <div class="cover skeleton"></div>
          <div class="card-body">
            <div class="sk-row"></div>
            <div class="sk-row sm"></div>
            <div class="sk-row sm"></div>
          </div>
        </article>
      </div>
    `).join('');
  }

  function renderGrid(list){
    const wrap = document.getElementById('qmGrid');
    if (!wrap) return;
    if (!list.length) {
      wrap.innerHTML = `<div class="col-12"><div class="alert alert-warning mb-0">Data villa belum tersedia.</div></div>`;
      return;
    }
    wrap.innerHTML = list.map(v=>{
      const cands = coverCandidates(v);
      const first = cands[0], alt1=cands[1]||'', alt2=cands[2]||'';
      return `
      <div class="col-12 col-md-6 col-lg-4">
        <article class="qm-card">
          <img class="cover" src="${first}" data-alt1="${alt1}" data-alt2="${alt2}" alt="${v.nama_villa||'Villa'}" onerror="(${imgFallback})(this)">
          <div class="card-body">
            <h6 class="mb-1">${v.nama_villa||'Villa'}</h6>
            <p class="small text-secondary mb-3">${(v.deskripsi||'').slice(0,150)}</p>
            <div class="d-flex justify-content-between align-items-center">
              <span class="text-info fw-semibold">${rupiah(v.harga_per_malam||v.harga)}/malam</span>
              <a class="btn btn-info btn-sm" href="${basePath()}detail.html?villa=${encodeURIComponent(v.id)}">Pilih</a>
            </div>
          </div>
        </article>
      </div>`;
    }).join('');
  }

  const bindQuickModal = () => {
    const modalEl = document.getElementById('quickModal');
    const trigger   = document.getElementById('navVillaLink') || pick('a[href="#quickModal"]');
    if (trigger) {
      trigger.addEventListener('click', (e)=>{ e.preventDefault(); new bootstrap.Modal('#quickModal').show(); });
    }
    if (modalEl) {
      modalEl.addEventListener('show.bs.modal', async () => {
        renderSkeleton();
        try{
          const res  = await fetch(`${API_BASE}/villas/list.php`, {cache:'no-store'});
          const text = await res.text();
          let j; try{ j=JSON.parse(text);}catch{ throw new Error('list.php bukan JSON'); }
          const data = Array.isArray(j)? j : (j.villas||[]);
          renderGrid(data);
        }catch(e){
          document.getElementById('qmGrid').innerHTML =
            `<div class="col-12"><div class="alert alert-danger mb-0">Gagal memuat data villa. ${e.message}</div></div>`;
        }
      });
    }
  };

  // ==== WA CTA: kecilkan/tinggikan ring sesuai scroll (bonus UX ringan) ====
  const tuneWA = () => {
    const wa = document.querySelector('.wa-float');
    if (!wa) return;
    const onScroll = () => {
      const y = window.scrollY || 0;
      wa.style.transform = `translateY(${Math.min(8, y/100)}px)`;
    };
    window.addEventListener('scroll', onScroll, {passive:true});
  };

  // ==== Init ====
  document.addEventListener('DOMContentLoaded', () => {
    setFavicon();
    fixNavLinks();
    ensureHeroBackground();
    initAOS();
    initParticles();
    bindQuickModal();
    tuneWA();
  });
})();
