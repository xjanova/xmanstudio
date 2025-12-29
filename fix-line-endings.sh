#!/usr/bin/env bash

#########################################################
# Fix Line Endings for All Scripts
# Converts CRLF to LF
#########################################################

echo "Fixing line endings for all shell scripts..."

# List of scripts to fix
SCRIPTS=(
    "install.sh"
    "quick-install.sh"
    "deploy.sh"
    "clear-cache.sh"
    "fix-permissions.sh"
    "run-migrations.sh"
    "rollback.sh"
)

# Check if dos2unix is available
if command -v dos2unix >/dev/null 2>&1; then
    echo "Using dos2unix..."
    for script in "${SCRIPTS[@]}"; do
        if [ -f "$script" ]; then
            dos2unix "$script" 2>/dev/null
            echo "✓ Fixed: $script"
        fi
    done
else
    echo "Using sed (dos2unix not found)..."
    for script in "${SCRIPTS[@]}"; do
        if [ -f "$script" ]; then
            sed -i 's/\r$//' "$script"
            echo "✓ Fixed: $script"
        fi
    done
fi

# Make all scripts executable
echo ""
echo "Setting executable permissions..."
chmod +x "${SCRIPTS[@]}" 2>/dev/null

echo ""
echo "✅ All scripts fixed and ready to use!"
echo ""
echo "You can now run:"
echo "  ./install.sh"
echo "  ./quick-install.sh"
echo "  ./deploy.sh"
