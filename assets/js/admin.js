const fmtRp = n => 'Rp ' + Number(n||0).toLocaleString('id-ID');

async function loadStats(){
  const r = await fetch('backend/api/admin/get_stats.php');
  const j = await r.json();
  if(!j.ok) return;
  const d = j.data;
  document.getElementById('kpiVillas').textContent   = d.villas;
  document.getElementById('kpiOrders').textContent   = d.orders;
  document.getElementById('kpiPaid').textContent     = d.paid;
  document.getElementById('kpiRevenue').textContent  = fmtRp(d.revenue);
}
async function loadBookings(){
  const r = await fetch('backend/api/admin/list_bookings.php');
  const j = await r.json();
  if(!j.ok) return;
  const tb = document.querySelector('#tbl tbody');
  tb.innerHTML = '';
  for(const x of j.data){
    const tr = document.createElement('tr');
    const statusClass = x.status==='paid'?'paid':(x.status==='pending'?'pending':'failed');
    tr.innerHTML = `
      <td>${x.order_id}</td>
      <td>${x.nama_villa}</td>
      <td>${x.checkin}</td>
      <td>${x.checkout}</td>
      <td>${x.customer_name||''}</td>
      <td>${fmtRp(x.total_amount)}</td>
      <td><span class="pill ${x.status}">${x.status}</span></td>
    `;
    tb.appendChild(tr);
  }
}
loadStats();
loadBookings();
setInterval(()=>{ loadStats(); loadBookings(); }, 15000); // refresh otomatis
