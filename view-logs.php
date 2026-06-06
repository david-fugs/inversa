<?php
/**
 * Visor de Logs - Diagnóstico de errores
 * Acceso: /inversa/view-logs.php
 */

// Verificar acceso (solo en desarrollo, remover en producción)
if ($_SERVER['HTTP_HOST'] !== 'localhost' && $_SERVER['HTTP_HOST'] !== '127.0.0.1') {
    die('Access denied. Este archivo solo está disponible en desarrollo.');
}

$logFile = __DIR__ . '/logs/flight_services.log';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visor de Logs - Flight Services</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; padding: 20px; }
        .log-container { background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 20px; }
        .log-content { background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 5px; font-family: 'Courier New', monospace; font-size: 13px; max-height: 600px; overflow-y: auto; white-space: pre-wrap; word-wrap: break-word; }
        .error { color: #f48771; }
        .success { color: #6a9955; }
        .warning { color: #dcdcaa; }
        .info { color: #569cd6; }
        .btn-group-custom { margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="log-container">
            <h2 class="mb-4"><i class="bi bi-file-text"></i> Visor de Logs - Flight Services</h2>
            
            <div class="btn-group-custom">
                <a href="?clear=1" class="btn btn-danger btn-sm" onclick="return confirm('¿Limpiar todos los logs?');">
                    <i class="bi bi-trash"></i> Limpiar logs
                </a>
                <a href="?" class="btn btn-primary btn-sm">
                    <i class="bi bi-arrow-clockwise"></i> Refrescar
                </a>
            </div>

            <?php if (isset($_GET['clear']) && $_GET['clear'] == 1): ?>
                <?php 
                    if (file_exists($logFile)) {
                        file_put_contents($logFile, '');
                        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            Logs limpios correctamente.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>';
                    }
                ?>
            <?php endif; ?>

            <div class="log-content">
                <?php 
                    if (!file_exists($logFile)) {
                        echo '<span class="info">No hay logs registrados aún. Los errores aparecerán aquí.</span>';
                    } else {
                        $content = file_get_contents($logFile);
                        
                        if (empty(trim($content))) {
                            echo '<span class="info">Archivo de logs vacío.</span>';
                        } else {
                            // Colorear por tipo
                            $content = htmlspecialchars($content);
                            $content = preg_replace('/\| ERROR \|/', '| <span class="error">ERROR</span> |', $content);
                            $content = preg_replace('/\| UPDATE ERROR \|/', '| <span class="error">UPDATE ERROR</span> |', $content);
                            $content = preg_replace('/\| STORE ERROR \|/', '| <span class="error">STORE ERROR</span> |', $content);
                            $content = preg_replace('/\| WARNING \|/', '| <span class="warning">WARNING</span> |', $content);
                            $content = preg_replace('/\| SUCCESS \|/', '| <span class="success">SUCCESS</span> |', $content);
                            $content = preg_replace('/\| INFO \|/', '| <span class="info">INFO</span> |', $content);
                            
                            echo $content;
                        }
                    }
                ?>
            </div>

            <div class="mt-3">
                <small class="text-muted">
                    📁 Ubicación: <code><?= $logFile ?></code>
                </small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
