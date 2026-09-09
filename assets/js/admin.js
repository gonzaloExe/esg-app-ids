async function get(a){const r=await fetch('api.php?action='+a);return r.json();}
async function post(a,d){const r=await fetch('api.php?action='+a,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(d)});return r.json();}
const badge=e=>e==='buena'?'🟢 Buena':e==='lenta'?'🟡 Lenta':e==='fallando'?'🔴 Fallando':'⚪ Sin estado';
async function loadTickets(){
 const j=await get('obtener_tickets');if(!j.ok)return;const b=document.getElementById('ticketsBody');if(!b)return;
 b.innerHTML=j.data.map(t=>`<tr><td>${t.id}</td><td>${t.fecha}</td><td>${t.pc_origen}</td><td>${t.departamento}</td><td>${esc(t.titulo)}</td><td>${badge(t.estado_pc)}</td><td>${t.estado}</td><td>${actions(t)}</td></tr>`).join('');
}
function actions(t){if(window.ESG.role!=='superadmin')return '-';let x='';if(t.estado==='pendiente')x+=`<button class="btn btn-success btn-sm me-1" onclick="approve(${t.id})">Aprobar</button><button class="btn btn-danger btn-sm" onclick="reject(${t.id})">Rechazar</button>`;if(t.estado==='aprobado')x+=`<button class="btn btn-primary btn-sm" onclick="resolveT(${t.id})">Resolver</button>`;x+=` <button class="btn btn-outline-danger btn-sm" onclick="deleteT(${t.id})">Eliminar</button>`;return x;}
const esc=s=>String(s??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
async function approve(id){await post('aprobar_ticket',{id});loadTickets();}
async function reject(id){const motivo=prompt('Motivo del rechazo:');if(!motivo)return;await post('rechazar_ticket',{id,motivo});loadTickets();}
async function resolveT(id){const comentarios=prompt('Comentario de resolución:')||'';await post('resolver_ticket',{id,comentarios});loadTickets();}
async function deleteT(id){if(confirm('¿Eliminar ticket?')){await post('eliminar_ticket',{id});loadTickets();}}
async function loadUsers(){
 if(window.ESG.role!=='superadmin')return;const j=await get('obtener_usuarios');if(!j.ok)return;const b=document.getElementById('usersBody');if(!b)return;
 b.innerHTML=j.data.map(u=>`<tr><td>${esc(u.nombre_usuario)}</td><td>${esc(u.nombre_usuario)}</td><td>${badge(u.estado_pc)}</td><td>${esc(u.departamento)}</td><td>${u.rol}</td><td>${u.activo?'Sí':'No'}</td><td><button class="btn btn-warning btn-sm" onclick="statePc('${esc(u.pc_identificador)}')">Estado</button> <button class="btn btn-secondary btn-sm" onclick="toggleUser('${esc(u.pc_identificador)}')">Activar/Desactivar</button></td></tr>`).join('');
}
async function statePc(pc){const e=prompt('Estado: buena, lenta o fallando');if(!['buena','lenta','fallando'].includes(e))return;const c=prompt('Comentario (opcional):')||'';await post('asignar_estado_pc',{pc,estado:e,comentario:c});loadUsers();}
async function toggleUser(pc){await post('desactivar_usuario',{pc});loadUsers();}
async function loadStats(){
 if(window.ESG.role!=='superadmin')return;const j=await get('obtener_estadisticas');if(!j.ok)return;
 let n={buena:0,lenta:0,fallando:0};j.data.pc_estados.forEach(x=>n[x.estado_pc]=+x.total);
 ['Buena','Lenta','Fallando'].forEach((x,i)=>{const el=document.getElementById('stat'+x);if(el)el.textContent=[n.buena,n.lenta,n.fallando][i];});
 const c=document.getElementById('criticalList');if(c)c.innerHTML=j.data.criticas.map(x=>`<div class="list-group-item">${esc(x.nombre_usuario)} — ${esc(x.departamento)}<br><small>${esc(x.estado_pc_comentario||'Sin comentario')}</small></div>`).join('');
 const cv=document.getElementById('pcChart');if(cv){if(window.pcChart)window.pcChart.destroy();window.pcChart=new Chart(cv,{type:'bar',data:{labels:['Buena','Lenta','Fallando'],datasets:[{label:'PCs',data:[n.buena,n.lenta,n.fallando]}]},options:{responsive:true}});}
}
document.addEventListener('DOMContentLoaded',()=>{loadTickets();loadUsers();loadStats();});
