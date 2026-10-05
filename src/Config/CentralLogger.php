<?php

namespace TuOrganizacion\CentralLogger\Config;

use CodeIgniter\Config\BaseConfig;

class CentralLogger extends BaseConfig
{
    public string $apiUrl = '';
    public string $apiKey = '';
    public string $appName = '';
    public string $environment = '';
    public float $timeout = 2.0;
    public string $threshold = 'critical';
    public int $maxRetries = 2;
    public bool $queueEnabled = true;
    public string $queuePath = '';
    public int $queueMaxSize = 1000;

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

    public function __construct()
    {
        parent::__construct();

        $this->apiUrl      = env('CENTRAL_LOGGER_API_URL', $this->apiUrl);
        $this->apiKey      = env('CENTRAL_LOGGER_API_KEY', $this->apiKey);
        $this->appName     = env('CENTRAL_LOGGER_APP_NAME', $this->appName);
        $this->environment = env('CENTRAL_LOGGER_ENVIRONMENT', $this->environment ?: ENVIRONMENT);
        $this->timeout     = (float) env('CENTRAL_LOGGER_TIMEOUT', $this->timeout);
        $this->threshold   = env('CENTRAL_LOGGER_THRESHOLD', $this->threshold);
        $this->maxRetries  = (int) env('CENTRAL_LOGGER_MAX_RETRIES', $this->maxRetries);
        $this->queueEnabled = filter_var(env('CENTRAL_LOGGER_QUEUE_ENABLED', true), FILTER_VALIDATE_BOOLEAN);
        $this->queuePath    = env('CENTRAL_LOGGER_QUEUE_PATH', WRITEPATH . 'logs' . DIRECTORY_SEPARATOR . 'central-logger-queue.json');
        $this->queueMaxSize = (int) env('CENTRAL_LOGGER_QUEUE_MAX_SIZE', 1000);
    }

    public function shouldHandle(string $level): bool
    {
        $level = strtolower(trim($level));
        $threshold = strtolower(trim($this->threshold));

        if (!isset($this->logLevels[$level]) || !isset($this->logLevels[$threshold])) {
            return false;
        }

        return $this->logLevels[$level] <= $this->logLevels[$threshold];
    }

    public function isValid(): bool
    {
        if (empty($this->apiUrl) || !filter_var($this->apiUrl, FILTER_VALIDATE_URL)) {
            return false;
        }

        if (empty($this->apiKey)) {
            return false;
        }

        if (empty($this->appName)) {
            return false;
        }

        if (empty($this->environment)) {
            return false;
        }

        if ($this->timeout <= 0) {
            return false;
        }

        if (!isset($this->logLevels[strtolower($this->threshold)])) {
            return false;
        }

        if ($this->maxRetries < 0) {
            return false;
        }

        return true;
    }
}
