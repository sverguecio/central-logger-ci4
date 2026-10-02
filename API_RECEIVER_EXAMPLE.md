# Ejemplo de Servicio Receptor de Logs Centralizados

Este documento describe un ejemplo básico de cómo debe ser el **servicio centralizado receptor** que recibe los logs de las 25 aplicaciones.

---

## 📋 Especificación de la API

### Endpoint

```
POST /api/logs
```

### Headers Requeridos

```http
Content-Type: application/json
X-Api-Key: tu_token_secreto
```

### Body (Payload JSON)

```json
{
    "app_name": "app-facturacion",
    "environment": "production",
    "level": "CRITICAL",
    "message": "Base de datos no disponible",
    "timestamp": "2026-10-02T03:12:45+00:00",
    "ip": "192.168.1.100",
    "uri": "/api/invoices/create",
    "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
    "method": "POST",
    "server_name": "web-server-01"
}
```

### Respuestas

#### Éxito (201 Created)

```json
{
    "status": "success",
    "log_id": "uuid-del-log-guardado",
    "message": "Log recibido correctamente"
}
```

#### Error de Autenticación (401 Unauthorized)

```json
{
    "status": "error",
    "message": "API Key inválida o faltante"
}
```

#### Error de Validación (422 Unprocessable Entity)

```json
{
    "status": "error",
    "message": "Datos inválidos",
    "errors": {
        "app_name": ["El campo app_name es requerido"],
        "level": ["El nivel debe ser uno de: EMERGENCY, ALERT, CRITICAL, ERROR, WARNING, NOTICE, INFO, DEBUG"]
    }
}
```

---

## 🛠️ Implementación de Ejemplo (CodeIgniter 4)

### 1. Controlador: `app/Controllers/Api/Logs.php`

```php
<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\LogModel;

class Logs extends ResourceController
{
    protected $format    = 'json';
    protected $modelName = LogModel::class;

    /**
     * Recibe y almacena un log desde una aplicación cliente
     * 
     * POST /api/logs
     */
    public function create()
    {
        // 1. Validar API Key
        $apiKey = $this->request->getHeaderLine('X-Api-Key');
        
        if (empty($apiKey) || !$this->validateApiKey($apiKey)) {
            return $this->respond([
                'status'  => 'error',
                'message' => 'API Key inválida o faltante',
            ], 401);
        }

        // 2. Validar datos del payload
        $rules = [
            'app_name'    => 'required|max_length[100]',
            'environment' => 'required|in_list[production,staging,development,testing]',
            'level'       => 'required|in_list[EMERGENCY,ALERT,CRITICAL,ERROR,WARNING,NOTICE,INFO,DEBUG]',
            'message'     => 'required',
            'timestamp'   => 'required|valid_date',
        ];

        if (!$this->validate($rules)) {
            return $this->respond([
                'status'  => 'error',
                'message' => 'Datos inválidos',
                'errors'  => $this->validator->getErrors(),
            ], 422);
        }

        // 3. Obtener datos validados
        $data = [
            'app_name'    => $this->request->getVar('app_name'),
            'environment' => $this->request->getVar('environment'),
            'level'       => $this->request->getVar('level'),
            'message'     => $this->request->getVar('message'),
            'timestamp'   => $this->request->getVar('timestamp'),
            'ip'          => $this->request->getVar('ip'),
            'uri'         => $this->request->getVar('uri'),
            'user_agent'  => $this->request->getVar('user_agent'),
            'method'      => $this->request->getVar('method'),
            'server_name' => $this->request->getVar('server_name'),
            'received_at' => date('Y-m-d H:i:s'),
        ];

        // 4. Guardar en base de datos
        try {
            $logId = $this->model->insert($data);

            // 5. Opcionalmente, disparar alertas para logs críticos
            if (in_array($data['level'], ['EMERGENCY', 'ALERT', 'CRITICAL'])) {
                $this->triggerAlert($data);
            }

            return $this->respond([
                'status'  => 'success',
                'log_id'  => $logId,
                'message' => 'Log recibido correctamente',
            ], 201);

        } catch (\Throwable $e) {
            log_message('error', 'Error al guardar log: ' . $e->getMessage());
            
            return $this->respond([
                'status'  => 'error',
                'message' => 'Error interno al procesar el log',
            ], 500);
        }
    }

    /**
     * Valida la API Key contra la base de datos o configuración
     */
    protected function validateApiKey(string $apiKey): bool
    {
        // Opción 1: API Key fija en configuración
        return $apiKey === env('CENTRAL_LOGS_API_KEY');

        // Opción 2: Validar contra tabla de API Keys
        // $apiKeyModel = new ApiKeyModel();
        // return $apiKeyModel->where('key', $apiKey)->where('is_active', 1)->countAllResults() > 0;
    }

    /**
     * Dispara alertas para logs críticos (Slack, email, etc.)
     */
    protected function triggerAlert(array $data): void
    {
        // Ejemplo: Enviar a Slack
        // $slackWebhook = env('SLACK_WEBHOOK_URL');
        // $message = "🚨 *{$data['level']}* en {$data['app_name']} ({$data['environment']})\n{$data['message']}";
        // ...enviar a Slack...

        // Ejemplo: Enviar email
        // $email = \Config\Services::email();
        // $email->setTo('sysadmin@tudominio.com');
        // $email->setSubject("Log Crítico: {$data['app_name']}");
        // $email->setMessage(...);
        // $email->send();
    }
}
```

### 2. Modelo: `app/Models/LogModel.php`

```php
<?php

namespace App\Models;

use CodeIgniter\Model;

class LogModel extends Model
{
    protected $table            = 'centralized_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    
    protected $allowedFields    = [
        'app_name',
        'environment',
        'level',
        'message',
        'timestamp',
        'ip',
        'uri',
        'user_agent',
        'method',
        'server_name',
        'received_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'app_name'    => 'required|max_length[100]',
        'environment' => 'required|in_list[production,staging,development,testing]',
        'level'       => 'required|in_list[EMERGENCY,ALERT,CRITICAL,ERROR,WARNING,NOTICE,INFO,DEBUG]',
        'message'     => 'required',
    ];
}
```

### 3. Migración: `app/Database/Migrations/2026-10-02-031245_CreateCentralizedLogsTable.php`

```php
<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCentralizedLogsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'app_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'environment' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'level' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'message' => [
                'type' => 'TEXT',
            ],
            'timestamp' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'ip' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
            ],
            'uri' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'user_agent' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'method' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
            ],
            'server_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'received_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('app_name');
        $this->forge->addKey('level');
        $this->forge->addKey('environment');
        $this->forge->addKey('timestamp');
        $this->forge->addKey('created_at');
        
        $this->forge->createTable('centralized_logs');
    }

    public function down()
    {
        $this->forge->dropTable('centralized_logs');
    }
}
```

### 4. Ruta: `app/Config/Routes.php`

```php
<?php

// API de Logs Centralizados
$routes->group('api', ['namespace' => 'App\Controllers\Api'], function($routes) {
    $routes->post('logs', 'Logs::create');
});
```

---

## 🧪 Pruebas con cURL

### Envío de log exitoso

```bash
curl -X POST https://api-logs.tudominio.com/api/logs \
  -H "Content-Type: application/json" \
  -H "X-Api-Key: tu_token_secreto" \
  -d '{
    "app_name": "app-facturacion",
    "environment": "production",
    "level": "CRITICAL",
    "message": "Base de datos no disponible",
    "timestamp": "2026-10-02T03:12:45+00:00",
    "ip": "192.168.1.100",
    "uri": "/api/invoices/create",
    "user_agent": "Mozilla/5.0",
    "method": "POST",
    "server_name": "web-server-01"
  }'
```

### Respuesta esperada

```json
{
    "status": "success",
    "log_id": 12345,
    "message": "Log recibido correctamente"
}
```

---

## 📊 Dashboard de Visualización (Opcional)

### Controlador: `app/Controllers/Dashboard/Logs.php`

```php
<?php

namespace App\Controllers\Dashboard;

use App\Controllers\BaseController;
use App\Models\LogModel;

class Logs extends BaseController
{
    public function index()
    {
        $model = new LogModel();
        
        $data = [
            'logs' => $model->orderBy('timestamp', 'DESC')->paginate(50),
            'pager' => $model->pager,
        ];

        return view('dashboard/logs/index', $data);
    }

    public function filter()
    {
        $model = new LogModel();
        $builder = $model->builder();

        // Filtros
        if ($appName = $this->request->getGet('app_name')) {
            $builder->where('app_name', $appName);
        }

        if ($level = $this->request->getGet('level')) {
            $builder->where('level', $level);
        }

        if ($environment = $this->request->getGet('environment')) {
            $builder->where('environment', $environment);
        }

        $data = [
            'logs' => $builder->orderBy('timestamp', 'DESC')->paginate(50),
            'pager' => $model->pager,
        ];

        return view('dashboard/logs/index', $data);
    }
}
```

---

## 🔒 Seguridad Adicional

### 1. Rate Limiting

Agrega un filtro de rate limiting para evitar abuso:

```php
// app/Filters/RateLimit.php
public function before(RequestInterface $request, $arguments = null)
{
    $key = $request->getIPAddress();
    $limit = 100; // 100 requests por minuto
    
    // Implementar lógica de rate limiting con Redis/Memcached
}
```

### 2. Validación de IP Permitidas

```php
protected function validateApiKey(string $apiKey): bool
{
    if ($apiKey !== env('CENTRAL_LOGS_API_KEY')) {
        return false;
    }

    // Lista blanca de IPs permitidas
    $allowedIps = explode(',', env('ALLOWED_IPS', ''));
    $clientIp = $this->request->getIPAddress();

    return in_array($clientIp, $allowedIps);
}
```

---

## 📈 Optimizaciones

### 1. Queue Asíncrona

Para alto volumen de logs, usa una cola (Redis, RabbitMQ):

```php
// En lugar de guardar directamente, encolar
$queue = \Config\Services::queue();
$queue->push('ProcessLogJob', $data);
```

### 2. Índices de Base de Datos

```sql
CREATE INDEX idx_app_level_timestamp ON centralized_logs (app_name, level, timestamp);
CREATE INDEX idx_critical_logs ON centralized_logs (level, timestamp) WHERE level IN ('EMERGENCY', 'ALERT', 'CRITICAL');
```

### 3. Particionado de Tabla

Para millones de logs, particiona por fecha:

```sql
ALTER TABLE centralized_logs PARTITION BY RANGE (YEAR(timestamp)) (
    PARTITION p2026 VALUES LESS THAN (2027),
    PARTITION p2027 VALUES LESS THAN (2028),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);
```

---

¡Listo! Con este ejemplo tienes todo lo necesario para implementar el servicio receptor de logs centralizados.
