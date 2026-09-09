# Configuración de ESG con Active Directory

## IIS + PHP

En el sitio de IIS:

- Windows Authentication: **Enabled**
- Anonymous Authentication: **Disabled**

En una red de dominio, IIS negociará Kerberos/NTLM y PHP podrá recibir la identidad mediante variables del servidor. Comprobá con `verificar_db.php` o un phpinfo temporal que `REMOTE_USER`/`AUTH_USER` llegue con el usuario del dominio.

## Apache

Apache necesita un módulo de autenticación integrada (por ejemplo, SSPI/Negotiate). La configuración concreta depende de la versión de Apache y del módulo disponible. El objetivo es que la petición autenticada llegue a PHP con:

`REMOTE_USER=DOMINIO\\usuario`

No uses `get_current_user()` para esto: esa función no devuelve el usuario de Windows que abrió el navegador.

## DNS del nombre de PC

ESG recibe la IP cliente mediante `REMOTE_ADDR` y ejecuta resolución DNS inversa. Para obtener `PC-001` en vez de `PC-10.24.x.x`, el DNS interno debe tener registros PTR correctos.

## Prueba rápida

Desde una PC del dominio:

1. Abrí ESG.
2. Entrá a `instalar.php` durante la primera configuración.
3. Verificá que aparezcan usuario de dominio, PC e IP.
4. Confirmá la cuenta SuperAdmin.
5. Desde otra PC del dominio, abrí ESG y verificá que aparezca automáticamente como `usuario`.
