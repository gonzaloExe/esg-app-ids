<?php
declare(strict_types=1);
require_once __DIR__.'/config.php';

function windowsIdentity(): string {
    $candidates = [
        $_SERVER['AUTH_USER'] ?? '',
        $_SERVER['REMOTE_USER'] ?? '',
        $_SERVER['LOGON_USER'] ?? '',
        $_SERVER['REDIRECT_REMOTE_USER'] ?? ''
    ];
    foreach ($candidates as $v) {
        $v=clean($v,100);
        if ($v!=='') return $v;
    }
    return '';
}

function clientIp(): string {
    return clean($_SERVER['REMOTE_ADDR'] ?? '',50);
}

function clientPcName(): string {
    $ip=clientIp();
    $host=clean($_SERVER['REMOTE_HOST'] ?? '',100);
    if ($host!=='' && strcasecmp($host,'localhost')!==0) return strtoupper($host);
    if ($ip!=='' && filter_var($ip,FILTER_VALIDATE_IP)) {
        $resolved=@gethostbyaddr($ip);
        if ($resolved && $resolved!==$ip) return strtoupper(clean($resolved,100));
    }
    return $ip!=='' ? 'PC-'.$ip : 'PC-DESCONOCIDA';
}

function domainUserShort(string $identity): string {
    if (strpos($identity,'\\')!==false) $identity=substr(strrchr($identity,'\\'),1);
    if (strpos($identity,'@')!==false) $identity=substr($identity,0,strpos($identity,'@'));
    return clean($identity,100);
}

function currentIdentity(): array {
    $identity=windowsIdentity();
    return [
        'identity'=>$identity,
        'username'=>domainUserShort($identity),
        'pc'=>$identity!=='' ? clientPcName() : '',
        'ip'=>clientIp()
    ];
}

function agentId(): string { return clientPcName(); }
function currentPc(): string { return agentId(); }

function ensureCurrentPc(): ?array {
    $id=agentId(); $identity=currentIdentity();
    if ($id==='' || $identity['identity']==='') return null;
    $st=db()->prepare('SELECT * FROM usuarios WHERE pc_identificador=? LIMIT 1');
    $st->execute([$id]); $u=$st->fetch();
    if (!$u) {
        $dep='General';
        $st=db()->prepare("SELECT valor FROM configuracion WHERE clave='departamento_por_defecto'");
        $st->execute(); $dep=clean($st->fetchColumn() ?: 'General',100);
        db()->prepare("INSERT INTO usuarios (pc_identificador,nombre_usuario,rol,departamento,activo,estado_pc,ip_local,version_php,fecha_ultima_conexion) VALUES (?,?,'usuario',?,1,'buena',?,'AD',?)")
          ->execute([$id,$identity['username'],$dep,$identity['ip'],now()]);
        $st=db()->prepare('SELECT * FROM usuarios WHERE pc_identificador=? LIMIT 1'); $st->execute([$id]); $u=$st->fetch();
    } else {
        if (!$u['activo']) return null;
        db()->prepare('UPDATE usuarios SET nombre_usuario=?,ip_local=?,fecha_ultima_conexion=? WHERE pc_identificador=?')
          ->execute([$identity['username'],$identity['ip'],now(),$id]);
        $u['nombre_usuario']=$identity['username']; $u['ip_local']=$identity['ip']; $u['fecha_ultima_conexion']=now();
    }
    return $u ?: null;
}

function currentUserRow(): ?array { return ensureCurrentPc(); }
function isSuperAdmin(): bool { $u=currentUserRow(); return $u && $u['rol']==='superadmin'; }
function requireSuperAdmin(): void { if (!isSuperAdmin()) jsonResponse(false,null,'Acceso reservado al SuperAdmin.',403); }
function requireInstalledPage(): void {
    $st=db()->prepare("SELECT valor FROM configuracion WHERE clave='instalado'"); $st->execute();
    if ($st->fetchColumn()!=='true') { header('Location: instalar.php'); exit; }
}
function requireInstalledApi(): void {
    $st=db()->prepare("SELECT valor FROM configuracion WHERE clave='instalado'"); $st->execute();
    if ($st->fetchColumn()!=='true') jsonResponse(false,null,'Sistema no instalado.',503);
}
function touchCurrentUser(): void {
    $id=agentId(); if($id==='') return;
    db()->prepare('UPDATE usuarios SET fecha_ultima_conexion=? WHERE pc_identificador=?')->execute([now(),$id]);
}
