<?php

namespace TuOrganizacion\CentralLogger\Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Configuración para el Logger Centralizado
 * 
 * Esta clase define todos los parámetros necesarios para conectar
 * con el servicio centralizado de logs. Soporta variables de entorno.
 */
class CentralLogger extends BaseConfig
{
    /**
     * URL del endpoint POST del servicio centralizado de logs
     * 
     * @var string
     */
    public string $apiUrl = '';

    /**
     * Token de autenticación para el servicio de logs
     * 
     * @var string
     */
    public string $apiKey = '';

    /**
     * Nombre o identificador único de la aplicación cliente
     * 
     * @var string
     */
    public string $appName = '';

    /**
     * Entorno de ejecución (production, staging, development, testing)
     * 
     * @var string
     */
    public string $environment = '';

    /**
     * Timeout en segundos para la petición HTTP (debe ser corto)
     * 
     * @var float
     */
    public float $timeout = 2.0;

    /**
     * Nivel mínimo de log a enviar al servicio central
     * Valores posibles: emergency, alert, critical, error, warning, notice, info, debug
     * 
     * @var string
     */
    public string $threshold = 'critical';

    /**
     * Mapeo de niveles de log a valores numéricos para comparación
     * 
     * @var array<string, int>
     */
    protected array $logLevels = [
        'emergency' => 1,
        'alert'     => 2,
        'critical'  => 3,
        'error'     => 4,
        'warning'   => 5,
        'notice'    => 6,
        'info'      => 7,
        'debug'     => 8,
    ];

    /**
     * Constructor - Carga valores desde variables de entorno
     */
    public function __construct()
    {
        parent::__construct();

        $this->apiUrl      = env('CENTRAL_LOGGER_API_URL', $this->apiUrl);
        $this->apiKey      = env('CENTRAL_LOGGER_API_KEY', $this->apiKey);
        $this->appName     = env('CENTRAL_LOGGER_APP_NAME', $this->appName);
        $this->environment = env('CENTRAL_LOGGER_ENVIRONMENT', $this->environment ?: ENVIRONMENT);
        $this->timeout     = (float) env('CENTRAL_LOGGER_TIMEOUT', $this->timeout);
        $this->threshold   = env('CENTRAL_LOGGER_THRESHOLD', $this->threshold);
    }

    /**
     * Verifica si un nivel dado debe ser enviado según el threshold configurado
     * 
     * @param string $level Nivel del log a verificar
     * @return bool
     */
    public function shouldHandle(string $level): bool
    {
        $level     = strtolower($level);
        $threshold = strtolower($this->threshold);

        if (!isset($this->logLevels[$level]) || !isset($this->logLevels[$threshold])) {
            return false;
        }

        return $this->logLevels[$level] <= $this->logLevels[$threshold];
    }

    /**
     * Valida que la configuración esté completa y sea válida
     * 
     * @return bool
     */
    public function isValid(): bool
    {
        return !empty($this->apiUrl) 
            && !empty($this->apiKey) 
            && !empty($this->appName)
            && !empty($this->environment);
    }
}
