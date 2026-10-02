<?php

/**
 * Ejemplo de configuración del Logger de CodeIgniter 4 con Central Logger
 * 
 * Este archivo es una REFERENCIA. Debes copiar la configuración del handler
 * a tu archivo existente app/Config/Logger.php
 * 
 * NO copies este archivo completo, solo la sección del handler CentralLogHandler
 */

namespace Config;

use CodeIgniter\Config\BaseConfig;
use TuOrganizacion\CentralLogger\Handlers\CentralLogHandler;

class Logger extends BaseConfig
{
    /**
     * Threshold local de CodeIgniter
     * 
     * Controla qué se guarda en los archivos locales de log.
     * Valores: 0 = Desactivado, 1-9 = Niveles de error
     * 
     * Recomendado: 9 (guardar todo localmente para debugging)
     */
    public int $threshold = 9;

    /**
     * Handlers de log
     * 
     * IMPORTANTE: El orden importa. Los handlers se ejecutan en el orden definido.
     */
    public array $handlers = [
        /**
         * Handler por defecto de archivos (mantener para debugging local)
         * 
         * Guarda logs en writable/logs/log-YYYY-MM-DD.log
         */
        'CodeIgniter\Log\Handlers\FileHandler' => [
            'handles' => [
                'critical',
                'alert',
                'emergency',
                'error',
                'warning',
                'notice',
                'info',
                'debug',
            ],
        ],

        /**
         * ============================================================
         * CENTRAL LOGGER HANDLER
         * ============================================================
         * 
         * Handler centralizado que envía logs críticos a un servicio externo.
         * 
         * Configuración:
         * - Configura las variables de entorno en .env (ver .env.example)
         * - Los logs se envían de forma asíncrona sin bloquear la aplicación
         * - Si el servicio central falla, la app continúa normalmente
         * 
         * Handles: Define qué niveles de log se envían al servicio central
         * - Por defecto: solo emergency, alert, critical
         * - Puedes agregar 'error' si necesitas más cobertura
         * 
         * ============================================================
         */
        CentralLogHandler::class => [
            'handles' => [
                'emergency',  // Sistema inutilizable
                'alert',      // Acción inmediata requerida
                'critical',   // Condición crítica
                // 'error',   // Descomenta si quieres enviar errores también
            ],
        ],
    ];

    /**
     * Configuración adicional para el FileHandler
     * 
     * Puedes agregar más configuraciones aquí si es necesario
     */
    public string $dateFormat = 'Y-m-d H:i:s';
}
