// Mobile menu toggle
(function(){
  const body = document.body;
  const btn  = document.querySelector('.mobile-toggle');
  const close= document.querySelector('.mobile-close');
  const ov   = document.querySelector('.mobile-overlay');
  function open(){ body.classList.add('menu-open'); }
  function shut(){ body.classList.remove('menu-open'); }
  btn && btn.addEventListener('click', open);
  close && close.addEventListener('click', shut);
  ov && ov.addEventListener('click', shut);
}());

// Smooth scroll ke section villa
window.scrollToVilla = function(){
  const el = document.getElementById('villa-section');
  if (!el) return;
  el.scrollIntoView({ behavior:'smooth', block:'start' });
};

// Copy nomor rekening
window.copyBankNumber = async function(no){
  try {
    await navigator.clipboard.writeText(no);
    alert('Nomor rekening disalin: ' + no);
  } catch(e){
    prompt('Salin nomor rekening:', no);
  }
};
