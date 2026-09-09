<?php
declare(strict_types=1);
require_once __DIR__.'/includes/config.php';
require_once __DIR__.'/includes/hardware.php';
require_once __DIR__.'/includes/auth.php';

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$method = $_SERVER['REQUEST_METHOD'];

function requestJson(): array {
    $raw=file_get_contents('php://input');
    $j=json_decode($raw ?: '{}', true);
    return is_array($j) ? $j : [];
}
function applyHardware(string $id, array $h): void {
    if ($id==='') return;
    db()->prepare("UPDATE usuarios SET
        nombre_usuario=?, sistema_operativo=?, arquitectura=?, procesador=?, cpu_nombre=?,
        cpu_cores=?, cpu_logical=?, ram_total_gb=?, ram_disponible_gb=?,
        placa_manufacturer=?, placa_product=?, disco_modelo=?, disco_tamano_gb=?,
        disco_c_total_gb=?, disco_c_libre_gb=?, disco_c_porcentaje_libre=?,
        mac_address=?, ip_local=?, version_php=?, ultima_deteccion_hardware=?, fecha_ultima_conexion=?
        WHERE pc_identificador=?")
      ->execute([
        clean($h['usuario_windows']??'No detectado',100),
        clean($h['sistema_operativo']??'No detectado',100),
        clean($h['arquitectura']??'No detectado',50),
        clean($h['procesador']??'No detectado',200),
        clean($h['cpu_nombre']??'No detectado',200),
        $h['cpu_cores']??null,$h['cpu_logical']??null,$h['ram_total_gb']??null,$h['ram_disponible_gb']??null,
        clean($h['placa_manufacturer']??'No detectado',100),
        clean($h['placa_product']??'No detectado',100),
        clean($h['disco_modelo']??'No detectado',200),
        $h['disco_tamano_gb']??null,$h['disco_c_total_gb']??null,$h['disco_c_libre_gb']??null,
        $h['disco_c_porcentaje_libre']??null,clean($h['mac_address']??'No detectada',50),
        clean($h['ip_local']??'No detectada',50),clean($h['version_php']??'Agente',20),
        now(),now(),$id
      ]);
}

try {
    if ($action==='identidad' && $method==='GET') {
        $i=currentIdentity();
        if ($i['identity']==='') jsonResponse(false,null,'Autenticación Windows no disponible.',401);
        $u=ensureCurrentPc();
        jsonResponse(true,['identity'=>$i,'usuario'=>$u]);
    }

    requireInstalledApi();

    if ($method === 'GET') {
        switch ($action) {
            case 'obtener_hardware':
                $u=currentUserRow(); if(!$u) jsonResponse(false,null,'PC no registrada.',401);
                jsonResponse(true,$u);
            case 'obtener_usuarios':
                requireSuperAdmin();
                jsonResponse(true,db()->query("SELECT * FROM usuarios ORDER BY estado_pc='fallando' DESC, pc_identificador")->fetchAll());
            case 'obtener_departamentos':
                $rows=db()->query("SELECT d.*, u.nombre_usuario AS encargado_nombre FROM departamentos d LEFT JOIN usuarios u ON u.pc_identificador=d.encargado_pc ORDER BY d.nombre")->fetchAll();
                if(!isSuperAdmin()){ $u=currentUserRow(); $rows=array_values(array_filter($rows,fn($d)=>$d['nombre']===($u['departamento']??''))); }
                jsonResponse(true,$rows);
            case 'obtener_tickets':
                $u=currentUserRow(); if(!$u) jsonResponse(false,null,'PC no registrada.',401);
                if($u['rol']==='superadmin'){$where='1=1';$params=[];}
                elseif($u['rol']==='encargado_departamento'){$where='t.departamento=?';$params=[$u['departamento']];}
                else{$where='t.pc_origen=?';$params=[$u['pc_identificador']];}
                $st=db()->prepare("SELECT t.*,u.estado_pc,u.estado_pc_comentario FROM tickets t LEFT JOIN usuarios u ON u.pc_identificador=t.pc_origen WHERE $where ORDER BY t.fecha DESC");
                $st->execute($params); jsonResponse(true,$st->fetchAll());
            case 'obtener_estadisticas':
                requireSuperAdmin();
                $stats=db()->query("SELECT estado_pc,COUNT(*) total FROM usuarios GROUP BY estado_pc")->fetchAll();
                $tickets=db()->query("SELECT estado,COUNT(*) total FROM tickets GROUP BY estado")->fetchAll();
                $critical=db()->query("SELECT pc_identificador,nombre_usuario,departamento,estado_pc,estado_pc_comentario FROM usuarios WHERE estado_pc='fallando' ORDER BY pc_identificador")->fetchAll();
                jsonResponse(true,['pc_estados'=>$stats,'tickets_estados'=>$tickets,'criticas'=>$critical]);
            default: jsonResponse(false,null,'Acción GET no reconocida.',400);
        }
    }

    if($method!=='POST') jsonResponse(false,null,'Método no permitido.',405);
    $data=requestJson();

    switch($action){
        case 'crear_ticket':
            $u=currentUserRow(); if(!$u||$u['rol']==='superadmin') jsonResponse(false,null,'El SuperAdmin no puede crear tickets.',403);
            $titulo=clean($data['titulo']??'',255); $descripcion=clean($data['descripcion']??'',10000);
            if($titulo===''||$descripcion==='') jsonResponse(false,null,'Título y descripción son obligatorios.',422);
            $hardware=$data['hardware']??[];
            $st=db()->prepare("INSERT INTO tickets(titulo,descripcion,pc_origen,usuario_origen,departamento,estado_pc_origen,hardware_snapshot) VALUES(?,?,?,?,?,?,?)");
            $st->execute([$titulo,$descripcion,$u['pc_identificador'],$u['nombre_usuario'],$u['departamento']?:'General',$u['estado_pc'],json_encode($hardware,JSON_UNESCAPED_UNICODE)]);
            jsonResponse(true,['id'=>db()->lastInsertId()],'Ticket creado y enviado a revisión.');
        case 'aprobar_ticket':
            requireSuperAdmin(); $id=(int)($data['id']??0);
            $st=db()->prepare("UPDATE tickets SET estado='aprobado',aprobado_por=?,fecha_aprobacion=? WHERE id=? AND estado='pendiente'");
            $st->execute([currentPc(),now(),$id]); jsonResponse($st->rowCount()>0,null,$st->rowCount()>0?'Ticket aprobado.':'No se pudo aprobar.', $st->rowCount()>0?200:409);
        case 'rechazar_ticket':
            requireSuperAdmin(); $id=(int)($data['id']??0); $motivo=clean($data['motivo']??'',5000);
            if($motivo==='') jsonResponse(false,null,'El motivo es obligatorio.',422);
            $st=db()->prepare("UPDATE tickets SET estado='rechazado',rechazado_por=?,fecha_rechazo=?,motivo_rechazo=? WHERE id=? AND estado='pendiente'");
            $st->execute([currentPc(),now(),$motivo,$id]); jsonResponse($st->rowCount()>0,null,$st->rowCount()>0?'Ticket rechazado.':'No se pudo rechazar.', $st->rowCount()>0?200:409);
        case 'resolver_ticket':
            requireSuperAdmin(); $id=(int)($data['id']??0); $c=clean($data['comentarios']??'',5000);
            $st=db()->prepare("UPDATE tickets SET estado='resuelto',resuelto_por=?,fecha_resolucion=?,comentarios_resolucion=? WHERE id=? AND estado='aprobado'");
            $st->execute([currentPc(),now(),$c,$id]); jsonResponse($st->rowCount()>0,null,$st->rowCount()>0?'Ticket resuelto.':'Solo se pueden resolver tickets aprobados.', $st->rowCount()>0?200:409);
        case 'eliminar_ticket':
            requireSuperAdmin(); $id=(int)($data['id']??0); db()->prepare("DELETE FROM tickets WHERE id=?")->execute([$id]); jsonResponse(true,null,'Ticket eliminado.');
        case 'asignar_estado_pc':
            requireSuperAdmin(); $pc=clean($data['pc']??'',100); $estado=$data['estado']??''; $coment=clean($data['comentario']??'',5000);
            if(!in_array($estado,['buena','lenta','fallando'],true)) jsonResponse(false,null,'Estado inválido.',422);
            db()->prepare("UPDATE usuarios SET estado_pc=?,estado_pc_fecha_asignacion=?,estado_pc_asignado_por=?,estado_pc_comentario=? WHERE pc_identificador=?")->execute([$estado,now(),currentPc(),$coment,$pc]);
            jsonResponse(true,null,'Estado de PC actualizado.');
        case 'asignar_encargado':
            requireSuperAdmin(); $pc=clean($data['pc']??'',100); $dep=clean($data['departamento']??'',100);
            if($dep==='') jsonResponse(false,null,'Departamento obligatorio.',422);
            db()->beginTransaction();
            db()->prepare("UPDATE usuarios SET rol=CASE WHEN pc_identificador=? THEN 'encargado_departamento' ELSE rol END, departamento=CASE WHEN pc_identificador=? THEN ? ELSE departamento END")->execute([$pc,$pc,$dep]);
            db()->prepare("UPDATE departamentos SET encargado_pc=NULL WHERE nombre=?")->execute([$dep]);
            db()->prepare("UPDATE departamentos SET encargado_pc=? WHERE nombre=?")->execute([$pc,$dep]); db()->commit();
            jsonResponse(true,null,'Encargado asignado.');
        case 'quitar_encargado':
            requireSuperAdmin(); $pc=clean($data['pc']??'',100); db()->prepare("UPDATE departamentos SET encargado_pc=NULL WHERE encargado_pc=?")->execute([$pc]); db()->prepare("UPDATE usuarios SET rol='usuario' WHERE pc_identificador=? AND rol='encargado_departamento'")->execute([$pc]); jsonResponse(true,null,'Rol retirado.');
        case 'desactivar_usuario':
            requireSuperAdmin(); $pc=clean($data['pc']??'',100); if($pc===currentPc()) jsonResponse(false,null,'No puedes desactivar el SuperAdmin.',422); db()->prepare("UPDATE usuarios SET activo=NOT activo WHERE pc_identificador=? AND rol<>'superadmin'")->execute([$pc]); jsonResponse(true,null,'Estado actualizado.');
        case 'crear_departamento':
            requireSuperAdmin(); $n=clean($data['nombre']??'',100);$d=clean($data['descripcion']??'',1000);if($n==='')jsonResponse(false,null,'Nombre obligatorio.',422);db()->prepare("INSERT INTO departamentos(nombre,descripcion) VALUES(?,?)")->execute([$n,$d]);jsonResponse(true,null,'Departamento creado.');
        case 'editar_departamento':
            requireSuperAdmin(); $id=(int)($data['id']??0);$n=clean($data['nombre']??'',100);$d=clean($data['descripcion']??'',1000);db()->prepare("UPDATE departamentos SET nombre=?,descripcion=? WHERE id=?")->execute([$n,$d,$id]);jsonResponse(true,null,'Departamento actualizado.');
        case 'eliminar_departamento':
            requireSuperAdmin();$id=(int)($data['id']??0);$st=db()->prepare("SELECT nombre FROM departamentos WHERE id=?");$st->execute([$id]);$n=$st->fetchColumn();if($n)db()->prepare("UPDATE usuarios SET departamento='General' WHERE departamento=?")->execute([$n]);db()->prepare("DELETE FROM departamentos WHERE id=?")->execute([$id]);jsonResponse(true,null,'Departamento eliminado.');
        case 'actualizar_hardware':
            $u=currentUserRow();if(!$u)jsonResponse(false,null,'PC no registrada.',401);$h=$data['hardware']??[];applyHardware($u['pc_identificador'],$h);jsonResponse(true,$h,'Hardware actualizado.');
        default: jsonResponse(false,null,'Acción POST no reconocida.',400);
    }
} catch(PDOException $e){ jsonResponse(false,null,'Error de base de datos: '.$e->getCode(),500); }
  catch(Throwable $e){ jsonResponse(false,null,'Error interno del sistema.',500); }
?>
