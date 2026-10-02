# Central Logger CI4

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-blue.svg)](https://php.net)
[![CodeIgniter](https://img.shields.io/badge/CodeIgniter-4.x-orange.svg)](https://codeigniter.com)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

Librería modular de **logging centralizado** para CodeIgniter 4 que intercepta logs críticos y los envía a un servicio externo mediante HTTP POST, usando exclusivamente el cliente nativo `CURLRequest` de CodeIgniter.

## 🎯 Características

- ✅ **Zero dependencias externas** - Solo usa componentes nativos de CodeIgniter 4
- ✅ **Configuración flexible** - Soporta variables de entorno y archivos de configuración
- ✅ **Fail-safe** - Nunca interrumpe la ejecución de la aplicación cliente si el servicio centralizado falla
- ✅ **Threshold configurable** - Define qué niveles de log enviar (por defecto: critical y superiores)
- ✅ **Timeout corto** - Peticiones rápidas (2s por defecto) para no bloquear la app
- ✅ **Rico en contexto** - Envía IP, URI, user agent, método HTTP, hostname, etc.
- ✅ **PSR-4 Autoload** - Instalación simple vía Composer

---

## 📦 Instalación

### Via Composer (Recomendado)

```bash
composer require tu-organizacion/central-logger-ci4
```

### Instalación manual

Si tu repositorio es privado y usas un repositorio Composer personalizado:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/tu-organizacion/central-logger-ci4.git"
        }
    ],
    "require": {
        "tu-organizacion/central-logger-ci4": "^1.0"
    }
}
```

---

## ⚙️ Configuración

### 1. Variables de Entorno (`.env`)

Agrega las siguientes variables a tu archivo `.env`:

```env
# Central Logger Configuration
CENTRAL_LOGGER_API_URL=https://api-logs.tudominio.com/api/logs
CENTRAL_LOGGER_API_KEY=tu_token_secreto_aqui
CENTRAL_LOGGER_APP_NAME=app-facturacion
CENTRAL_LOGGER_ENVIRONMENT=production
CENTRAL_LOGGER_TIMEOUT=2.0
CENTRAL_LOGGER_THRESHOLD=critical
```

### 2. Configurar el Logger de CodeIgniter

Edita el archivo `app/Config/Logger.php` y agrega el handler:

```php
<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use TuOrganizacion\CentralLogger\Handlers\CentralLogHandler;

class Logger extends BaseConfig
{
    public $threshold = 4; // 4 = LogLevel::CRITICAL

    public $handlers = [
        // Handler por defecto de archivos
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

        // Handler centralizado para logs críticos
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

### 3. Configuración Avanzada (Opcional)

Si prefieres no usar variables de entorno, puedes publicar y personalizar el archivo de configuración:

```bash
# Copia el archivo de configuración a tu app
cp vendor/tu-organizacion/central-logger-ci4/src/Config/CentralLogger.php app/Config/
```

Luego edítalo directamente:

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
}
```

---

## 🚀 Uso

Una vez configurado, el handler interceptará **automáticamente** todos los logs que cumplan el threshold:

```php
<?php

// Estos logs se enviarán al servicio centralizado
log_message('emergency', 'Base de datos no disponible');
log_message('alert', 'Memoria crítica alcanzada');
log_message('critical', 'Archivo de configuración corrupto');

// Estos NO se enviarán (por debajo del threshold 'critical')
log_message('error', 'Usuario no encontrado');
log_message('warning', 'Cache expirado');
log_message('info', 'Usuario inició sesión');
```

### Ejemplo de Payload Enviado

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
    "server_name": "web-server-01"
}
```

---

## 🔒 Seguridad

### Headers de Autenticación

El handler envía la API Key en el header `X-Api-Key`:

```
POST /api/logs HTTP/1.1
Host: api-logs.tudominio.com
Content-Type: application/json
X-Api-Key: tu_token_secreto
```

Si tu servicio centralizado usa `Authorization: Bearer`, modifica el método `sendToApi()` en `CentralLogHandler.php`:

```php
$headers = [
    'Content-Type'  => 'application/json',
    'Authorization' => 'Bearer ' . $this->config->apiKey,
];
```

### Timeout y Fail-Safe

- **Timeout corto (2s)**: Evita bloquear la aplicación si el servicio central está lento
- **Try/catch global**: Captura cualquier excepción (red caída, DNS error, timeout) y retorna `false` sin afectar la app
- **SSL Verification**: Activo en `production`, desactivado en `development` para pruebas locales

---

## 📊 Niveles de Log Soportados

De mayor a menor prioridad:

| Nivel       | Valor | Descripción                          |
|-------------|-------|--------------------------------------|
| `emergency` | 1     | Sistema inutilizable                 |
| `alert`     | 2     | Acción inmediata requerida           |
| `critical`  | 3     | Condición crítica (default threshold)|
| `error`     | 4     | Error que no requiere acción inmediata|
| `warning`   | 5     | Advertencia                          |
| `notice`    | 6     | Evento normal pero significativo     |
| `info`      | 7     | Información general                  |
| `debug`     | 8     | Información de depuración            |

---

## 🧪 Testing

Para probar la librería sin enviar logs reales:

1. Usa un endpoint de prueba como [webhook.site](https://webhook.site)
2. Configura la URL en tu `.env`:

```env
CENTRAL_LOGGER_API_URL=https://webhook.site/tu-uuid-unico
CENTRAL_LOGGER_API_KEY=test-key
CENTRAL_LOGGER_APP_NAME=app-test
CENTRAL_LOGGER_THRESHOLD=debug
```

3. Ejecuta un log de prueba:

```php
log_message('critical', 'Test de integración con el servicio centralizado');
```

4. Verifica en webhook.site que llegó el payload JSON

---

## 🛠️ Troubleshooting

### Los logs no se envían

1. **Verifica las variables de entorno**:
   ```bash
   php spark env:show CENTRAL_LOGGER_API_URL
   ```

2. **Verifica que el handler está registrado**:
   ```php
   var_dump(config('Logger')->handlers);
   ```

3. **Revisa los logs locales**:
   ```bash
   tail -f writable/logs/log-*.log
   ```

### Timeout muy largo

Si tus peticiones tardan más de 2 segundos, ajusta el timeout:

```env
CENTRAL_LOGGER_TIMEOUT=5.0
```

### Errores SSL en desarrollo local

Desactiva la verificación SSL editando `CentralLogHandler.php`:

```php
$client = Services::curlrequest([
    'timeout' => $this->config->timeout,
    'verify'  => false, // Solo para desarrollo
]);
```

---

## 📝 Licencia

Este paquete está licenciado bajo la [Licencia MIT](LICENSE).

---

## 👥 Contribución

1. Fork el repositorio
2. Crea una rama para tu feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit tus cambios (`git commit -am 'Agrega nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Crea un Pull Request

---

## 📧 Soporte

Para reportar bugs o solicitar features, abre un issue en el repositorio:

**GitHub**: [tu-organizacion/central-logger-ci4/issues](https://github.com/tu-organizacion/central-logger-ci4/issues)

---

## 🙏 Créditos

Desarrollado por **Tu Organización** para ecosistemas CodeIgniter 4 empresariales.

**Compatibilidad**:
- PHP: >= 8.1
- CodeIgniter: >= 4.0
- cURL extension requerida
