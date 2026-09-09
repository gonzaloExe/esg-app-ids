<?php
declare(strict_types=1);
require_once __DIR__.'/includes/config.php';
header('Content-Type: text/html; charset=utf-8');
try{
 $pdo=db(); $tables=$pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
 echo '<h1>ESG · Verificación DB</h1><p>Conexión OK.</p><ul>';
 foreach($tables as $t) echo '<li>'.htmlspecialchars($t).'</li>';
 echo '</ul>';
}catch(Throwable $e){http_response_code(500);echo '<h1>Error</h1><pre>'.htmlspecialchars($e->getMessage()).'</pre>';}
?>
