# ESG - Entorno Seguro y Gestión (modo Active Directory)

Esta versión está preparada para redes Windows donde las PCs pertenecen a un dominio Active Directory.

## Qué cambia

- No requiere PowerShell ni agente local para identificar al usuario.
- La identidad se obtiene mediante autenticación integrada de Windows (`REMOTE_USER`, `AUTH_USER` o `LOGON_USER`).
- La PC se identifica por nombre DNS inverso a partir de la IP cliente. Si el DNS inverso no está disponible, se usa `PC-<IP>`.
- Una PC nueva se registra automáticamente como `usuario` y departamento `General`.
- El SuperAdmin puede cambiar después el rol y el departamento desde el panel.
- El primer usuario que realice la instalación inicial queda como SuperAdmin.
- La información completa de hardware sigue requiriendo un agente local; el navegador por sí solo no puede leer motherboard, disco, MAC, etc.

## Requisito del servidor web

### IIS (recomendado en Windows Server)

1. Habilitar Windows Authentication.
2. Deshabilitar Anonymous Authentication para el sitio ESG.
3. Asegurar que PHP se ejecute mediante FastCGI.
4. Activar DNS correcto para que la IP de cada cliente tenga resolución inversa si se desea mostrar el nombre exacto de la PC.

### Apache

Se necesita un módulo de autenticación integrada compatible con Windows, por ejemplo SSPI/Negotiate, y el sitio debe exponer la identidad autenticada en `REMOTE_USER` o una variable equivalente. La configuración exacta depende del módulo instalado.

## Primera instalación

Abrí `instalar.php` desde una PC del dominio usando la cuenta que quieras convertir en SuperAdmin. ESG mostrará la cuenta y PC detectadas. Al confirmar, esa cuenta queda como `superadmin`.

Después, cualquier PC del dominio que entre a `index.php` será creada automáticamente como `usuario`.

## Importante: nombre de PC

PHP no puede consultar `gethostname()` para saber el hostname de la PC cliente: esa función siempre consulta el equipo donde corre PHP. ESG intenta resolver la IP cliente mediante DNS inverso (`gethostbyaddr`). Para nombres exactos, configurá correctamente el DNS del dominio.

## Hardware automático

Si posteriormente querés CPU, RAM, motherboard, discos, MAC y demás datos, todavía hace falta un agente Windows o una herramienta de administración central (GPO/Intune/SCCM/PDQ). Esta versión no instala nada en las PCs y no pretende falsear esos datos con información del servidor.
# esg-app-ids
