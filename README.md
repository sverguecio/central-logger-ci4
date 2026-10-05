# Central Logger CI4

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.0-blue.svg)](https://php.net)
[![CodeIgniter](https://img.shields.io/badge/CodeIgniter-4.x-orange.svg)](https://codeigniter.com)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

Librería de logging centralizado para CodeIgniter 4 que intercepta logs de nivel crítico y los envía a un servicio externo mediante HTTP POST, usando el cliente nativo de CodeIgniter (`curlrequest`).

Incluye además:
- reintentos automáticos
- manejo fail-safe
- queue local para eventos no enviados
- contexto extendido del log (archivo, línea, clase, función, request_id, etc.)

## Características

- ✅ Zero dependencias externas
- ✅ Configuración por variables de entorno
- ✅ Threshold configurable
- ✅ Reintentos con backoff
- ✅ Cola local para persistencia ante fallas
- ✅ Fail-safe: no rompe la ejecución de la app si el backend central falla
- ✅ Contexto útil para diagnóstico
- ✅ Autoload PSR-4

---

## Instalación

### Opción 1: Composer (recomendada)

```bash
composer require sverguecio/central-logger-ci4
```

### Opción 2: Desde GitHub

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/sverguecio/central-logger-ci4.git"
    }
  ],
  "require": {
    "sverguecio/central-logger-ci4": "^1.1"
  }
}
```

> **Nota sobre el namespace**: el paquete se llama `sverguecio/central-logger-ci4` pero su namespace
> PSR-4 sigue siendo `TuOrganizacion\CentralLogger\`. Renombrarlo rompería el `use` de todo el código
> que ya lo consume, así que el cambio queda reservado para la versión 2.0.0. Hasta entonces, importá
> las clases con `TuOrganizacion\CentralLogger\...` tal como aparece en los ejemplos.

---

## Configuración

### 1. Variables de entorno (.env)

Agrega estas variables a tu archivo `.env`:

```env
CENTRAL_LOGGER_API_URL=https://api-logs.tudominio.com/api/logs
CENTRAL_LOGGER_API_KEY=tu_token_secreto_aqui
CENTRAL_LOGGER_APP_NAME=app-facturacion
CENTRAL_LOGGER_ENVIRONMENT=production
CENTRAL_LOGGER_TIMEOUT=2.0
CENTRAL_LOGGER_THRESHOLD=critical
CENTRAL_LOGGER_MAX_RETRIES=2
CENTRAL_LOGGER_QUEUE_ENABLED=true
CENTRAL_LOGGER_QUEUE_PATH=/path/to/writable/logs/central-logger-queue.json
CENTRAL_LOGGER_QUEUE_MAX_SIZE=1000
```

### 2. Configurar el logger de CodeIgniter

Edita `app/Config/Logger.php` y agrega el handler centralizado:

```php
<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use TuOrganizacion\CentralLogger\Handlers\CentralLogHandler;

class Logger extends BaseConfig
{
    public $threshold = 4; // 4 = LogLevel::CRITICAL

    public $handlers = [
        'CodeIgniter\Log\Handlers\FileHandler' => [
            'handles' => [
                'critical',
                'alert',
                'emergency',
                'debug',
                'error',
                'info',
                'notice',
                'warning',
            ],
        ],

        CentralLogHandler::class => [
            'handles' => [
                'critical',
                'alert',
                'emergency',
            ],
        ],
    ];
}
```

### 3. Configuración personalizada opcional

Si querés sobrescribir valores programáticamente, podés extender la clase `CentralLogger`:

```php
<?php

namespace Config;

use TuOrganizacion\CentralLogger\Config\CentralLogger as BaseCentralLogger;

class CentralLogger extends BaseCentralLogger
{
    public string $apiUrl = 'https://api-logs.tudominio.com/api/logs';
    public string $apiKey = 'tu_token_secreto';
    public string $appName = 'app-facturacion';
    public string $environment = 'production';
    public float $timeout = 2.0;
    public string $threshold = 'critical';
    public int $maxRetries = 2;
    public bool $queueEnabled = true;
    public int $queueMaxSize = 1000;
}
```

---

## Uso

Una vez configurado, el handler intercepta automáticamente los logs que cumplan el threshold configurado:

```php
log_message('emergency', 'Base de datos no disponible');
log_message('alert', 'Memoria crítica alcanzada');
log_message('critical', 'Archivo de configuración corrupto');

// Estos no se envían si el threshold es 'critical'
log_message('error', 'Usuario no encontrado');
log_message('warning', 'Cache expirado');
log_message('info', 'Usuario inició sesión');
```

### Payload enviado al servicio central

```json
{
  "app_name": "app-facturacion",
  "environment": "production",
  "level": "CRITICAL",
  "message": "Base de datos no disponible",
  "timestamp": "2026-10-02T03:12:45+00:00",
  "ip": "192.168.1.100",
  "uri": "/api/invoices/create",
  "user_agent": "Mozilla/5.0...",
  "method": "POST",
  "server_name": "web-server-01",
  "request_id": "abc123",
  "php_sapi": "fpm-fcgi",
  "file": "/var/www/app/Controllers/InvoiceController.php",
  "line": 88,
  "class": "App\\Controllers\\InvoiceController",
  "function": "create"
}
```

---

## Cola local

Si el servicio central falla o no responde, el paquete guarda el evento en una cola local en `WRITEPATH/logs` o en la ruta configurada con `CENTRAL_LOGGER_QUEUE_PATH`.

Esto permite:
- no perder eventos en cortes de red
- reintentar más adelante
- recuperar el stream cuando el backend esté disponible otra vez

Puedes forzar el flush manualmente:

```php
$handler = new \TuOrganizacion\CentralLogger\Handlers\CentralLogHandler();
$handler->flushQueue();
```

---

## Seguridad

### Headers

El paquete envía la clave en el header `X-Api-Key`:

```http
POST /api/logs HTTP/1.1
Host: api-logs.tudominio.com
Content-Type: application/json
Accept: application/json
X-Api-Key: tu_token_secreto
```

Si tu backend usa `Authorization: Bearer`, podés adaptar `CentralLogHandler.php`.

### Timeout y fail-safe

- timeout corto por defecto (2s)
- reintentos configurables
- no interrumpe la ejecución de la app si el backend cae
- SSL verificado en producción

---

## Niveles soportados

De mayor prioridad a menor:

| Nivel | Valor | Descripción |
|---|---:|---|
| `emergency` | 1 | Sistema inutilizable |
| `alert` | 2 | Acción inmediata requerida |
| `critical` | 3 | Condición crítica |
| `error` | 4 | Error no crítico |
| `warning` | 5 | Advertencia |
| `notice` | 6 | Evento relevante |
| `info` | 7 | Información general |
| `debug` | 8 | Depuración |

---

## Testing

Para probar la integración sin enviar logs reales:

1. Usá un endpoint de prueba como webhook.site
2. Configurá `.env` con esa URL
3. Ejecutá un log:

```php
log_message('critical', 'Test de integración con central logger');
```

4. Verificá el payload recibido en el endpoint de prueba

---

## Troubleshooting

### Los logs no se envían

Verificá:

```bash
php spark env:show CENTRAL_LOGGER_API_URL
php spark env:show CENTRAL_LOGGER_API_KEY
php spark env:show CENTRAL_LOGGER_THRESHOLD
```

Y confirmá que el handler está registrado en `app/Config/Logger.php`.

### Revisar la cola

Si el servicio central estaba caído, revisá:

```bash
ls writable/logs
cat writable/logs/central-logger-queue.json
```

### Timeout muy largo

```env
CENTRAL_LOGGER_TIMEOUT=5.0
```

---

## Licencia

Este paquete está bajo la licencia MIT. Ver `LICENSE`.

---

## Contribuciones

1. Fork del repositorio
2. Crear una rama (`git checkout -b feature/nueva-funcionalidad`)
3. Hacer commit y push
4. Abrir Pull Request

---

## Soporte

Para reportar bugs o feature requests, abrí un issue en GitHub.

---

## Compatibilidad

- PHP: >= 8.0
- CodeIgniter 4.x
- cURL requerido

---

## Autor

Sebastian Verguecio
