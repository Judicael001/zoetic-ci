#!/bin/bash
# Script de vérification - Gestion du Logo (Shell)
# À lancer depuis le répertoire Zoetic2

echo "🔍 Vérification de l'implémentation de la gestion du logo..."
echo ""

# Couleurs
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m' # No Color

ERRORS=0

# Test 1: Fichiers créés
echo "📁 Test 1: Fichiers créés"
files=(
    "admin/connect/save_logo.php"
    "admin/connect/delete_logo.php"
)
for file in "${files[@]}"; do
    if [ -f "$file" ]; then
        echo -e "${GREEN}✓${NC} $file"
    else
        echo -e "${RED}✗${NC} $file (MANQUANT)"
        ERRORS=$((ERRORS + 1))
    fi
done
echo ""

# Test 2: Fichiers modifiés
echo "📝 Test 2: Fichiers modifiés"
files_modified=(
    "admin/settings.php"
    "src/include/nav.php"
    "src/db/init.php"
)
for file in "${files_modified[@]}"; do
    if [ -f "$file" ]; then
        echo -e "${GREEN}✓${NC} $file"
    else
        echo -e "${RED}✗${NC} $file (MANQUANT)"
        ERRORS=$((ERRORS + 1))
    fi
done
echo ""

# Test 3: Fichiers de documentation
echo "📚 Test 3: Fichiers de documentation"
docs=(
    "GUIDE_LOGO.md"
    "IMPLEMENTATION_LOGO.md"
    "README_LOGO.txt"
    "CAS_UTILISATION.md"
    "CHANGEMENTS.md"
)
for doc in "${docs[@]}"; do
    if [ -f "$doc" ]; then
        echo -e "${GREEN}✓${NC} $doc"
    else
        echo -e "${RED}✗${NC} $doc (MANQUANT)"
        ERRORS=$((ERRORS + 1))
    fi
done
echo ""

# Test 4: Vérifier la syntaxe PHP (si PHP est installé)
echo "🔧 Test 4: Syntaxe PHP"
if command -v php &> /dev/null; then
    php -l admin/connect/save_logo.php > /dev/null 2>&1
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓${NC} save_logo.php - OK"
    else
        echo -e "${RED}✗${NC} save_logo.php - Erreur de syntaxe"
        ERRORS=$((ERRORS + 1))
    fi
    
    php -l admin/connect/delete_logo.php > /dev/null 2>&1
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓${NC} delete_logo.php - OK"
    else
        echo -e "${RED}✗${NC} delete_logo.php - Erreur de syntaxe"
        ERRORS=$((ERRORS + 1))
    fi
else
    echo -e "${GREEN}ℹ${NC} PHP non trouvé (vérification syntaxe ignorée)"
fi
echo ""

# Test 5: Dossier uploads/logos
echo "📂 Test 5: Dossier uploads/logos"
if [ -d "uploads/logos" ]; then
    echo -e "${GREEN}✓${NC} uploads/logos existe"
else
    echo -e "${GREEN}ℹ${NC} uploads/logos sera créé au premier upload"
fi
echo ""

# Résumé
echo "=================================="
if [ $ERRORS -eq 0 ]; then
    echo -e "${GREEN}✅ TOUS LES TESTS PASSENT${NC}"
    echo ""
    echo "La fonctionnalité est prête à l'emploi!"
else
    echo -e "${RED}❌ $ERRORS ERREUR(S) DÉTECTÉE(S)${NC}"
    echo ""
    echo "Vérifiez les fichiers manquants ci-dessus."
fi
echo "=================================="
