<?php

namespace TuOrganizacion\CentralLogger\Handlers;

use CodeIgniter\Log\Handlers\BaseHandler;
use TuOrganizacion\CentralLogger\Config\CentralLogger;
use Config\Services;
use Throwable;

/**
 * Handler Centralizado de Logs para CodeIgniter 4
 * 
 * Intercepta logs de nivel crítico o superior y los envía mediante
 * HTTP POST a un servicio centralizado usando CURLRequest nativo.
 */
class CentralLogHandler extends BaseHandler
{
    /**
     * Instancia de configuración del logger
     * 
     * @var CentralLogger
     */
    protected CentralLogger $config;

    /**
     * Niveles considerados críticos que siempre deben enviarse
     * 
     * @var array<string>
     */
    protected array $criticalLevels = ['emergency', 'alert', 'critical'];

    /**
     * Constructor
     * 
     * @param array $config Configuración opcional del handler
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $this->config = new CentralLogger();

        if (isset($config['handles'])) {
            $this->handles = $config['handles'];
        }
    }

    /**
     * Maneja un evento de log y lo envía al servicio centralizado
     * 
     * @param string $level Nivel del log (emergency, alert, critical, error, etc.)
     * @param string $message Mensaje del log
     * @return bool true si se envió correctamente, false en caso contrario
     */
    public function handle($level, $message): bool
    {
        $level = strtolower($level);

        // Verificar si la configuración es válida
        if (!$this->config->isValid()) {
            return false;
        }

        // Verificar si el nivel debe ser manejado según threshold o si es crítico
        if (!$this->config->shouldHandle($level) && !in_array($level, $this->criticalLevels, true)) {
            return false;
        }

        // Construir el payload estructurado
        $payload = $this->buildPayload($level, $message);

        // Enviar al servicio centralizado
        return $this->sendToApi($payload);
    }

    /**
     * Construye el payload JSON con toda la información del log
     * 
     * @param string $level Nivel del log
     * @param string $message Mensaje del log
     * @return array
     */
    protected function buildPayload(string $level, string $message): array
    {
        return [
            'app_name'    => $this->config->appName,
            'environment' => $this->config->environment,
            'level'       => strtoupper($level),
            'message'     => $message,
            'timestamp'   => date('c'), // ISO 8601
            'ip'          => $_SERVER['REMOTE_ADDR'] ?? null,
            'uri'         => $_SERVER['REQUEST_URI'] ?? null,
            'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'method'      => $_SERVER['REQUEST_METHOD'] ?? null,
            'server_name' => gethostname(),
        ];
    }

    /**
     * Envía el payload al servicio centralizado mediante HTTP POST
     * 
     * @param array $payload Datos a enviar
     * @return bool true si la petición fue exitosa (2xx), false en caso contrario
     */
    protected function sendToApi(array $payload): bool
    {
        try {
            // Instanciar el cliente CURL nativo de CodeIgniter
            $client = Services::curlrequest([
                'timeout'     => $this->config->timeout,
                'http_errors' => false, // No lanzar excepciones por códigos HTTP
                'verify'      => ENVIRONMENT === 'production', // SSL solo en producción
            ]);

            // Preparar headers
            $headers = [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
                'X-Api-Key'    => $this->config->apiKey,
            ];

            // Realizar la petición POST
            $response = $client->post($this->config->apiUrl, [
                'headers' => $headers,
                'json'    => $payload,
            ]);

            // Verificar código de respuesta
            $statusCode = $response->getStatusCode();
            
            return $statusCode >= 200 && $statusCode < 300;

        } catch (Throwable $e) {
            // CRÍTICO: Nunca permitir que un fallo en el servicio central
            // interrumpa la ejecución de la aplicación cliente.
            // Solo registrar internamente si hay un logger de fallback disponible.
            
            // Opcionalmente se puede loguear el error localmente
            if (function_exists('log_message')) {
                log_message('error', 'CentralLogHandler falló al enviar log: ' . $e->getMessage());
            }

            return false;
        }
    }

    /**
     * Determina si este handler puede manejar el nivel dado
     * 
     * @param string $level Nivel del log
     * @return bool
     */
    public function canHandle(string $level): bool
    {
        $level = strtolower($level);
        
        return $this->config->shouldHandle($level) 
            || in_array($level, $this->criticalLevels, true);
    }
}
