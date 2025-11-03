#!/bin/bash

# Install Git Hooks for Infinri Framework
# This script sets up pre-commit hooks for code quality

echo "🔧 Installing Git hooks for Infinri Framework..."

# Check if we're in a git repository
if [ ! -d ".git" ]; then
    echo "❌ Error: Not in a Git repository"
    exit 1
fi

# Create hooks directory if it doesn't exist
mkdir -p .git/hooks

# Copy pre-commit hook
if [ -f ".githooks/pre-commit" ]; then
    cp .githooks/pre-commit .git/hooks/pre-commit
    chmod +x .git/hooks/pre-commit
    echo "✅ Pre-commit hook installed"
else
    echo "❌ Error: .githooks/pre-commit not found"
    exit 1
fi

# Set git hooks path (optional, for team consistency)
git config core.hooksPath .githooks

echo ""
echo "🎉 Git hooks installed successfully!"
echo ""
echo "The following hooks are now active:"
echo "  • pre-commit: Runs code quality checks before each commit"
echo ""
echo "To bypass hooks temporarily, use: git commit --no-verify"
echo "To uninstall hooks, run: git config --unset core.hooksPath"
