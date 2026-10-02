# Changelog

Todos los cambios notables de este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es/1.0.0/),
y este proyecto adhiere a [Versionado Semántico](https://semver.org/lang/es/).

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

[1.0.0]: https://github.com/tu-organizacion/central-logger-ci4/releases/tag/v1.0.0
