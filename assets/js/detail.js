// assets/js/detail.js
const PLACEHOLDER =
  'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="800" height="450"><rect width="100%" height="100%" fill="%23101735"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="%23bcd4ff" font-family="Inter,Arial" font-size="28">No Image</text></svg>';

const API_BASE = new URL('/ciburial/backend/api/', location.origin).href;
const fmtRp = n => 'Rp ' + Number(n || 0).toLocaleString('id-ID');
const gid   = id => document.getElementById(id);
const qs    = k  => new URLSearchParams(location.search).get(k) || '';

async function jsonSafe(resp){
  const t = await resp.text();
  try { return JSON.parse(t); }
  catch(e){ console.error('NON-JSON from server:\n', t); throw new Error('Server mengembalikan non-JSON'); }
}

/* ===== Load detail villa ===== */
(async function loadVilla(){
  const id = qs('villa');
  if(!id){ alert('ID villa tidak ada'); location.href='index.html'; return; }

  try{
    const r = await fetch(API_BASE + 'villas/get.php?id=' + encodeURIComponent(id), { credentials:'omit' });
    if(!r.ok) throw new Error('HTTP ' + r.status);
    const j = await jsonSafe(r);
    if(!j.ok) throw new Error(j.error || 'Villa tidak ditemukan');

    const v = j.data;
    window.__villaDetail = v; // <— penting: simpan untuk dipakai saat bayar

    gid('villaId').value      = v.id;
    gid('vName').textContent  = v.nama_villa;
    gid('vDesc').textContent  = v.deskripsi || '';
    const img = gid('vImg');
    img.src = v.url_gambar || PLACEHOLDER;
    img.alt = v.nama_villa;
    img.onerror = () => (img.src = PLACEHOLDER);
    gid('vPrice').textContent = fmtRp(v.harga_per_malam);
  }catch(err){
    alert('Gagal memuat detail villa: ' + err.message);
    console.error(err);
  }
})();

/* ===== Bayar (Midtrans Snap) ===== */
async function bayarSekarang(){
  const v        = window.__villaDetail || {};
  const villaId  = parseInt(gid('villaId').value,10);
  const checkin  = gid('checkin').value;
  const checkout = gid('checkout').value;
  const payPlan  = 'full';
  const customer_name  = (gid('custName')  || {}).value?.trim?.() || 'Guest';
  const customer_email = (gid('custEmail') || {}).value?.trim?.() || 'guest@example.com';
  const customer_phone = (gid('custPhone') || {}).value?.trim?.() || '08123456789';

  if(!villaId || !checkin || !checkout){
    alert('Lengkapi data (villa, check-in, check-out).');
    return;
  }

  try{
    const resp = await fetch(API_BASE + 'payment/create_transaction.php', {
      method:'POST',
      headers:{ 'Content-Type':'application/json' },
      credentials:'omit',
      body: JSON.stringify({ villa_id:villaId, checkin, checkout, pay_plan:payPlan, customer_name, customer_email, customer_phone })
    });
    if(!resp.ok){
      const t = await resp.text().catch(()=> '');
      throw new Error('HTTP ' + resp.status + ' ' + resp.statusText + '\n' + t);
    }
    const json = await jsonSafe(resp);
    if(!json.ok) throw new Error(json.error || 'Gagal membuat transaksi');

    const { token, redirect_url, order_id } = json.data;

    // data ringkas untuk thankyou.html
    const nights = Math.max(1, Math.ceil((new Date(checkout) - new Date(checkin)) / 86400000));
    const total  = nights * Number(v.harga_per_malam || 0);

    sessionStorage.setItem('lastOrderInfo', JSON.stringify({
      order_id,
      villa: v.nama_villa || ('Villa #' + villaId),
      tanggal: `${checkin} → ${checkout} (${nights} malam)`,
      paket: payPlan === 'full' ? 'Bayar Penuh' : 'Cicilan',
      amount: fmtRp(total)
    }));

    const goThankYou = () => location.href = basePath() + 'thankyou.html?order_id=' + encodeURIComponent(order_id);

    if (window.snap && token){
      window.snap.pay(token, { onSuccess: goThankYou, onPending: goThankYou, onError: () => alert('Pembayaran gagal/ditolak'), onClose: () => {} });
    } else if (redirect_url){
      location.href = redirect_url;
    } else {
      alert('Token/redirect_url tidak tersedia.');
    }
  }catch(err){
    alert('Terjadi kesalahan jaringan\n' + err.message);
    console.error(err);
  }
}

function basePath(){ return location.pathname.replace(/[^\/]+$/, ''); }

const btn = document.getElementById('btnBayar');
if (btn) btn.addEventListener('click', bayarSekarang);
