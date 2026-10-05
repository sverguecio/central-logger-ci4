# Changelog

Todos los cambios notables de este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es/1.0.0/),
y este proyecto adhiere a [Versionado Semántico](https://semver.org/lang/es/).

## [1.1.0] - 2026-10-05

### Agregado
- Reintentos automáticos con backoff progresivo al enviar logs (`CENTRAL_LOGGER_MAX_RETRIES`)
- Cola local en disco para los eventos que no pudieron entregarse, con tamaño máximo configurable
  (`CENTRAL_LOGGER_QUEUE_ENABLED`, `CENTRAL_LOGGER_QUEUE_PATH`, `CENTRAL_LOGGER_QUEUE_MAX_SIZE`)
- Método público `CentralLogHandler::flushQueue()` para reenviar manualmente los eventos encolados
- Reenvío automático de la cola tras el primer envío exitoso
- Contexto extendido en el payload: `request_id`, `php_sapi`, `file`, `line`, `class` y `function`
- Validación de configuración (`CentralLogger::isValid()`) que evita envíos con parámetros incompletos
- Método `CentralLogHandler::canHandle()` para consultar si un nivel será enviado
- Archivo `.gitignore` versionado (`vendor/`, `composer.lock`, `.env`, artefactos de test)

### Cambiado
- Nombre del paquete unificado como `sverguecio/central-logger-ci4` en documentación, ejemplos y scripts
- Requisito mínimo de PHP unificado en `>=8.0` entre `composer.json` y `README.md`
- `validate.sh` ahora valida el nombre y la versión vigentes del paquete

### Pendiente (reservado para 2.0.0)
- El namespace PSR-4 sigue siendo `TuOrganizacion\CentralLogger\`. Renombrarlo a algo coherente con
  el nombre del paquete (por ejemplo `Sverguecio\CentralLogger\`) rompe compatibilidad con el código
  que ya hace `use`, por lo que queda diferido a una versión mayor.

## [1.0.0] - 2026-10-02

### Agregado
- Handler `CentralLogHandler` que implementa `HandlerInterface` de CodeIgniter 4
- Clase de configuración `CentralLogger` con soporte para variables de entorno
- Envío de logs mediante `CURLRequest` nativo (sin dependencias externas)
- Validación de threshold configurable (niveles de log)
- Niveles críticos siempre enviados (emergency, alert, critical)
- Payload estructurado con contexto completo (IP, URI, user agent, hostname, etc.)
- Timeout configurable (default: 2 segundos)
- Protección fail-safe con try/catch para no interrumpir la aplicación
- Documentación completa en README.md
- Licencia MIT
- Autoload PSR-4 vía Composer

### Características de Seguridad
- Soporte para autenticación vía `X-Api-Key` header
- Verificación SSL en entorno de producción
- Timeout corto para evitar bloqueos

[1.1.0]: https://github.com/sverguecio/central-logger-ci4/releases/tag/v1.1.0
[1.0.0]: https://github.com/sverguecio/central-logger-ci4/releases/tag/v1.0.0
