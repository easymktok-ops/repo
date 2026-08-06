#!/bin/bash
set -euo pipefail

# Install Node dependencies so the CLI, tests, and linters can run.
# npm install (not `ci`) so the resolved node_modules are cached with the container state.
cd "$CLAUDE_PROJECT_DIR"
npm install
