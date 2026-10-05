# Guía de Integración Rápida

Esta guía te ayudará a integrar el Central Logger CI4 en tus 25 aplicaciones CodeIgniter 4 de forma rápida y consistente.

---

## 🚀 Instalación Rápida (Paso a Paso)

### 1. Instalar el paquete

```bash
cd /ruta/a/tu/aplicacion
composer require sverguecio/central-logger-ci4
```

### 2. Configurar variables de entorno

Agrega estas líneas a tu archivo `.env`:

```env
#--------------------------------------------------------------------
# CENTRAL LOGGER
#--------------------------------------------------------------------
CENTRAL_LOGGER_API_URL=https://api-logs.tudominio.com/api/logs
CENTRAL_LOGGER_API_KEY=tu_token_secreto_aqui
CENTRAL_LOGGER_APP_NAME=nombre-de-esta-app
CENTRAL_LOGGER_ENVIRONMENT=${CI_ENVIRONMENT}
CENTRAL_LOGGER_TIMEOUT=2.0
CENTRAL_LOGGER_THRESHOLD=critical
```

**Importante**: Cambia `nombre-de-esta-app` por un identificador único para cada aplicación (ej: `app-facturacion`, `app-inventario`, `app-crm`, etc.)

### 3. Registrar el Handler

Edita `app/Config/Logger.php` y agrega el handler:

```php
<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use TuOrganizacion\CentralLogger\Handlers\CentralLogHandler;

class Logger extends BaseConfig
{
    public int $threshold = 9; // Threshold local (muestra todo)

    public array $handlers = [
        // Handler local de archivos (mantener para debugging local)
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

        // *** NUEVO: Handler centralizado para logs críticos ***
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

### 4. Verificar la instalación

Crea un archivo temporal `app/Controllers/TestLogger.php`:

```php
<?php

namespace App\Controllers;

class TestLogger extends BaseController
{
    public function test()
    {
        log_message('critical', 'Test de integración con Central Logger desde ' . getenv('CENTRAL_LOGGER_APP_NAME'));
        
        return $this->response->setJSON([
            'status' => 'ok',
            'message' => 'Log crítico enviado. Verifica el servicio centralizado.',
        ]);
    }
}
```

Accede a `https://tu-app.local/test-logger/test` y verifica que el log llegue al servicio centralizado.

**Una vez verificado, elimina el controlador de prueba**.

---

## 📋 Checklist de Integración

Para cada una de tus 25 aplicaciones:

- [ ] Ejecutar `composer require sverguecio/central-logger-ci4`
- [ ] Agregar variables de entorno al `.env`
- [ ] Cambiar `CENTRAL_LOGGER_APP_NAME` por nombre único
- [ ] Editar `app/Config/Logger.php` y registrar el handler
- [ ] Probar con endpoint de test
- [ ] Verificar que los logs llegan al servicio central
- [ ] Eliminar controlador de prueba
- [ ] Commit y deploy

---

## 🔄 Script de Instalación Automatizada

Si tienes acceso SSH a todos los servidores, puedes usar este script bash:

```bash
#!/bin/bash

# Script de instalación masiva de Central Logger CI4
# Uso: ./install-central-logger.sh

APPS=(
    "/var/www/app-facturacion"
    "/var/www/app-inventario"
    "/var/www/app-crm"
    # ... agregar las 25 rutas
)

API_URL="https://api-logs.tudominio.com/api/logs"
API_KEY="tu_token_secreto_aqui"

for APP_PATH in "${APPS[@]}"; do
    echo "=========================================="
    echo "Procesando: $APP_PATH"
    echo "=========================================="
    
    if [ ! -d "$APP_PATH" ]; then
        echo "⚠️  Directorio no existe: $APP_PATH"
        continue
    fi
    
    cd "$APP_PATH" || continue
    
    # 1. Instalar el paquete
    echo "📦 Instalando paquete..."
    composer require sverguecio/central-logger-ci4 --no-interaction
    
    # 2. Obtener el nombre de la app del directorio
    APP_NAME=$(basename "$APP_PATH")
    
    # 3. Agregar configuración al .env (si no existe)
    if ! grep -q "CENTRAL_LOGGER_API_URL" .env; then
        echo ""
        echo "# Central Logger Configuration" >> .env
        echo "CENTRAL_LOGGER_API_URL=$API_URL" >> .env
        echo "CENTRAL_LOGGER_API_KEY=$API_KEY" >> .env
        echo "CENTRAL_LOGGER_APP_NAME=$APP_NAME" >> .env
        echo "CENTRAL_LOGGER_ENVIRONMENT=\${CI_ENVIRONMENT}" >> .env
        echo "CENTRAL_LOGGER_TIMEOUT=2.0" >> .env
        echo "CENTRAL_LOGGER_THRESHOLD=critical" >> .env
        echo "✅ Variables de entorno agregadas"
    else
        echo "ℹ️  Variables ya existen en .env"
    fi
    
    # 4. Backup del Logger.php
    cp app/Config/Logger.php app/Config/Logger.php.backup
    
    echo "✅ Instalación completada para $APP_NAME"
    echo ""
done

echo "=========================================="
echo "🎉 Proceso finalizado"
echo "=========================================="
echo ""
echo "⚠️  IMPORTANTE: Debes editar manualmente app/Config/Logger.php"
echo "   en cada aplicación para registrar el handler."
echo ""
echo "   Ver: README.md sección 'Configuración'"
```

Guarda el script como `install-central-logger.sh`, dale permisos de ejecución y ejecútalo:

```bash
chmod +x install-central-logger.sh
./install-central-logger.sh
```

---

## 🧪 Probar en Ambiente de Staging

Antes de desplegar en producción:

1. **Usa un endpoint de prueba** como [webhook.site](https://webhook.site):

```env
CENTRAL_LOGGER_API_URL=https://webhook.site/tu-uuid-unico
CENTRAL_LOGGER_API_KEY=test-key-staging
CENTRAL_LOGGER_ENVIRONMENT=staging
CENTRAL_LOGGER_THRESHOLD=debug  # Más permisivo para testing
```

2. **Genera logs de prueba** en varios niveles:

```php
log_message('debug', 'Mensaje de debug');
log_message('info', 'Información general');
log_message('warning', 'Advertencia menor');
log_message('error', 'Error recuperable');
log_message('critical', '¡Esto debe llegar al servicio central!');
log_message('emergency', '¡Emergencia del sistema!');
```

3. **Verifica en webhook.site** que solo los logs `critical` y superiores lleguen (o todos si configuraste `threshold=debug`)

---

## 🚨 Troubleshooting Común

### Problema: Los logs no llegan al servicio central

**Solución 1**: Verifica las variables de entorno

```php
// Agrega temporalmente en un controlador
var_dump([
    'url' => env('CENTRAL_LOGGER_API_URL'),
    'key' => env('CENTRAL_LOGGER_API_KEY') ? 'CONFIGURADO' : 'FALTA',
    'app' => env('CENTRAL_LOGGER_APP_NAME'),
]);
```

**Solución 2**: Revisa los logs locales

```bash
tail -f writable/logs/log-$(date +%Y-%m-%d).log
```

Busca líneas como:

```
ERROR --> CentralLogHandler falló al enviar log: Connection timeout
```

**Solución 3**: Aumenta el timeout

```env
CENTRAL_LOGGER_TIMEOUT=5.0
```

### Problema: Demasiados logs se están enviando

Ajusta el threshold a un nivel más restrictivo:

```env
CENTRAL_LOGGER_THRESHOLD=emergency  # Solo emergencias
```

### Problema: Error SSL en desarrollo local

Si usas certificados auto-firmados, desactiva temporalmente la verificación SSL (solo desarrollo):

Edita `vendor/sverguecio/central-logger-ci4/src/Handlers/CentralLogHandler.php`:

```php
$client = Services::curlrequest([
    'timeout' => $this->config->timeout,
    'verify'  => false, // SOLO PARA DESARROLLO
]);
```

---

## 📊 Monitoreo Post-Instalación

Después de instalar en las 25 aplicaciones:

1. **Dashboard centralizado**: Verifica que los logs lleguen desde todas las apps
2. **Alerta si falta alguna**: Busca por `app_name` en tu servicio central
3. **Verifica volumen de logs**: Ajusta thresholds si hay demasiados o muy pocos logs

---

## 🔐 Rotación de API Keys

Para rotar la API Key en las 25 aplicaciones:

```bash
# Script de rotación masiva
for app in /var/www/app-*; do
    cd "$app"
    sed -i 's/CENTRAL_LOGGER_API_KEY=.*/CENTRAL_LOGGER_API_KEY=nueva_key_aqui/' .env
    echo "✅ Key actualizada en $(basename $app)"
done
```

---

## 📞 Soporte

Si tienes problemas durante la instalación:

1. Verifica la [sección Troubleshooting del README](../README.md#-troubleshooting)
2. Revisa los logs locales en `writable/logs/`
3. Abre un issue en el repositorio interno

---

¡Listo! Con esta guía deberías poder integrar el Central Logger en tus 25 aplicaciones de forma rápida y sin complicaciones.
