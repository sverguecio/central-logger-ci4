# Central Logger CI4 - Resumen del Paquete

## 📦 Estructura del Paquete

```
central-logger-ci4/
│
├── 📄 composer.json              # Configuración del paquete Composer
├── 📄 LICENSE                    # Licencia MIT
├── 📄 README.md                  # Documentación principal
├── 📄 CHANGELOG.md               # Historial de versiones
├── 📄 INTEGRATION_GUIDE.md       # Guía de integración paso a paso
├── 📄 PUBLISHING_GUIDE.md        # Guía para publicar el paquete
├── 📄 API_RECEIVER_EXAMPLE.md    # Ejemplo del servicio receptor
├── 📄 .gitignore                 # Archivos ignorados por Git
├── 📄 .env.example               # Ejemplo de variables de entorno
├── 📄 phpunit.xml.dist           # Configuración de PHPUnit
│
├── 📁 src/                       # Código fuente del paquete
│   ├── 📁 Config/
│   │   └── CentralLogger.php     # Clase de configuración
│   └── 📁 Handlers/
│       └── CentralLogHandler.php # Handler principal de logs
│
└── 📁 examples/                  # Archivos de ejemplo
    ├── Logger.php                # Ejemplo de configuración Logger.php
    ├── TestCentralLogger.php     # Controlador de prueba
    └── test_central_logger.php   # Vista de prueba
```

---

## 🎯 Componentes Principales

### 1. **CentralLogger.php** (Configuración)

**Ubicación**: `src/Config/CentralLogger.php`

**Responsabilidad**: 
- Gestionar la configuración del logger centralizado
- Leer variables de entorno
- Validar configuración
- Determinar qué niveles de log enviar

**Propiedades**:
```php
$apiUrl       // URL del servicio centralizado
$apiKey       // Token de autenticación
$appName      // Identificador de la aplicación
$environment  // Entorno (production, staging, etc.)
$timeout      // Timeout de peticiones HTTP
$threshold    // Nivel mínimo de log a enviar
```

**Métodos Clave**:
- `shouldHandle(string $level): bool` - Verifica si un nivel debe ser manejado
- `isValid(): bool` - Valida que la configuración esté completa

---

### 2. **CentralLogHandler.php** (Handler)

**Ubicación**: `src/Handlers/CentralLogHandler.php`

**Responsabilidad**:
- Implementar `HandlerInterface` de CodeIgniter 4
- Interceptar logs de la aplicación
- Construir payload estructurado
- Enviar logs al servicio centralizado vía HTTP POST
- Manejar errores sin interrumpir la aplicación

**Métodos Clave**:
- `handle(string $level, string $message): bool` - Procesa un evento de log
- `buildPayload(string $level, string $message): array` - Construye el payload JSON
- `sendToApi(array $payload): bool` - Envía la petición HTTP
- `canHandle(string $level): bool` - Determina si puede manejar el nivel

**Características de Seguridad**:
- ✅ Try/catch global para capturar cualquier error
- ✅ Timeout corto (2s) para no bloquear la app
- ✅ Retorna false en lugar de lanzar excepciones
- ✅ Verificación SSL en producción

---

## 🔄 Flujo de Funcionamiento

```
┌─────────────────────────────────────────────────────────────┐
│  Aplicación CodeIgniter 4                                   │
│                                                             │
│  log_message('critical', 'Error de base de datos')         │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│  Logger de CodeIgniter (app/Config/Logger.php)              │
│                                                             │
│  Ejecuta todos los handlers registrados                    │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ├─────► FileHandler (logs locales)
                     │
                     ├─────► CentralLogHandler
                     │              │
                     │              ▼
                     │       ┌─────────────────────────┐
                     │       │ ¿Nivel >= threshold?    │
                     │       └──────────┬──────────────┘
                     │                  │ Sí
                     │                  ▼
                     │       ┌─────────────────────────┐
                     │       │ Construir payload JSON  │
                     │       └──────────┬──────────────┘
                     │                  │
                     │                  ▼
                     │       ┌─────────────────────────────────┐
                     │       │ CURLRequest::post()             │
                     │       │                                 │
                     │       │ POST /api/logs                  │
                     │       │ Headers: X-Api-Key, JSON        │
                     │       │ Timeout: 2s                     │
                     │       └──────────┬──────────────────────┘
                     │                  │
                     │                  ▼
                     │       ┌─────────────────────────────────┐
                     │       │ Servicio Centralizado de Logs   │
                     │       │                                 │
                     │       │ - Validar API Key               │
                     │       │ - Guardar en base de datos      │
                     │       │ - Enviar alertas si es crítico  │
                     │       └─────────────────────────────────┘
                     │
                     ▼
           App continúa normalmente
```

---

## 🔐 Variables de Entorno

```env
# Requeridas
CENTRAL_LOGGER_API_URL=https://api-logs.tudominio.com/api/logs
CENTRAL_LOGGER_API_KEY=tu_token_secreto
CENTRAL_LOGGER_APP_NAME=app-facturacion

# Opcionales (con valores por defecto)
CENTRAL_LOGGER_ENVIRONMENT=production
CENTRAL_LOGGER_TIMEOUT=2.0
CENTRAL_LOGGER_THRESHOLD=critical
```

---

## 📊 Payload Enviado

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

## ✅ Checklist de Integración

Para cada aplicación:

1. **Instalación**
   ```bash
   composer require sverguecio/central-logger-ci4
   ```

2. **Configuración de .env**
   ```env
   CENTRAL_LOGGER_API_URL=https://...
   CENTRAL_LOGGER_API_KEY=...
   CENTRAL_LOGGER_APP_NAME=nombre-unico
   ```

3. **Registrar Handler** en `app/Config/Logger.php`
   ```php
   CentralLogHandler::class => [
       'handles' => ['critical', 'alert', 'emergency'],
   ],
   ```

4. **Probar**
   - Usar el controlador de prueba `TestCentralLogger.php`
   - Verificar en el servicio central
   - Eliminar archivos de prueba

---

## 🧪 Testing

### Prueba Manual Rápida

```php
// En cualquier controlador
log_message('critical', 'Test desde ' . env('CENTRAL_LOGGER_APP_NAME'));
```

### Prueba con Webhook.site

1. Ir a [webhook.site](https://webhook.site)
2. Copiar la URL única
3. Configurar en `.env`:
   ```env
   CENTRAL_LOGGER_API_URL=https://webhook.site/tu-uuid
   ```
4. Enviar log de prueba
5. Verificar en webhook.site

---

## 📈 Ventajas del Paquete

### ✅ Zero Dependencias
- Solo usa componentes nativos de CodeIgniter 4
- No requiere Guzzle, Monolog, ni otras librerías

### ✅ Fail-Safe
- Nunca interrumpe la aplicación cliente
- Try/catch global captura cualquier error
- Timeout corto evita bloqueos

### ✅ Configurable
- Variables de entorno para cada aplicación
- Threshold ajustable por nivel de log
- Timeout personalizable

### ✅ Rico en Contexto
- Envía IP, URI, user agent, método HTTP
- Incluye nombre del servidor
- Timestamp en formato ISO 8601

### ✅ Fácil Integración
- Instalación vía Composer
- 3 pasos para integrar
- Guías detalladas incluidas

---

## 🚀 Roadmap Futuro (Opcional)

### v1.1.0 - Features Adicionales
- [ ] Soporte para múltiples endpoints (failover)
- [ ] Queue asíncrona para alto volumen
- [ ] Filtros personalizados de mensajes
- [ ] Soporte para contexto adicional (user_id, session_id)

### v1.2.0 - Monitoreo
- [ ] Dashboard integrado para ver logs enviados
- [ ] Métricas de rendimiento (tiempo de respuesta)
- [ ] Alertas cuando el servicio central falla

### v2.0.0 - Breaking Changes
- [ ] Migrar a PSR-3 Logger Interface
- [ ] Soporte para CodeIgniter 5
- [ ] Batch sending (agrupar múltiples logs)

---

## 📞 Soporte y Contribución

### Reportar Issues
1. Ir a GitHub Issues
2. Usar template de issue
3. Incluir versiones de PHP y CodeIgniter

### Contribuir
1. Fork del repositorio
2. Crear rama feature/nombre-feature
3. Hacer cambios
4. Crear Pull Request

---

## 📝 Licencia

MIT License - Libre para uso comercial y privado.

---

## 🎉 ¡Listo para Usar!

El paquete está completamente funcional y listo para ser instalado en tus 25 aplicaciones CodeIgniter 4.

**Próximo paso**: Ver `INTEGRATION_GUIDE.md` para comenzar la instalación masiva.
