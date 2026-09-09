async function agentInfo(){const r=await fetch('http://127.0.0.1:17890/info',{cache:'no-store'});if(!r.ok)throw new Error('Agente no disponible');return await r.json();}
async function api(action,data=null){
 const o=data?{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)}:{};
 const r=await fetch('api.php?action='+encodeURIComponent(action),o);return await r.json();
}
function setOnline(v){const e=document.getElementById('internetStatus');if(e){e.textContent=v?'Online':'Offline';e.className='badge p-2 '+(v?'bg-success':'bg-danger');}}
async function refreshHardware(){
 try{const h=await agentInfo();await api('actualizar_hardware',{hardware:h});document.querySelectorAll('[data-hw]').forEach(e=>{const k=e.dataset.hw;if(h[k]!=null)e.textContent=h[k];});setOnline(true);Swal.fire({icon:'success',title:'Hardware actualizado',timer:1200,showConfirmButton:false});}
 catch(e){setOnline(false);Swal.fire('Agente no disponible','Ejecutá ESG-Agent.ps1 en esta PC.','warning');}
}
document.addEventListener('DOMContentLoaded',()=>{
 const b=document.getElementById('btnRefreshHardware');if(b)b.onclick=refreshHardware;
 const f=document.getElementById('ticketForm');if(f)f.onsubmit=async ev=>{ev.preventDefault();try{const h=await agentInfo();const d=Object.fromEntries(new FormData(f).entries());d.hardware=h;const j=await api('crear_ticket',d);if(!j.ok)throw new Error(j.message);f.reset();Swal.fire('Enviado',j.message,'success');}catch(e){Swal.fire('Error',e.message,'error');}};
});
