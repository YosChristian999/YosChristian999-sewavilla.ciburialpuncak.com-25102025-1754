// initGallery(villaId, containerId)
// Render daftar gambar/video dari /backend/api/villas/get.php
window.initGallery = async function(villaId, containerId) {
const cont = document.getElementById(containerId);
if (!cont) return;
cont.innerHTML = '<div class="text-secondary">Memuat galeri...</div>';

try {
    const res = await fetch(/backend/api/villas/get.php?id=${encodeURIComponent(villaId)}, { cache: 'no-store' });
const data = await res.json();
if (!data || data.ok === false) {
const msg = (data && data.error) ? data.error : 'Gagal memuat data';
throw new Error(msg);
}
const list = Array.isArray(data.media) ? data.media : [];
if (!list.length) {
  cont.innerHTML = '<div class="text-secondary">Belum ada media.</div>';
  return;
}

cont.innerHTML = list.map(m => {
  const type = (m.type || 'image').toLowerCase();
  const url  = String(m.url || '').trim();
  if (!url) return '';

  if (type === 'video') {
    return `
      <video controls muted playsinline
             style="width:100%;max-height:360px;border-radius:12px;border:1px solid rgba(255,255,255,.12);background:#000;margin-bottom:10px">
        <source src="${url}" type="video/mp4">
      </video>`;
  }
  return `
    <img src="${url}" alt=""
         style="width:100%;object-fit:cover;max-height:360px;border-radius:12px;border:1px solid rgba(255,255,255,.12);background:#0a0f16;margin-bottom:10px">`;
}).join('');
} catch (e) {
cont.innerHTML = <div class="text-danger small">Galeri gagal dimuat: ${e.message}</div>;
}
};