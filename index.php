<?php
declare(strict_types=1);
require_once __DIR__.'/includes/config.php';
require_once __DIR__.'/includes/hardware.php';
require_once __DIR__.'/includes/auth.php';
requireInstalledPage();
$u=ensureCurrentPc();
if(!$u):
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>ESG · Autenticación Windows</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light"><main class="container py-5"><div class="card shadow-sm mx-auto" style="max-width:650px"><div class="card-body p-4"><h1 class="h3">ESG · Acceso Windows</h1><div class="alert alert-danger">No se pudo identificar automáticamente tu cuenta de dominio o esta PC está desactivada.</div><p class="text-muted">El servidor debe tener habilitada la autenticación integrada de Windows (Kerberos/NTLM).</p></div></div></main></body></html>
<?php exit; endif; ?>
<!doctype html><html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>ESG · Entorno Seguro y Gestión</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<link href="assets/css/estilo.css" rel="stylesheet"></head><body>
<header class="navbar navbar-dark esg-header sticky-top"><div class="container-fluid">
<a class="navbar-brand fw-bold" href="index.php">🛡 ESG</a>
<div class="d-flex align-items-center gap-2"><span id="internetStatus" class="badge bg-success p-2">Online</span><a class="btn btn-light btn-lg" href="admin.php">Ir al Panel</a></div>
</div></header><main class="container py-4">
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4"><div><h1 class="h3">Equipo actual</h1><p class="text-muted"><?=htmlspecialchars($u['rol'])?></p></div>
<button id="btnRefreshHardware" class="btn btn-outline-primary btn-lg">Actualizar hardware</button></div>
<div id="pcCard" class="card shadow-sm status-border-<?=htmlspecialchars($u['estado_pc'])?> mb-4"><div class="card-body">
<div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h4">🖥️ <?=htmlspecialchars($u['nombre_usuario'])?></h2><span id="pcStatus" class="status-badge status-<?=htmlspecialchars($u['estado_pc'])?>"><?=getEstadoBadge($u['estado_pc'])?></span></div>
<div class="row g-3"><?php
$items=['nombre_usuario'=>'PC / Usuario','sistema_operativo'=>'Sistema Operativo','arquitectura'=>'Arquitectura','procesador'=>'Procesador','ram_total_gb'=>'RAM Total (GB)','ram_disponible_gb'=>'RAM Disponible (GB)','placa_manufacturer'=>'Placa Base','placa_product'=>'Modelo Placa','disco_modelo'=>'Disco Duro','disco_tamano_gb'=>'Disco Tamaño (GB)','disco_c_total_gb'=>'C: Total (GB)','disco_c_libre_gb'=>'C: Libre (GB)','disco_c_porcentaje_libre'=>'C: Libre (%)','mac_address'=>'MAC','ip_local'=>'IP Local','version_php'=>'Agente','departamento'=>'Departamento','rol'=>'Rol'];
foreach($items as $k=>$label): ?><div class="col-sm-6 col-lg-4"><div class="info-tile"><span><?=htmlspecialchars($label)?></span><strong data-hw="<?=htmlspecialchars($k)?>"><?=htmlspecialchars((string)($u[$k]??'No detectado'))?></strong></div></div><?php endforeach; ?>
<div class="col-12"><div class="info-tile"><span>Estado de la PC</span><strong id="statusText"><?=getEstadoBadge($u['estado_pc'])?></strong><?php if($u['estado_pc_comentario']): ?><small class="text-muted d-block mt-1"><?=htmlspecialchars($u['estado_pc_comentario'])?></small><?php endif; ?></div></div>
</div></div></div>
<?php if($u['rol']!=='superadmin'): ?><section class="card shadow-sm"><div class="card-body"><h2 class="h4">Nuevo ticket</h2>
<form id="ticketForm" class="row g-3"><div class="col-12"><input name="titulo" class="form-control form-control-lg" placeholder="Título" required></div><div class="col-12"><textarea name="descripcion" class="form-control" rows="5" placeholder="Descripción" required></textarea></div><div class="col-12"><button class="btn btn-primary btn-lg">Enviar ticket</button></div></form></div></section>
<?php else: ?><div class="alert alert-info">👑 Esta PC es el SuperAdmin. No puede crear tickets.</div><?php endif; ?>
</main><script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script><script>window.ESG={role:<?=json_encode($u['rol'])?>,pc:<?=json_encode($u['pc_identificador'])?>};</script><script src="assets/js/app.js"></script></body></html>