#!/bin/bash

# Script de validación para Central Logger CI4
# Verifica que todos los componentes del paquete estén correctos

set -e

PACKAGE_NAME="sverguecio/central-logger-ci4"
EXPECTED_PHP=">=8.0"
EXPECTED_VERSION="1.1.0"

echo "=========================================="
echo "🔍 Validando paquete Central Logger CI4"
echo "   Paquete: $PACKAGE_NAME"
echo "   Versión esperada en CHANGELOG: $EXPECTED_VERSION"
echo "=========================================="
echo ""

ERRORS=0

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Función para mensajes de error
error() {
    echo -e "${RED}❌ ERROR: $1${NC}"
    ERRORS=$((ERRORS + 1))
}

# Función para mensajes de éxito
success() {
    echo -e "${GREEN}✅ $1${NC}"
}

# Función para mensajes de advertencia
warning() {
    echo -e "${YELLOW}⚠️  WARNING: $1${NC}"
}

echo "1. Verificando estructura de archivos..."
echo "----------------------------------------"

# Archivos requeridos
REQUIRED_FILES=(
    "composer.json"
    "README.md"
    "LICENSE"
    "CHANGELOG.md"
    ".gitignore"
    "src/Config/CentralLogger.php"
    "src/Handlers/CentralLogHandler.php"
)

for file in "${REQUIRED_FILES[@]}"; do
    if [ -f "$file" ]; then
        success "Archivo encontrado: $file"
    else
        error "Archivo faltante: $file"
    fi
done

echo ""
echo "2. Verificando sintaxis PHP..."
echo "----------------------------------------"

# Verificar sintaxis de archivos PHP (solo si PHP está disponible)
if command -v php &> /dev/null; then
    while IFS= read -r file; do
        if php -l "$file" > /dev/null 2>&1; then
            success "Sintaxis correcta: $file"
        else
            error "Sintaxis incorrecta: $file"
        fi
    done < <(find src -type f -name '*.php' | sort)
else
    warning "PHP no disponible - omitiendo validación de sintaxis"
fi

echo ""
echo "3. Verificando composer.json..."
echo "----------------------------------------"

# Verificar que composer.json es válido (solo si composer está disponible)
if command -v composer &> /dev/null; then
    if composer validate --strict > /dev/null 2>&1; then
        success "composer.json es válido (composer validate --strict)"
    else
        error "composer.json tiene errores"
        composer validate --strict 2>&1 | sed 's/^/    /'
    fi
else
    warning "Composer no disponible - omitiendo validación de composer.json"
fi

# Verificar campos importantes en composer.json
if grep -q "\"name\": \"$PACKAGE_NAME\"" composer.json; then
    success "Campo 'name' es '$PACKAGE_NAME'"
else
    error "Campo 'name' incorrecto o faltante en composer.json (se esperaba '$PACKAGE_NAME')"
fi

if grep -q '"type": "library"' composer.json; then
    success "Campo 'type' es 'library'"
else
    warning "Campo 'type' debería ser 'library'"
fi

if grep -q "\"php\": \"$EXPECTED_PHP\"" composer.json; then
    success "Requiere PHP $EXPECTED_PHP"
else
    error "Requisito de PHP inconsistente en composer.json (se esperaba '$EXPECTED_PHP')"
fi

# El badge del README debe coincidir con el requisito de composer.json
if grep -q "badge/php-%3E%3D${EXPECTED_PHP#>=}-" README.md; then
    success "Badge de PHP en README.md coincide con composer.json"
else
    error "Badge de PHP en README.md no coincide con $EXPECTED_PHP"
fi

if grep -q "PHP: ${EXPECTED_PHP/>=/>= }" README.md; then
    success "Sección de compatibilidad de README.md coincide con composer.json"
else
    error "Sección de compatibilidad de README.md no coincide con $EXPECTED_PHP"
fi

if grep -q '"codeigniter4/framework": "\^4.0"' composer.json; then
    success "Requiere CodeIgniter 4"
else
    error "Dependencia de CodeIgniter 4 faltante"
fi

echo ""
echo "4. Verificando clases PHP..."
echo "----------------------------------------"

# Verificar que las clases existen
if grep -q "class CentralLogger extends BaseConfig" src/Config/CentralLogger.php; then
    success "Clase CentralLogger existe y extiende BaseConfig"
else
    error "Clase CentralLogger no configurada correctamente"
fi

if grep -q "class CentralLogHandler extends BaseHandler" src/Handlers/CentralLogHandler.php; then
    success "Clase CentralLogHandler existe y extiende BaseHandler"
else
    error "Clase CentralLogHandler no configurada correctamente"
fi

# Verificar método handle()
if grep -q "public function handle" src/Handlers/CentralLogHandler.php; then
    success "Método handle() existe en CentralLogHandler"
else
    error "Método handle() faltante en CentralLogHandler"
fi

echo ""
echo "5. Verificando namespace PSR-4..."
echo "----------------------------------------"

if grep -q '"TuOrganizacion\\\\CentralLogger\\\\": "src/"' composer.json; then
    success "Namespace PSR-4 configurado"
else
    error "Namespace PSR-4 no configurado correctamente"
fi

if grep -q "namespace TuOrganizacion" src/Config/CentralLogger.php; then
    success "Namespace correcto en CentralLogger.php"
else
    error "Namespace incorrecto en CentralLogger.php"
fi

if grep -q "namespace TuOrganizacion" src/Handlers/CentralLogHandler.php; then
    success "Namespace correcto en CentralLogHandler.php"
else
    error "Namespace incorrecto en CentralLogHandler.php"
fi

warning "El namespace PSR-4 sigue siendo 'TuOrganizacion\\CentralLogger\\' y no coincide con el nombre del paquete. Renombrarlo es un breaking change: queda diferido a 2.0.0 (ver CHANGELOG.md)."

echo ""
echo "6. Verificando documentación..."
echo "----------------------------------------"

# No deben quedar referencias al nombre antiguo del paquete ni al repo antiguo
STALE=$(grep -rln "tu-organizacion/central-logger-ci4" --exclude-dir=.git --exclude=validate.sh . || true)
if [ -z "$STALE" ]; then
    success "Sin referencias al nombre de paquete antiguo"
else
    error "Referencias al nombre de paquete antiguo en: $(echo "$STALE" | tr '\n' ' ')"
fi

# Verificar README.md
if [ -s README.md ]; then
    if grep -q "# Central Logger CI4" README.md; then
        success "README.md tiene título correcto"
    else
        warning "README.md sin título principal"
    fi
    
    if grep -q "## Instalación" README.md; then
        success "README.md tiene sección de instalación"
    else
        warning "README.md sin sección de instalación"
    fi
else
    error "README.md está vacío o no existe"
fi

# Verificar CHANGELOG.md
if [ -s CHANGELOG.md ]; then
    if grep -q "^## \[$EXPECTED_VERSION\]" CHANGELOG.md; then
        success "CHANGELOG.md documenta la versión $EXPECTED_VERSION"
    else
        error "CHANGELOG.md sin entrada para la versión $EXPECTED_VERSION"
    fi
else
    error "CHANGELOG.md está vacío o no existe"
fi

echo ""
echo "7. Verificando ejemplos..."
echo "----------------------------------------"

if [ -f examples/Logger.php ]; then
    success "Ejemplo Logger.php existe"
else
    warning "Ejemplo Logger.php faltante"
fi

if [ -f examples/TestCentralLogger.php ]; then
    success "Ejemplo TestCentralLogger.php existe"
else
    warning "Ejemplo TestCentralLogger.php faltante"
fi

echo ""
echo "8. Verificando configuración de seguridad..."
echo "----------------------------------------"

# Verificar que CentralLogHandler tiene try/catch
if grep -q "try {" src/Handlers/CentralLogHandler.php && grep -q "} catch" src/Handlers/CentralLogHandler.php; then
    success "CentralLogHandler tiene manejo de excepciones"
else
    error "CentralLogHandler sin try/catch"
fi

# Verificar que usa timeout
if grep -q "'timeout'" src/Handlers/CentralLogHandler.php; then
    success "CentralLogHandler configura timeout"
else
    warning "CentralLogHandler podría no tener timeout configurado"
fi

# Verificar que usa CURLRequest de CodeIgniter
if grep -q "Services::curlrequest" src/Handlers/CentralLogHandler.php; then
    success "CentralLogHandler usa CURLRequest nativo"
else
    error "CentralLogHandler no usa CURLRequest de CodeIgniter"
fi

echo ""
echo "9. Verificando .gitignore..."
echo "----------------------------------------"

if [ -f .gitignore ]; then
    if grep -q "vendor/" .gitignore; then
        success ".gitignore excluye vendor/"
    else
        error ".gitignore no excluye vendor/"
    fi

    if grep -q "^\.env$" .gitignore; then
        success ".gitignore excluye .env"
    else
        error ".gitignore no excluye .env"
    fi

    if grep -q "^composer.lock$" .gitignore; then
        success ".gitignore excluye composer.lock (correcto para una librería)"
    else
        warning ".gitignore no excluye composer.lock"
    fi
else
    error ".gitignore no existe"
fi

# El paquete no debe versionar artefactos de instalación
if git rev-parse --is-inside-work-tree > /dev/null 2>&1; then
    if git ls-files --error-unmatch composer.lock > /dev/null 2>&1; then
        warning "composer.lock está versionado; en una librería conviene no publicarlo"
    else
        success "composer.lock no está versionado"
    fi

    if [ -n "$(git ls-files vendor/)" ]; then
        error "vendor/ está versionado"
    else
        success "vendor/ no está versionado"
    fi
fi

echo ""
echo "=========================================="
if [ $ERRORS -eq 0 ]; then
    echo -e "${GREEN}✅ VALIDACIÓN EXITOSA - 0 errores${NC}"
    echo ""
    echo "El paquete está listo para ser publicado."
    exit 0
else
    echo -e "${RED}❌ VALIDACIÓN FALLIDA - $ERRORS error(es) encontrado(s)${NC}"
    echo ""
    echo "Por favor corrige los errores antes de publicar."
    exit 1
fi
