(() => {
  const form  = document.getElementById('loginForm');
  if (!form) return;
  const email = document.getElementById('email');
  const pass  = document.getElementById('password');
  const btn   = document.getElementById('btnLogin');
  const msg   = document.getElementById('msg');

  async function postJSON(url, data){
  const r = await fetch(url, {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify(data)
  });
  const text = await r.text();
  let json = null;
  try { json = JSON.parse(text); } catch(e){}
  return { ok: r.ok && json && json.ok, status:r.status, body: json || {raw:text} };
}

form.addEventListener('submit', async (e)=>{
  e.preventDefault();
  msg.textContent = '';
  btn.disabled = true;

  const {ok,status,body} = await postJSON('/backend/api/auth/login.php', {
    email: email.value.trim(),
    password: pass.value
  });

  if (ok) {
    // redirect ke dashboard admin (nanti ganti)
    location.href = '/admin.html';
  } else {
    msg.textContent = body?.error || `Server error (E${status})`;
  }
  btn.disabled = false;
});

})();
