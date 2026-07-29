# Headroom tokensave — activación automática

Para que **tokensave** comprima el contexto **cada vez** que usas Claude Code,
hay dos entornos configurados:

## 1. Terminal local (tu máquina) — solución completa

`headroom wrap claude` arranca el proxy, fija `ANTHROPIC_BASE_URL`, registra el
MCP de compresión y construye el índice tokensave (code-graph). Para no
escribirlo cada vez, añade esta función a tu shell:

**bash** → `~/.bashrc` · **zsh** → `~/.zshrc`

```bash
# Enruta siempre Claude Code por Headroom (tokensave)
claude() {
  command headroom wrap claude "$@"
}
```

Recarga con `source ~/.zshrc` (o `~/.bashrc`). Desde entonces, cada vez que
ejecutes `claude`, tokensave se activa automáticamente.

Variantes útiles (edita la función a tu gusto):
- `headroom wrap claude --memory`  → memoria persistente entre sesiones
- `headroom wrap claude --1m`      → conserva la ventana de contexto de 1M

Para saltarte el wrap puntualmente: `command claude`.

## 2. Claude Code en la web (este repo) — hook SessionStart

`.claude/settings.json` registra un hook (`.claude/hooks/headroom-tokensave.sh`)
que en cada sesión web:
1. Instala el CLI `headroom` si falta.
2. Registra el MCP de Headroom (`headroom_retrieve` / `headroom_compress`).
3. Deja tokensave listo para el repo.

**Limitación del entorno web:** el harness gestiona la conexión al modelo, así
que el hook **no** puede reenrutar `ANTHROPIC_BASE_URL` por el proxy a mitad de
sesión. Deja las herramientas de tokensave disponibles, pero la compresión
automática de *todo* el tráfico solo ocurre con `headroom wrap claude` en local.
