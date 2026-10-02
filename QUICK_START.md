# Central Logger CI4 - Guía Rápida de 5 Minutos

## 🚀 Instalación Express

### 1. Instalar el paquete (1 minuto)

```bash
composer require tu-organizacion/central-logger-ci4
```

### 2. Configurar .env (2 minutos)

Agregar al final de tu archivo `.env`:

```env
CENTRAL_LOGGER_API_URL=https://api-logs.tudominio.com/api/logs
CENTRAL_LOGGER_API_KEY=tu_token_secreto
CENTRAL_LOGGER_APP_NAME=nombre-unico-de-esta-app
```

### 3. Registrar el handler (2 minutos)

Editar `app/Config/Logger.php` y agregar:

```php
use TuOrganizacion\CentralLogger\Handlers\CentralLogHandler;

public array $handlers = [
    // ... handlers existentes ...
    
    CentralLogHandler::class => [
        'handles' => ['critical', 'alert', 'emergency'],
    ],
];
```

### 4. Verificar

```php
// En cualquier controlador o función
log_message('critical', 'Test de Central Logger');
```

---

## 📋 Cheat Sheet

### Niveles de Log (de mayor a menor severidad)

```php
log_message('emergency', 'Sistema caído');      // Siempre se envía
log_message('alert', 'Acción inmediata');       // Siempre se envía
log_message('critical', 'Condición crítica');   // Siempre se envía
log_message('error', 'Error recuperable');      // Solo si threshold <= error
log_message('warning', 'Advertencia');          // Solo si threshold <= warning
log_message('notice', 'Evento significativo');  // Solo si threshold <= notice
log_message('info', 'Información general');     // Solo si threshold <= info
log_message('debug', 'Debug detallado');        // Solo si threshold <= debug
```

### Variables de Entorno

| Variable                      | Requerido | Default     | Descripción                           |
|-------------------------------|-----------|-------------|---------------------------------------|
| `CENTRAL_LOGGER_API_URL`      | ✅ Sí     | -           | URL del endpoint central              |
| `CENTRAL_LOGGER_API_KEY`      | ✅ Sí     | -           | Token de autenticación                |
| `CENTRAL_LOGGER_APP_NAME`     | ✅ Sí     | -           | Identificador único de la app         |
| `CENTRAL_LOGGER_ENVIRONMENT`  | ❌ No     | `CI_ENV`    | production, staging, development      |
| `CENTRAL_LOGGER_TIMEOUT`      | ❌ No     | `2.0`       | Timeout en segundos (float)           |
| `CENTRAL_LOGGER_THRESHOLD`    | ❌ No     | `critical`  | Nivel mínimo a enviar                 |

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

## 🔧 Troubleshooting Rápido

### Problema: Los logs no llegan

**Solución 1**: Verificar variables de entorno
```php
var_dump(env('CENTRAL_LOGGER_API_URL')); // Debe mostrar la URL
```

**Solución 2**: Revisar logs locales
```bash
tail -f writable/logs/log-$(date +%Y-%m-%d).log
```
Buscar mensajes como: `CentralLogHandler falló al enviar log`

**Solución 3**: Verificar que el handler está registrado
```php
var_dump(config('Logger')->handlers);
```
Debe aparecer `CentralLogHandler::class` en la lista.

### Problema: Timeout muy largo

Reducir el timeout en `.env`:
```env
CENTRAL_LOGGER_TIMEOUT=1.0  # Más rápido (1 segundo)
```

### Problema: Demasiados logs se envían

Hacer el threshold más restrictivo:
```env
CENTRAL_LOGGER_THRESHOLD=emergency  # Solo emergencias
```

### Problema: Error SSL en desarrollo local

Opción A (recomendado): Configurar certificado válido en el servicio central

Opción B (temporal): Desactivar verificación SSL
```php
// En src/Handlers/CentralLogHandler.php
$client = Services::curlrequest([
    'timeout' => $this->config->timeout,
    'verify'  => false, // SOLO PARA DESARROLLO
]);
```

---

## 🧪 Testing Express

### Test Básico con Webhook.site

1. Ir a [webhook.site](https://webhook.site)
2. Copiar tu URL única (ej: `https://webhook.site/abc-123`)
3. Configurar en `.env`:
   ```env
   CENTRAL_LOGGER_API_URL=https://webhook.site/abc-123
   CENTRAL_LOGGER_API_KEY=test
   CENTRAL_LOGGER_APP_NAME=test-app
   CENTRAL_LOGGER_THRESHOLD=debug
   ```
4. Enviar log de prueba:
   ```php
   log_message('critical', 'Prueba exitosa!');
   ```
5. Verificar en webhook.site que llegó el JSON

### Test con Controlador de Prueba

1. Copiar `vendor/tu-organizacion/central-logger-ci4/examples/TestCentralLogger.php` a `app/Controllers/`
2. Copiar `vendor/tu-organizacion/central-logger-ci4/examples/test_central_logger.php` a `app/Views/`
3. Acceder a `https://tu-app.local/test-central-logger`
4. Ver resultados en pantalla
5. **IMPORTANTE**: Eliminar ambos archivos después de probar

---

## 📦 Instalación Masiva (25 apps)

### Script Bash para Instalación Automatizada

```bash
#!/bin/bash

APPS=(
    "/var/www/app-facturacion"
    "/var/www/app-inventario"
    "/var/www/app-crm"
    # ... agregar las 25 rutas
)

for APP in "${APPS[@]}"; do
    echo "📦 Instalando en $(basename $APP)..."
    cd "$APP"
    
    # Instalar paquete
    composer require tu-organizacion/central-logger-ci4 --no-interaction
    
    # Agregar configuración al .env
    if ! grep -q "CENTRAL_LOGGER_API_URL" .env; then
        cat >> .env << 'EOF'

# Central Logger Configuration
CENTRAL_LOGGER_API_URL=https://api-logs.tudominio.com/api/logs
CENTRAL_LOGGER_API_KEY=tu_token_secreto
CENTRAL_LOGGER_APP_NAME=$(basename $APP)
CENTRAL_LOGGER_ENVIRONMENT=${CI_ENVIRONMENT}
CENTRAL_LOGGER_TIMEOUT=2.0
CENTRAL_LOGGER_THRESHOLD=critical
EOF
        echo "✅ Configuración agregada"
    fi
done

echo "🎉 Instalación completada en ${#APPS[@]} aplicaciones"
```

---

## 🔑 Comandos Útiles

### Verificar instalación
```bash
composer show tu-organizacion/central-logger-ci4
```

### Actualizar a última versión
```bash
composer update tu-organizacion/central-logger-ci4
```

### Ver logs locales en tiempo real
```bash
tail -f writable/logs/log-*.log
```

### Generar API Key segura
```bash
openssl rand -hex 32
```

### Buscar logs de una app específica (en el servidor central)
```sql
SELECT * FROM centralized_logs 
WHERE app_name = 'app-facturacion' 
ORDER BY timestamp DESC 
LIMIT 50;
```

---

## 📚 Documentación Completa

- **README.md** - Documentación principal y referencia completa
- **INTEGRATION_GUIDE.md** - Guía paso a paso de integración
- **PUBLISHING_GUIDE.md** - Cómo publicar y versionar el paquete
- **API_RECEIVER_EXAMPLE.md** - Ejemplo del servicio receptor
- **EXECUTIVE_SUMMARY.md** - Resumen ejecutivo para stakeholders
- **PACKAGE_OVERVIEW.md** - Visión técnica completa del paquete

---

## 💡 Tips y Mejores Prácticas

### ✅ DO (Hacer)

- ✅ Usar nombres únicos para `CENTRAL_LOGGER_APP_NAME`
- ✅ Mantener threshold en `critical` en producción
- ✅ Revisar logs centralizados diariamente
- ✅ Configurar alertas para logs de tipo `emergency`
- ✅ Probar en staging antes de producción

### ❌ DON'T (No hacer)

- ❌ Loguear datos sensibles (passwords, tokens, tarjetas de crédito)
- ❌ Aumentar timeout más allá de 5 segundos
- ❌ Usar threshold `debug` en producción (demasiados logs)
- ❌ Compartir la API Key públicamente
- ❌ Enviar logs con información de usuarios sin consentimiento

### 🎯 Logs Efectivos

**Malo**:
```php
log_message('critical', 'Error');
```

**Bueno**:
```php
log_message('critical', 'Error de conexión a base de datos: timeout después de 30s');
```

**Excelente**:
```php
log_message('critical', sprintf(
    'Error de conexión a base de datos [%s]: %s (timeout: %ds, host: %s)',
    $exception->getCode(),
    $exception->getMessage(),
    $timeout,
    $dbHost
));
```

---

## ⚡ Resumen Ultra-Rápido

```bash
# 1. Instalar
composer require tu-organizacion/central-logger-ci4

# 2. Configurar .env
echo "CENTRAL_LOGGER_API_URL=https://api-logs.tudominio.com/api/logs" >> .env
echo "CENTRAL_LOGGER_API_KEY=tu_token" >> .env
echo "CENTRAL_LOGGER_APP_NAME=nombre-app" >> .env

# 3. Editar app/Config/Logger.php
# Agregar: CentralLogHandler::class => ['handles' => ['critical', 'alert', 'emergency']]

# 4. Probar
# log_message('critical', 'Test OK');
```

**¡Listo en 5 minutos!**

---

**Documentación actualizada**: Octubre 2, 2026  
**Versión del paquete**: 1.0.0  
**Soporte**: https://github.com/tu-organizacion/central-logger-ci4/issues
