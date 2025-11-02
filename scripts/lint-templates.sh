#!/bin/bash
#
# Template Security Linting Script
# Finds potentially unsafe output in .phtml templates
#
# Phase 2.3: Output Escaping Audit
#

set -e

# Colors for output
RED='\033[0;31m'
YELLOW='\033[1;33m'
GREEN='\033[0;32m'
NC='\033[0m' # No Color

echo "🔍 Scanning templates for potential XSS vulnerabilities..."
echo ""

# Find all .phtml files
TEMPLATE_DIR="app"
TOTAL=0
ISSUES=0

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  Looking for unescaped output: <?= \$variable ?>"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Find potentially unsafe <?= $variable ?> patterns
# Exclude those that use escapeHtml, escapeHtmlAttr, escapeUrl, or escapeJs
UNSAFE=$(grep -rn "<?=\s*\$" $TEMPLATE_DIR --include="*.phtml" | \
  grep -v "escapeHtml\|escapeHtmlAttr\|escapeUrl\|escapeJs\|escapeCss" || true)

if [ -n "$UNSAFE" ]; then
    echo -e "${RED}⚠️  Found potentially unescaped output:${NC}"
    echo ""
    echo "$UNSAFE" | while IFS= read -r line; do
        ISSUES=$((ISSUES + 1))
        echo "  $line"
    done
    echo ""
else
    echo -e "${GREEN}✓ No unescaped variables found${NC}"
    echo ""
fi

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  Looking for potentially dangerous URLs"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Find href/src attributes that may contain unescaped variables
UNSAFE_URLS=$(grep -rn "href=.*\$\|src=.*\$" $TEMPLATE_DIR --include="*.phtml" | \
  grep -v "escapeUrl" || true)

if [ -n "$UNSAFE_URLS" ]; then
    echo -e "${YELLOW}⚠️  Found URLs that might need escapeUrl():${NC}"
    echo ""
    echo "$UNSAFE_URLS" | while IFS= read -r line; do
        echo "  $line"
    done
    echo ""
else
    echo -e "${GREEN}✓ All URLs appear to be properly escaped${NC}"
    echo ""
fi

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  Looking for inline JavaScript with unescaped data"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Find <script> tags with PHP variables
UNSAFE_JS=$(grep -rn "<script.*\$" $TEMPLATE_DIR --include="*.phtml" | \
  grep -v "escapeJs\|json_encode" || true)

if [ -n "$UNSAFE_JS" ]; then
    echo -e "${YELLOW}⚠️  Found JavaScript with potentially unsafe data:${NC}"
    echo ""
    echo "$UNSAFE_JS" | while IFS= read -r line; do
        echo "  $line"
    done
    echo ""
else
    echo -e "${GREEN}✓ JavaScript contexts appear safe${NC}"
    echo ""
fi

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  Summary"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Count total templates
TOTAL=$(find $TEMPLATE_DIR -name "*.phtml" | wc -l)

echo "Templates scanned: $TOTAL"
echo ""

if [ -n "$UNSAFE" ] || [ -n "$UNSAFE_URLS" ] || [ -n "$UNSAFE_JS" ]; then
    echo -e "${YELLOW}⚠️  Please review the findings above${NC}"
    echo ""
    echo "Best Practices:"
    echo "  • Use escapeHtml() for general content"
    echo "  • Use escapeHtmlAttr() for HTML attributes (title, alt, etc.)"
    echo "  • Use escapeUrl() for href and src attributes"
    echo "  • Use escapeJs() for JavaScript data"
    echo "  • Use escapeCss() for inline CSS"
    echo ""
    echo "Example:"
    echo '  <h1><?= $block->escapeHtml($page->getTitle()) ?></h1>'
    echo '  <a href="<?= $block->escapeUrl($url) ?>" title="<?= $block->escapeHtmlAttr($title) ?>">Link</a>'
    echo '  <script>var data = <?= $block->escapeJs($data) ?>;</script>'
    echo ""
    exit 1
else
    echo -e "${GREEN}✓ All templates appear to be properly escaped!${NC}"
    echo ""
    exit 0
fi
