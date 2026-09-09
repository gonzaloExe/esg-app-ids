<?php
declare(strict_types=1);

function localAgentInfo(): ?array {
    // El navegador obtiene estos datos del agente PowerShell local.
    return null;
}

/* Fallback: estos datos son del servidor y solo se usan si no llega
   información del agente. No se presentan como hardware del cliente. */
function obtenerHardwarePC(): array {
    return [
        'pc_nombre'=>'No detectado',
        'usuario_windows'=>'No detectado',
        'sistema_operativo'=>PHP_OS,
        'arquitectura'=>php_uname('m'),
        'procesador'=>'No detectado',
        'version_php'=>PHP_VERSION,
        'ip_local'=>$_SERVER['REMOTE_ADDR'] ?? 'No detectada',
        'ram_total_gb'=>null,'ram_disponible_gb'=>null,
        'placa_manufacturer'=>'No detectado','placa_product'=>'No detectado',
        'disco_modelo'=>'No detectado','disco_tamano_gb'=>null,
        'disco_c_total_gb'=>null,'disco_c_libre_gb'=>null,'disco_c_porcentaje_libre'=>null,
        'mac_address'=>'No detectada','cpu_nombre'=>'No detectado',
        'cpu_cores'=>null,'cpu_logical'=>null,'fecha_deteccion'=>date('Y-m-d H:i:s')
    ];
}
function getEstadoColor($estado): string {
    return ['buena'=>'success','lenta'=>'warning','fallando'=>'danger'][$estado] ?? 'secondary';
}
function getEstadoBadge($estado): string {
    return ['buena'=>'🟢 Buena','lenta'=>'🟡 Lenta','fallando'=>'🔴 Fallando'][$estado] ?? '⚪ Sin estado';
}
