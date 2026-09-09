<?php
declare(strict_types=1);
require_once __DIR__.'/includes/config.php';
require_once __DIR__.'/includes/hardware.php';
require_once __DIR__.'/includes/auth.php';
requireInstalledPage(); $u=ensureCurrentPc();
if(!$u) { http_response_code(403); exit('PC no registrada.'); }
?>
<!doctype html>
<html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>ESG · Panel</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<link href="assets/css/estilo.css" rel="stylesheet">
</head><body>
<header class="navbar navbar-dark esg-header sticky-top"><div class="container-fluid">
<a class="navbar-brand fw-bold" href="index.php">ESG · Panel</a>
<div class="d-flex gap-2 align-items-center"><span id="internetStatus" class="badge bg-success p-2">Online</span><a href="index.php" class="btn btn-light btn-lg">Inicio</a></div>
</div></header>
<main class="container-fluid py-4">
<div class="mb-4"><h1 class="h3 mb-1">Panel de gestión</h1><p class="text-muted"><?=htmlspecialchars($u['nombre_usuario'])?> · <?=htmlspecialchars($u['rol'])?> · <?=htmlspecialchars($u['departamento']??'')?></p></div>

<?php if($u['rol']==='superadmin'): ?>
<section id="dashboard" class="mb-4">
<div class="row g-3">
<div class="col-md-3"><div class="stat-card"><span>🟢 Buenas</span><strong id="statBuena">0</strong></div></div>
<div class="col-md-3"><div class="stat-card"><span>🟡 Lentas</span><strong id="statLenta">0</strong></div></div>
<div class="col-md-3"><div class="stat-card"><span>🔴 Fallando</span><strong id="statFallando">0</strong></div></div>
<div class="col-md-3"><div class="stat-card"><span>Tickets pendientes</span><strong id="statPendientes">0</strong></div></div>
</div>
<div class="row g-3 mt-1"><div class="col-lg-7"><div class="card"><div class="card-body"><h2 class="h5">PCs por estado</h2><canvas id="pcChart" height="130"></canvas></div></div></div>
<div class="col-lg-5"><div class="card"><div class="card-body"><h2 class="h5">PCs críticas 🔴</h2><div id="criticalList" class="list-group list-group-flush"></div></div></div></div></div>
</section>
<section class="card shadow-sm mb-4"><div class="card-body"><div class="d-flex justify-content-between"><h2 class="h4">Usuarios / PCs</h2><button class="btn btn-primary btn-lg" onclick="openDeptModal()">Nuevo departamento</button></div>
<div class="table-responsive mt-3"><table class="table align-middle"><thead><tr><th>PC</th><th>Usuario</th><th>Estado</th><th>Departamento</th><th>Rol</th><th>Activo</th><th>Acciones</th></tr></thead><tbody id="usersBody"></tbody></table></div></div></section>
<section class="card shadow-sm mb-4"><div class="card-body"><h2 class="h4">Departamentos</h2><div class="table-responsive"><table class="table"><thead><tr><th>Nombre</th><th>Descripción</th><th>Encargado</th><th>Acciones</th></tr></thead><tbody id="deptsBody"></tbody></table></div></div></section>
<?php endif; ?>

<section class="card shadow-sm"><div class="card-body"><div class="d-flex justify-content-between align-items-center"><h2 class="h4">Tickets</h2><button class="btn btn-outline-primary btn-lg" onclick="loadTickets()">Actualizar</button></div>
<div class="table-responsive mt-3"><table class="table align-middle"><thead><tr><th>ID</th><th>Fecha</th><th>PC</th><th>Departamento</th><th>Título</th><th>Estado PC</th><th>Ticket</th><th>Acciones</th></tr></thead><tbody id="ticketsBody"></tbody></table></div></div></section>
</main>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>window.ESG={role:<?=json_encode($u['rol'])?>,pc:<?=json_encode($u['pc_identificador'])?>,dept:<?=json_encode($u['departamento'])?>};</script>
<script src="assets/js/admin.js"></script>
</body></html>
