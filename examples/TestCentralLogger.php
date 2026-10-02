<?php

/**
 * Script de prueba para Central Logger CI4
 * 
 * Uso:
 * 1. Copia este archivo a tu proyecto: app/Controllers/TestCentralLogger.php
 * 2. Accede a: https://tu-app.local/test-central-logger
 * 3. Verifica que los logs lleguen al servicio centralizado
 * 4. ELIMINA este archivo después de probar
 */

namespace App\Controllers;

use TuOrganizacion\CentralLogger\Config\CentralLogger;

class TestCentralLogger extends BaseController
{
    public function index()
    {
        $config = new CentralLogger();
        
        // 1. Verificar configuración
        $configStatus = [
            'api_url'     => !empty($config->apiUrl) ? '✅ Configurado' : '❌ Falta',
            'api_key'     => !empty($config->apiKey) ? '✅ Configurado' : '❌ Falta',
            'app_name'    => !empty($config->appName) ? $config->appName : '❌ Falta',
            'environment' => $config->environment ?: '❌ Falta',
            'timeout'     => $config->timeout,
            'threshold'   => $config->threshold,
            'is_valid'    => $config->isValid() ? '✅ Válida' : '❌ Inválida',
        ];

        // 2. Enviar logs de prueba si la config es válida
        $testResults = [];
        
        if ($config->isValid()) {
            $levels = [
                'debug'     => 'Mensaje de debug - NO debería enviarse si threshold=critical',
                'info'      => 'Mensaje de info - NO debería enviarse si threshold=critical',
                'warning'   => 'Mensaje de warning - NO debería enviarse si threshold=critical',
                'error'     => 'Mensaje de error - NO debería enviarse si threshold=critical',
                'critical'  => '✅ Mensaje CRITICAL - DEBE enviarse al servicio central',
                'alert'     => '✅ Mensaje ALERT - DEBE enviarse al servicio central',
                'emergency' => '✅ Mensaje EMERGENCY - DEBE enviarse al servicio central',
            ];

            foreach ($levels as $level => $message) {
                log_message($level, "[TEST] {$message}");
                $testResults[$level] = $message;
            }
        }

        // 3. Verificar handlers registrados
        $loggerConfig = config('Logger');
        $handlersInfo = [];
        
        foreach ($loggerConfig->handlers as $handlerClass => $handlerConfig) {
            $handlersInfo[] = [
                'class'   => $handlerClass,
                'handles' => $handlerConfig['handles'] ?? [],
            ];
        }

        // 4. Renderizar vista de resultados
        return view('test_central_logger', [
            'config'       => $configStatus,
            'test_results' => $testResults,
            'handlers'     => $handlersInfo,
        ]);
    }

    /**
     * Endpoint simple para pruebas rápidas
     */
    public function quick()
    {
        $config = new CentralLogger();

        if (!$config->isValid()) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Central Logger no está configurado correctamente',
                'config' => [
                    'api_url_set' => !empty($config->apiUrl),
                    'api_key_set' => !empty($config->apiKey),
                    'app_name_set' => !empty($config->appName),
                ],
            ])->setStatusCode(500);
        }

        // Enviar un log crítico de prueba
        log_message('critical', '[TEST] Prueba rápida de Central Logger desde ' . $config->appName);

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Log crítico enviado. Verifica el servicio centralizado.',
            'data' => [
                'app_name'    => $config->appName,
                'environment' => $config->environment,
                'timestamp'   => date('Y-m-d H:i:s'),
            ],
        ]);
    }
}
