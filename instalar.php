<?php
declare(strict_types=1);
require_once __DIR__.'/includes/config.php';
require_once __DIR__.'/includes/auth.php';
$st=db()->prepare("SELECT valor FROM configuracion WHERE clave='instalado'"); $st->execute();
if($st->fetchColumn()==='true'){ header('Location:index.php'); exit; }
$identity=currentIdentity();
if($identity['identity']!=='') ensureCurrentPc();
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>ESG · Instalación AD</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light"><main class="container py-5"><div class="card shadow-sm mx-auto" style="max-width:650px"><div class="card-body p-4">
<h1 class="h3">ESG · Configuración inicial</h1>
<?php if($identity['identity']===''): ?>
<div class="alert alert-danger">No se recibió la identidad de Windows. Configurá autenticación integrada de Windows (Kerberos/NTLM) en IIS o Apache antes de continuar.</div>
<?php else: ?>
<p>Esta PC será el <strong>SuperAdmin</strong> inicial.</p>
<ul><li>Usuario AD: <strong><?=htmlspecialchars($identity['identity'])?></strong></li><li>PC: <strong><?=htmlspecialchars($identity['pc'])?></strong></li><li>IP: <strong><?=htmlspecialchars($identity['ip'])?></strong></li></ul>
<form method="post"><button class="btn btn-primary btn-lg">Convertir esta cuenta en SuperAdmin</button></form>
<?php if($_SERVER['REQUEST_METHOD']==='POST'):
    $u=ensureCurrentPc();
    if($u){db()->prepare("UPDATE usuarios SET rol='superadmin',activo=1 WHERE pc_identificador=?")->execute([$u['pc_identificador']]);db()->prepare("INSERT INTO configuracion(clave,valor) VALUES('instalado','true') ON DUPLICATE KEY UPDATE valor='true'")->execute();header('Location:index.php');exit;}
endif; ?>
<?php endif; ?><hr><p class="text-muted small mb-0">Después de la instalación, las nuevas PCs se registran automáticamente como Usuario. El cambio de rol queda exclusivamente en el panel SuperAdmin.</p>
</div></div></main></body></html>
