# Guía de Publicación del Paquete

Esta guía te ayudará a publicar el paquete `central-logger-ci4` en tu repositorio Git y hacerlo disponible para instalación vía Composer.

---

## 📦 Opción 0: Packagist público (recomendada)

Es la vía que permite instalar el paquete con un único comando, sin declarar repositorios extra:

```bash
composer require sverguecio/central-logger-ci4:^1.1
```

### 1. Publicar el tag y el GitHub Release

```bash
git checkout main
git pull origin main

git tag -a v1.1.0 -m "Release v1.1.0 - Reintentos, cola local y contexto extendido"
git push origin v1.1.0

# Release de GitHub con las notas del CHANGELOG
gh release create v1.1.0 --title "v1.1.0" --notes-from-tag
```

Packagist deriva las versiones de los tags de Git: sin un tag `v1.1.0`, la restricción `^1.1` no
resuelve a nada.

### 2. Registrar el paquete en Packagist

1. Iniciar sesión en [packagist.org](https://packagist.org/login/github) con la cuenta de GitHub.
2. Ir a [Submit](https://packagist.org/packages/submit) y pegar
   `https://github.com/sverguecio/central-logger-ci4`.
3. Confirmar. Packagist leerá `composer.json` y tomará el nombre `sverguecio/central-logger-ci4`
   (debe coincidir con el `vendor` de la cuenta o Packagist pedirá confirmar la propiedad).

### 3. Activar la actualización automática

En **Packagist → el paquete → Settings** se obtiene el token de API. Con GitHub basta con instalar
la [integración de Packagist](https://packagist.org/profile/) (`Enable GitHub Hook`) para que cada
push de tag actualice el paquete. Si no, hay que pulsar **Update** manualmente tras cada release.

### 4. Verificar

```bash
composer show sverguecio/central-logger-ci4 --all
composer require sverguecio/central-logger-ci4:^1.1 --dry-run
```

---

## 📦 Opción 1: Repositorio Privado con VCS

Si tu organización tiene un repositorio Git privado (GitHub, GitLab, Bitbucket):

### 1. Inicializar Git y Publicar

```bash
cd central-logger-ci4/

# Inicializar repositorio
git init

# Agregar todos los archivos
git add .

# Primer commit
git commit -m "Initial release v1.0.0 - Central Logger CI4"

# Agregar remote (reemplaza con tu URL)
git remote add origin git@github.com:sverguecio/central-logger-ci4.git

# Push inicial
git push -u origin main
```

### 2. Crear Tag de Versión

```bash
# Crear tag para la versión 1.0.0
git tag -a v1.0.0 -m "Release v1.0.0 - Primera versión estable"

# Push del tag
git push origin v1.0.0
```

### 3. Configurar Composer en las 25 Aplicaciones

Edita el `composer.json` de cada aplicación y agrega:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:sverguecio/central-logger-ci4.git"
        }
    ],
    "require": {
        "sverguecio/central-logger-ci4": "^1.0"
    }
}
```

### 4. Instalar en cada aplicación

```bash
composer install
```

---

## 📦 Opción 2: Repositorio Privado con Satis (Recommended)

Satis es un generador de repositorios Composer estáticos. Ideal para organizaciones con múltiples paquetes privados.

### 1. Instalar Satis

```bash
composer create-project composer/satis --stability=dev --keep-vcs
cd satis
```

### 2. Configurar `satis.json`

```json
{
    "name": "Private Packages",
    "homepage": "https://packages.example.com",
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:sverguecio/central-logger-ci4.git"
        }
    ],
    "require-all": true,
    "archive": {
        "directory": "dist",
        "format": "tar",
        "prefix-url": "https://packages.example.com"
    }
}
```

### 3. Generar el Repositorio

```bash
php bin/satis build satis.json public/
```

### 4. Configurar en las Aplicaciones

En cada aplicación, edita `composer.json`:

```json
{
    "repositories": [
        {
            "type": "composer",
            "url": "https://packages.example.com"
        }
    ],
    "require": {
        "sverguecio/central-logger-ci4": "^1.0"
    }
}
```

---

## 📦 Opción 3: Path Local (Solo para Testing)

Para probar localmente antes de publicar:

### En el directorio de desarrollo

```bash
# Clonar o copiar el paquete
cd /var/www/packages/
git clone git@github.com:sverguecio/central-logger-ci4.git
```

### En cada aplicación de prueba

Edita `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "/var/www/packages/central-logger-ci4"
        }
    ],
    "require": {
        "sverguecio/central-logger-ci4": "@dev"
    }
}
```

Instalar:

```bash
composer install
```

---

## 🔄 Workflow de Actualizaciones

### 1. Hacer Cambios en el Paquete

```bash
cd central-logger-ci4/

# Hacer cambios...
git add .
git commit -m "Fix: Corregir timeout en peticiones"

# Push
git push origin main
```

### 2. Crear Nueva Versión

```bash
# Para bug fixes (1.0.0 -> 1.0.1)
git tag -a v1.0.1 -m "Fix: Timeout configuration"
git push origin v1.0.1

# Para nuevas features (1.0.0 -> 1.1.0)
git tag -a v1.1.0 -m "Feature: Añadir soporte para custom headers"
git push origin v1.1.0

# Para breaking changes (1.0.0 -> 2.0.0)
git tag -a v2.0.0 -m "Breaking: Cambio en estructura de configuración"
git push origin v2.0.0
```

### 3. Actualizar en las Aplicaciones

```bash
cd /var/www/app-facturacion/
composer update sverguecio/central-logger-ci4

# O para actualizar todo
composer update
```

---

## 🏷️ Convención de Versionado Semántico

Usa [Semantic Versioning](https://semver.org/lang/es/):

- **MAJOR** (X.0.0): Cambios incompatibles con versiones anteriores
- **MINOR** (1.X.0): Nueva funcionalidad compatible con versiones anteriores
- **PATCH** (1.0.X): Corrección de bugs compatible con versiones anteriores

### Ejemplos

```bash
# Bug fix: 1.0.0 -> 1.0.1
git tag -a v1.0.1 -m "Fix: Corregir encoding UTF-8 en mensajes"

# Nueva feature: 1.0.1 -> 1.1.0
git tag -a v1.1.0 -m "Feature: Añadir soporte para filtros personalizados"

# Breaking change: 1.1.0 -> 2.0.0
git tag -a v2.0.0 -m "Breaking: Cambiar namespace de TuOrganizacion a MiEmpresa"
```

---

## 🔒 Autenticación para Repositorios Privados

### GitHub con Token de Acceso Personal

```bash
# Configurar auth.json global
composer config -g github-oauth.github.com TU_TOKEN_GITHUB_AQUI
```

### GitLab con Deploy Token

```bash
composer config -g gitlab-token.gitlab.com TU_TOKEN_GITLAB_AQUI
```

### SSH Keys (Recomendado)

```bash
# Generar SSH key si no tienes una
ssh-keygen -t ed25519 -C "deploy@example.com"

# Agregar la clave pública a GitHub/GitLab
cat ~/.ssh/id_ed25519.pub
```

---

## 📋 Checklist de Publicación

Antes de publicar la versión 1.1.0:

- [ ] Verificar que todos los archivos están en el repositorio
- [ ] Actualizar `CHANGELOG.md` con cambios de la versión
- [ ] Verificar que `composer.json` tiene los datos correctos
- [ ] Ejecutar `composer validate --strict` y `./validate.sh`
- [ ] Probar instalación en una aplicación de prueba
- [ ] Crear tag v1.1.0
- [ ] Push del tag
- [ ] Crear el GitHub Release con las notas del CHANGELOG
- [ ] Registrar o actualizar el paquete en Packagist

---

## 🚀 Script de Publicación Automatizada

Guarda este script como `release.sh`:

```bash
#!/bin/bash

# Script de Release para Central Logger CI4
# Uso: ./release.sh 1.0.1 "Fix: Corregir timeout"

VERSION=$1
MESSAGE=$2

if [ -z "$VERSION" ] || [ -z "$MESSAGE" ]; then
    echo "Uso: ./release.sh VERSION \"MENSAJE\""
    echo "Ejemplo: ./release.sh 1.0.1 \"Fix: Corregir timeout\""
    exit 1
fi

# Validar formato de versión
if ! [[ $VERSION =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo "❌ Formato de versión inválido. Usa X.Y.Z (ejemplo: 1.0.1)"
    exit 1
fi

echo "=========================================="
echo "🚀 Releasing version $VERSION"
echo "=========================================="

# 1. Verificar que estamos en main
BRANCH=$(git rev-parse --abbrev-ref HEAD)
if [ "$BRANCH" != "main" ]; then
    echo "❌ Debes estar en la rama 'main' para hacer release"
    exit 1
fi

# 2. Verificar que no hay cambios sin commitear
if ! git diff-index --quiet HEAD --; then
    echo "❌ Tienes cambios sin commitear. Haz commit primero."
    exit 1
fi

# 3. Pull últimos cambios
echo "📥 Pulling latest changes..."
git pull origin main

# 4. Actualizar CHANGELOG.md
echo "📝 Updating CHANGELOG.md..."
DATE=$(date +%Y-%m-%d)
sed -i "s/## \[Unreleased\]/## [Unreleased]\n\n## [$VERSION] - $DATE\n\n### $MESSAGE/" CHANGELOG.md

# 5. Commit del changelog
git add CHANGELOG.md
git commit -m "chore: Release v$VERSION"

# 6. Crear tag
echo "🏷️  Creating tag v$VERSION..."
git tag -a "v$VERSION" -m "$MESSAGE"

# 7. Push
echo "📤 Pushing to origin..."
git push origin main
git push origin "v$VERSION"

echo ""
echo "=========================================="
echo "✅ Release v$VERSION publicado correctamente"
echo "=========================================="
echo ""
echo "Tag: v$VERSION"
echo "Mensaje: $MESSAGE"
echo ""
echo "📦 Ahora puedes instalar con:"
echo "   composer require sverguecio/central-logger-ci4:^$VERSION"
```

Uso:

```bash
chmod +x release.sh
./release.sh 1.0.1 "Fix: Corregir timeout en peticiones HTTP"
```

---

## 📊 Monitoreo de Uso

Para saber cuántas aplicaciones están usando el paquete:

### En GitHub

- Ve a "Insights" → "Traffic" → "Referring sites"
- Revisa los clones del repositorio

### En Packagist/Satis

- Revisa los logs de descarga
- Implementa analytics en el endpoint del repositorio

---

¡Listo! Tu paquete está listo para ser publicado y usado en las 25 aplicaciones CodeIgniter 4.
