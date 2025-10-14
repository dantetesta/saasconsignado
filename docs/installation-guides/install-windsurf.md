# Instalação do Windsurf

## MCP Server do GitHub - Configuração

### Pré-requisitos
- Node.js instalado
- GitHub Personal Access Token (Classic)

### Passos de Instalação

1. **Instalar o MCP Server**
```bash
npm install -g @modelcontextprotocol/server-github
```

2. **Configurar o Token**
- Acesse: https://github.com/settings/tokens
- Clique em "Generate new token (classic)"
- Selecione os scopes: repo, workflow, read:org, gist

3. **Criar Configuração**
Arquivo: `~/Library/Application Support/Windsurf/User/globalStorage/windsurf.windsurf/mcp_config.json`

```json
{
  "mcpServers": {
    "github": {
      "command": "npx",
      "args": ["-y", "@modelcontextprotocol/server-github"],
      "env": {
        "GITHUB_PERSONAL_ACCESS_TOKEN": "seu_token_aqui"
      }
    }
  }
}
```

4. **Reiniciar o Windsurf**

### Verificação
No MCP Marketplace, o GitHub deve aparecer como "Enabled" ✅

---
*Documentação criada por [Dante Testa](https://dantetesta.com.br)*
