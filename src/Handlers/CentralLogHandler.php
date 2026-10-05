<?php

namespace TuOrganizacion\CentralLogger\Handlers;

use CodeIgniter\Log\Handlers\BaseHandler;
use Config\Services;
use Throwable;
use TuOrganizacion\CentralLogger\Config\CentralLogger;

class CentralLogHandler extends BaseHandler
{
    protected CentralLogger $config;
    protected array $criticalLevels = ['emergency', 'alert', 'critical'];

    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $this->config = new CentralLogger();

        if (isset($config['handles'])) {
            $this->handles = $config['handles'];
        }
    }

    public function handle($level, $message): bool
    {
        $level = strtolower((string) $level);

        if (!$this->shouldSendLevel($level)) {
            return false;
        }

        $payload = $this->buildPayload($level, $message);
        $sent = $this->sendToApi($payload);

        if (!$sent) {
            if ($this->config->queueEnabled) {
                $this->enqueueFailedLog($payload);
            }

            return false;
        }

        $this->flushQueue();

        return true;
    }

    public function flushQueue(): int
    {
        if (!$this->config->queueEnabled) {
            return 0;
        }

        $queuePath = $this->config->queuePath;

        if (!is_file($queuePath)) {
            return 0;
        }

        $content = @file_get_contents($queuePath);

        if ($content === false || trim($content) === '') {
            @unlink($queuePath);
            return 0;
        }

        $items = json_decode($content, true);

        if (!is_array($items)) {
            @unlink($queuePath);
            return 0;
        }

        $processed = 0;

        foreach ($items as $item) {
            $payload = $item['payload'] ?? null;

            if (!is_array($payload)) {
                continue;
            }

            if ($this->sendToApi($payload, false)) {
                $processed++;
            } else {
                break;
            }
        }

        if ($processed > 0) {
            $remaining = $this->readQueue();

            if (!empty($remaining)) {
                $this->writeQueue($remaining);
            } else {
                @unlink($queuePath);
            }
        }

        return $processed;
    }

    protected function shouldSendLevel(string $level): bool
    {
        if ($level === '') {
            return false;
        }

        if (!$this->config->isValid()) {
            return false;
        }

        return $this->config->shouldHandle($level)
            || in_array($level, $this->criticalLevels, true);
    }

    protected function buildPayload(string $level, string $message): array
    {
        $caller = $this->resolveCallerInfo();

        return [
            'app_name'     => $this->config->appName,
            'environment'  => $this->config->environment,
            'level'        => strtoupper($level),
            'message'      => (string) $message,
            'timestamp'    => date('c'),
            'ip'           => $this->getServerValue('REMOTE_ADDR'),
            'uri'          => $this->getServerValue('REQUEST_URI'),
            'user_agent'   => $this->getServerValue('HTTP_USER_AGENT'),
            'method'       => $this->getServerValue('REQUEST_METHOD'),
            'server_name'  => gethostname() ?: php_uname('n'),
            'request_id'   => $this->getServerValue('HTTP_X_REQUEST_ID')
                ?? $this->getServerValue('UNIQUE_ID')
                ?? null,
            'php_sapi'     => php_sapi_name(),
            'file'         => $caller['file'] ?? null,
            'line'         => $caller['line'] ?? null,
            'class'        => $caller['class'] ?? null,
            'function'     => $caller['function'] ?? null,
        ];
    }

    protected function sendToApi(array $payload, bool $logWarnings = true): bool
    {
        $maxAttempts = max(1, $this->config->maxRetries + 1);

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $client = Services::curlrequest([
                    'timeout'     => $this->config->timeout,
                    'http_errors' => false,
                    'verify'      => ENVIRONMENT === 'production',
                ]);

                $response = $client->post($this->config->apiUrl, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept'       => 'application/json',
                        'X-Api-Key'    => $this->config->apiKey,
                    ],
                    'json' => $payload,
                ]);

                $statusCode = $response->getStatusCode();

                if ($statusCode >= 200 && $statusCode < 300) {
                    return true;
                }

                $body = (string) $response->getBody();

                if ($logWarnings && function_exists('log_message')) {
                    log_message('warning', sprintf(
                        'Central logger respondió con %s. Body: %s',
                        $statusCode,
                        $body ?: 'sin cuerpo'
                    ));
                }

                if ($attempt >= $maxAttempts) {
                    return false;
                }

                $this->waitBeforeRetry($attempt);

            } catch (Throwable $e) {
                if ($logWarnings && function_exists('log_message')) {
                    log_message('error', 'CentralLogHandler falló al enviar log: ' . $e->getMessage());
                }

                if ($attempt >= $maxAttempts) {
                    return false;
                }

                $this->waitBeforeRetry($attempt);
            }
        }

        return false;
    }

    protected function enqueueFailedLog(array $payload): void
    {
        $queuePath = $this->config->queuePath;
        $dir = dirname($queuePath);

        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $items = $this->readQueue();

        $items[] = [
            'timestamp' => date('c'),
            'payload'   => $payload,
        ];

        if (count($items) > $this->config->queueMaxSize) {
            $items = array_slice($items, -$this->config->queueMaxSize);
        }

        $this->writeQueue($items);
    }

    protected function readQueue(): array
    {
        $queuePath = $this->config->queuePath;

        if (!is_file($queuePath)) {
            return [];
        }

        $content = @file_get_contents($queuePath);

        if ($content === false || trim($content) === '') {
            @unlink($queuePath);
            return [];
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : [];
    }

    protected function writeQueue(array $items): void
    {
        $queuePath = $this->config->queuePath;
        $dir = dirname($queuePath);

        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $json = json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            return;
        }

        @file_put_contents($queuePath, $json, LOCK_EX);
    }

    protected function waitBeforeRetry(int $attempt): void
    {
        $sleepMicroseconds = min(500000 * $attempt, 2000000);
        usleep($sleepMicroseconds);
    }

    protected function getServerValue(string $key): ?string
    {
        if (!isset($_SERVER[$key])) {
            return null;
        }

        $value = $_SERVER[$key];

        if ($value === '' || $value === null) {
            return null;
        }

        return (string) $value;
    }

    protected function resolveCallerInfo(): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 12);

        foreach ($trace as $frame) {
            $class = $frame['class'] ?? null;
            $function = $frame['function'] ?? null;
            $file = $frame['file'] ?? null;
            $line = $frame['line'] ?? null;

            if ($class === self::class || $function === 'log_message') {
                continue;
            }

            if ($file !== null && $line !== null) {
                return [
                    'file'     => $file,
                    'line'     => $line,
                    'class'    => $class,
                    'function' => $function,
                ];
            }
        }

        return [];
    }

    public function canHandle(string $level): bool
    {
        $level = strtolower((string) $level);

        return $this->shouldSendLevel($level);
    }
}
