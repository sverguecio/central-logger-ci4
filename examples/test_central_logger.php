<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Central Logger CI4</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        
        .header {
            background: #2d3748;
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .header h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        
        .header p {
            opacity: 0.8;
            font-size: 0.9rem;
        }
        
        .section {
            padding: 2rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .section:last-child {
            border-bottom: none;
        }
        
        .section h2 {
            color: #2d3748;
            font-size: 1.3rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge.success {
            background: #10b981;
            color: white;
        }
        
        .badge.error {
            background: #ef4444;
            color: white;
        }
        
        .config-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .config-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .config-table td:first-child {
            font-weight: 600;
            color: #4a5568;
            width: 40%;
        }
        
        .config-table td:last-child {
            color: #1a202c;
            font-family: 'Courier New', monospace;
        }
        
        .log-item {
            background: #f7fafc;
            border-left: 4px solid #cbd5e0;
            padding: 1rem;
            margin-bottom: 0.75rem;
            border-radius: 4px;
        }
        
        .log-item.critical {
            border-left-color: #dc2626;
            background: #fef2f2;
        }
        
        .log-item.alert {
            border-left-color: #ea580c;
            background: #fff7ed;
        }
        
        .log-item.emergency {
            border-left-color: #991b1b;
            background: #fef2f2;
        }
        
        .log-level {
            display: inline-block;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            margin-right: 0.5rem;
        }
        
        .handler-box {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 6px;
        }
        
        .handler-box h3 {
            color: #2d3748;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            font-family: 'Courier New', monospace;
        }
        
        .handler-handles {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .handler-handle {
            background: white;
            border: 1px solid #cbd5e0;
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            font-size: 0.75rem;
            color: #4a5568;
        }
        
        .alert-box {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }
        
        .alert-box p {
            color: #78350f;
            font-size: 0.9rem;
            line-height: 1.6;
        }
        
        .alert-box strong {
            display: block;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Test Central Logger CI4</h1>
            <p>Verificación de configuración e integración</p>
        </div>

        <!-- Configuración -->
        <div class="section">
            <h2>
                ⚙️ Configuración
                <?php if ($config['is_valid'] === '✅ Válida'): ?>
                    <span class="badge success">VÁLIDA</span>
                <?php else: ?>
                    <span class="badge error">INVÁLIDA</span>
                <?php endif; ?>
            </h2>
            
            <table class="config-table">
                <tr>
                    <td>API URL</td>
                    <td><?= esc($config['api_url']) ?></td>
                </tr>
                <tr>
                    <td>API Key</td>
                    <td><?= esc($config['api_key']) ?></td>
                </tr>
                <tr>
                    <td>App Name</td>
                    <td><?= esc($config['app_name']) ?></td>
                </tr>
                <tr>
                    <td>Environment</td>
                    <td><?= esc($config['environment']) ?></td>
                </tr>
                <tr>
                    <td>Timeout</td>
                    <td><?= esc($config['timeout']) ?> segundos</td>
                </tr>
                <tr>
                    <td>Threshold</td>
                    <td><?= esc($config['threshold']) ?></td>
                </tr>
            </table>
        </div>

        <!-- Handlers Registrados -->
        <div class="section">
            <h2>📋 Handlers Registrados</h2>
            
            <?php foreach ($handlers as $handler): ?>
                <div class="handler-box">
                    <h3><?= esc(class_basename($handler['class'])) ?></h3>
                    <small style="color: #718096; font-size: 0.75rem;">
                        <?= esc($handler['class']) ?>
                    </small>
                    <div class="handler-handles">
                        <?php foreach ($handler['handles'] as $level): ?>
                            <span class="handler-handle"><?= esc($level) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Resultados de Test -->
        <?php if (!empty($test_results)): ?>
            <div class="section">
                <h2>🧪 Logs de Prueba Enviados</h2>
                
                <div class="alert-box">
                    <strong>⚠️ Importante:</strong>
                    <p>
                        Verifica en tu servicio centralizado que SOLO los logs marcados con ✅ 
                        hayan llegado (si tu threshold está en "critical"). Los demás no deberían aparecer.
                    </p>
                </div>
                
                <?php foreach ($test_results as $level => $message): ?>
                    <div class="log-item <?= esc($level) ?>">
                        <span class="log-level" style="color: <?= in_array($level, ['critical', 'alert', 'emergency']) ? '#dc2626' : '#6b7280' ?>">
                            <?= esc(strtoupper($level)) ?>
                        </span>
                        <?= esc($message) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="section">
                <h2>❌ No se enviaron logs de prueba</h2>
                <p style="color: #718096;">
                    La configuración no es válida. Revisa las variables de entorno en tu archivo .env
                </p>
            </div>
        <?php endif; ?>

        <!-- Instrucciones -->
        <div class="section">
            <h2>📝 Próximos Pasos</h2>
            <ol style="color: #4a5568; line-height: 2; padding-left: 1.5rem;">
                <li>Verifica que los logs llegaron al servicio centralizado</li>
                <li>Confirma que solo los niveles configurados se enviaron</li>
                <li><strong>ELIMINA</strong> el controlador <code>TestCentralLogger.php</code></li>
                <li><strong>ELIMINA</strong> esta vista <code>test_central_logger.php</code></li>
            </ol>
        </div>
    </div>
</body>
</html>
