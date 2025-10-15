# 📚 Manual Completo - Integração API Pagou

> **Autor:** [Dante Testa](https://dantetesta.com.br)  
> **Versão:** 1.0  
> **Data:** 07/10/2025

---

## 📋 Índice

1. [Visão Geral](#visão-geral)
2. [Credenciais e Configuração](#credenciais-e-configuração)
3. [Criar PIX (Gerar QR Code)](#criar-pix-gerar-qr-code)
4. [Verificar Status do Pagamento](#verificar-status-do-pagamento)
5. [Fluxo Completo Frontend](#fluxo-completo-frontend)
6. [Implementação Backend](#implementação-backend)
7. [Problemas Comuns e Soluções](#problemas-comuns-e-soluções)
8. [Checklist de Implementação](#checklist-de-implementação)
9. [Código Mínimo Funcional](#código-mínimo-funcional)

---

## 🎯 Visão Geral

### O que este sistema faz:

1. ✅ **Gera QR Code PIX** via API Pagou
2. ✅ **Aguarda pagamento** (verificação automática a cada 5 segundos)
3. ✅ **Detecta pagamento REAL** (via campo `paid_at`)
4. ✅ **Libera acesso/produto** automaticamente

### Fluxo Simplificado:

```
Cliente → Preenche Dados → Gera PIX → Escaneia QR Code → Paga
         ↓
Sistema → Verifica a cada 5s → Detecta paid_at → Libera Acesso
```

---

## 🔑 Credenciais e Configuração

### 1. Obter Chave de API

1. Acesse: https://app.pagou.com.br
2. Vá em: **Configurações → API**
3. Copie a **chave de PRODUÇÃO** (não teste/sandbox)

### 2. Configurar no Código

```php
// Configurações da API Pagou
define('PAGOU_API_KEY', 'sua-chave-aqui');
define('PAGOU_API_URL', 'https://api.pagou.com.br');
```

### ⚠️ Importante:

- **Nunca** exponha a chave em código público
- Use **variáveis de ambiente** em produção
- Chave de **teste** auto-aprova pagamentos (não use!)

---

## 📡 Criar PIX (Gerar QR Code)

### Endpoint:

```
POST https://api.pagou.com.br/v1/pix
```

### Headers Obrigatórios:

```http
X-API-KEY: sua-chave-aqui
Content-Type: application/json
User-Agent: SeuApp/1.0
```

### Payload (JSON):

```json
{
  "amount": 5.00,
  "description": "Nome do Produto",
  "expiration": 3600,
  "payer": {
    "name": "Nome Completo do Cliente",
    "document": "12345678909"
  }
}
```

### Campos Obrigatórios:

| Campo | Tipo | Descrição | Validação |
|-------|------|-----------|-----------|
| `amount` | number | Valor em reais | Mínimo: 5.00 |
| `description` | string | Nome do produto | Obrigatório |
| `expiration` | integer | Tempo em segundos | Mínimo: 60 |
| `payer.name` | string | Nome do cliente | Obrigatório |
| `payer.document` | string | CPF do cliente | 11 dígitos |

### Resposta de Sucesso (HTTP 201):

```json
{
  "id": "uuid-do-pix",
  "amount": 5,
  "description": "Nome do Produto",
  "expiration": 3600,
  "payer": {
    "name": "Nome do Cliente",
    "document": "12345678909"
  },
  "payload": {
    "data": "00020101021226910014br.gov.bcb.pix...",
    "image": "iVBORw0KGgoAAAANSUhEUgAABAAAAAQA..."
  },
  "status": 0,
  "paid_at": null
}
```

### Dados Importantes da Resposta:

- **`id`**: UUID do PIX (salvar para consultas)
- **`payload.data`**: Código PIX copia e cola
- **`payload.image`**: QR Code em base64 (sem prefixo)
- **`status`**: 0 = Pendente

### ⚠️ Atenção - QR Code Base64:

A imagem vem em **base64 puro**, sem prefixo. Para exibir:

```javascript
// ❌ ERRADO
<img src="{base64}">

// ✅ CORRETO
<img src="data:image/png;base64,{base64}">
```

### Exemplo de Implementação (PHP):

```php
/**
 * Cria PIX na API Pagou
 * 
 * @param string $nome Nome do cliente
 * @param string $cpf CPF (11 dígitos)
 * @param string $email Email do cliente
 * @param float $valor Valor em reais
 * @return array Dados do PIX criado
 */
function criarPix($nome, $cpf, $email, $valor) {
    // Remove caracteres não numéricos do CPF
    $cpfLimpo = preg_replace('/\D/', '', $cpf);
    
    // Valida CPF
    if (strlen($cpfLimpo) !== 11) {
        throw new Exception('CPF deve ter 11 dígitos');
    }
    
    // Monta payload
    $payload = [
        'amount' => $valor,
        'description' => 'Produto',
        'expiration' => 3600, // 1 hora
        'payer' => [
            'name' => $nome,
            'document' => $cpfLimpo
        ]
    ];
    
    // Faz requisição
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.pagou.com.br/v1/pix',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'X-API-KEY: ' . PAGOU_API_KEY,
            'Content-Type: application/json',
            'User-Agent: MeuApp/1.0'
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 201) {
        throw new Exception('Erro ao criar PIX');
    }
    
    return json_decode($response, true);
}
```

---

## 🔍 Verificar Status do Pagamento

### Endpoint:

```
GET https://api.pagou.com.br/v1/pix/{id}
```

### Headers:

```http
X-API-KEY: sua-chave-aqui
Content-Type: application/json
```

### Resposta:

```json
{
  "id": "uuid-do-pix",
  "status": 0,
  "paid_at": null,
  "expired_at": "2025-10-07T03:11:18.943Z"
}
```

### 📊 Status Possíveis:

| Status | Significado | Ação |
|--------|-------------|------|
| `0` | Pendente | Aguardar |
| `1` | Pago | Verificar `paid_at` |
| `2` | Cancelado | Não liberar |
| `3` | Expirado | Verificar `paid_at` |
| `4` | Expirado | Verificar `paid_at` |

### 🔴 PROBLEMA CRÍTICO - Auto-aprovação Fake:

A API em modo **teste/sandbox** retorna:

```json
{
  "status": 1,
  "paid_at": null  // ❌ SEM DATA = FAKE!
}
```

Isso é **auto-aprovação falsa**. O sistema libera sem pagamento real!

### ✅ SOLUÇÃO - Validação Correta:

```php
// ❌ ERRADO - Não confie apenas no status
if ($response['status'] == 1) {
    liberarAcesso(); // PERIGOSO!
}

// ✅ CORRETO - Verifique paid_at
if (isset($response['paid_at']) && !empty($response['paid_at'])) {
    liberarAcesso(); // SEGURO!
}
```

### Exemplo de Implementação (PHP):

```php
/**
 * Verifica se o PIX foi pago
 * 
 * @param string $chargeId UUID do PIX
 * @return bool True se pago, False se pendente
 */
function verificarPagamento($chargeId) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "https://api.pagou.com.br/v1/pix/{$chargeId}",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'X-API-KEY: ' . PAGOU_API_KEY,
            'Content-Type: application/json'
        ],
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return false;
    }
    
    $data = json_decode($response, true);
    
    // ✅ VALIDAÇÃO CORRETA
    // Ignora status, só verifica paid_at
    if (isset($data['paid_at']) && !empty($data['paid_at'])) {
        return true; // PAGO DE VERDADE!
    }
    
    return false; // Ainda pendente
}
```

---

## 🔄 Fluxo Completo Frontend

### HTML + JavaScript (Exemplo Completo):

```html
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento PIX</title>
</head>
<body>
    <!-- Formulário -->
    <form id="form-compra">
        <input type="text" id="nome" placeholder="Nome Completo" required>
        <input type="email" id="email" placeholder="Email" required>
        <input type="text" id="cpf" placeholder="CPF" required>
        <button type="submit">Pagar R$ 5,00</button>
    </form>
    
    <!-- QR Code (oculto inicialmente) -->
    <div id="qrcode-section" style="display: none;">
        <img id="qrcode-img" alt="QR Code PIX">
        <p id="status">Aguardando pagamento...</p>
    </div>
    
    <script>
        let chargeId = null;
        let checkInterval = null;
        
        // 1. Gerar PIX
        document.getElementById('form-compra').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const nome = document.getElementById('nome').value;
            const email = document.getElementById('email').value;
            const cpf = document.getElementById('cpf').value;
            
            try {
                const response = await fetch('/api/criar-pix.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ nome, email, cpf, valor: 5.00 })
                });
                
                const data = await response.json();
                
                if (!data.success) {
                    alert('Erro: ' + data.error);
                    return;
                }
                
                // 2. Exibir QR Code
                chargeId = data.charge_id;
                const qrcodeImg = data.qrcode_image;
                
                document.getElementById('qrcode-img').src = 
                    'data:image/png;base64,' + qrcodeImg;
                
                document.getElementById('form-compra').style.display = 'none';
                document.getElementById('qrcode-section').style.display = 'block';
                
                // 3. Iniciar verificação (polling a cada 5s)
                setTimeout(() => {
                    verificarPagamento();
                    checkInterval = setInterval(verificarPagamento, 5000);
                }, 5000);
                
            } catch (error) {
                alert('Erro: ' + error.message);
            }
        });
        
        // 4. Verificar pagamento
        async function verificarPagamento() {
            if (!chargeId) return;
            
            try {
                const response = await fetch(`/api/verificar.php?id=${chargeId}`);
                const data = await response.json();
                
                if (data.pago) {
                    // PAGAMENTO CONFIRMADO!
                    clearInterval(checkInterval);
                    
                    document.getElementById('status').innerHTML = 
                        '✅ Pagamento Confirmado!';
                    
                    // Redirecionar ou liberar acesso
                    setTimeout(() => {
                        window.location.href = '/produto';
                    }, 2000);
                }
            } catch (error) {
                console.error('Erro:', error);
            }
        }
    </script>
</body>
</html>
```

---

## 🔧 Implementação Backend

### Arquivo: `/api/criar-pix.php`

```php
<?php
/**
 * Endpoint para criar PIX
 * @author Dante Testa (https://dantetesta.com.br)
 */

header('Content-Type: application/json');
require_once 'config.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $nome = $input['nome'] ?? '';
    $email = $input['email'] ?? '';
    $cpf = $input['cpf'] ?? '';
    $valor = $input['valor'] ?? 5.00;
    
    // Validações
    if (empty($nome) || empty($email) || empty($cpf)) {
        throw new Exception('Dados incompletos');
    }
    
    $cpfLimpo = preg_replace('/\D/', '', $cpf);
    if (strlen($cpfLimpo) !== 11) {
        throw new Exception('CPF inválido');
    }
    
    if ($valor < 5.00) {
        throw new Exception('Valor mínimo: R$ 5,00');
    }
    
    // Criar PIX
    $pix = criarPix($nome, $cpfLimpo, $email, $valor);
    
    // Salvar no banco (opcional)
    // salvarVenda($pix['id'], $nome, $email, $cpf, $valor);
    
    echo json_encode([
        'success' => true,
        'charge_id' => $pix['id'],
        'qrcode_image' => $pix['payload']['image']
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
```

### Arquivo: `/api/verificar.php`

```php
<?php
/**
 * Endpoint para verificar pagamento
 * @author Dante Testa (https://dantetesta.com.br)
 */

header('Content-Type: application/json');
require_once 'config.php';

try {
    $chargeId = $_GET['id'] ?? '';
    
    if (empty($chargeId)) {
        throw new Exception('ID não informado');
    }
    
    // Verificar na API Pagou
    $pago = verificarPagamento($chargeId);
    
    if ($pago) {
        // Marcar como pago no banco (opcional)
        // marcarComoPago($chargeId);
        
        // Liberar acesso, enviar email, etc
        // liberarAcesso($chargeId);
    }
    
    echo json_encode([
        'success' => true,
        'pago' => $pago
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
```

---

## ⚠️ Problemas Comuns e Soluções

### 1. 🔴 Auto-aprovação Fake

**Problema:** Sistema libera acesso sem pagamento real

**Causa:** Chave de API em modo teste/sandbox

**Sintomas:**
- `status: 1` mas `paid_at: null`
- Libera imediatamente após gerar QR Code

**Solução:**
```php
// ❌ ERRADO
if ($status == 1) { liberarAcesso(); }

// ✅ CORRETO
if (!empty($paid_at)) { liberarAcesso(); }
```

---

### 2. 🔴 PIX Expirado mas Pago

**Problema:** Cliente pagou mas sistema não libera

**Causa:** Pagamento feito após expiração do PIX

**Sintomas:**
- `status: 4` (expirado)
- `paid_at: "2025-10-07T02:11:41.854Z"` (tem data!)

**Solução:**
```php
// Ignora status, só verifica paid_at
if (isset($data['paid_at']) && !empty($data['paid_at'])) {
    liberarAcesso(); // Cliente pagou!
}
```

---

### 3. 🔴 QR Code não Aparece

**Problema:** Imagem do QR Code não carrega

**Causa:** Falta prefixo `data:image/png;base64,`

**Sintomas:**
- Tag `<img>` vazia ou quebrada
- Console mostra erro de imagem

**Solução:**
```javascript
// ❌ ERRADO
img.src = qrcodeBase64;

// ✅ CORRETO
img.src = 'data:image/png;base64,' + qrcodeBase64;
```

---

### 4. 🔴 Não Detecta Pagamento

**Problema:** Cliente paga mas sistema não detecta

**Causa:** Polling não está funcionando

**Soluções:**
1. Verificar se `setInterval` está ativo
2. Conferir se `chargeId` está correto
3. Ver logs do servidor (erros na API)
4. Testar endpoint `/api/verificar.php` manualmente

---

## ✅ Checklist de Implementação

### Passo 1: Configuração Inicial

- [ ] Criar conta no Pagou (https://app.pagou.com.br)
- [ ] Obter chave de API de **PRODUÇÃO**
- [ ] Configurar credenciais no código
- [ ] Testar conexão com API

### Passo 2: Criar PIX

- [ ] Criar endpoint POST `/api/criar-pix.php`
- [ ] Validar dados do cliente (nome, CPF, email)
- [ ] Fazer requisição para `/v1/pix`
- [ ] Salvar `id` retornado (charge_id)
- [ ] Retornar QR Code em base64

### Passo 3: Exibir QR Code

- [ ] Criar formulário de compra
- [ ] Adicionar prefixo `data:image/png;base64,`
- [ ] Exibir imagem do QR Code
- [ ] Mostrar status "Aguardando pagamento"

### Passo 4: Verificar Pagamento

- [ ] Criar endpoint GET `/api/verificar.php`
- [ ] Implementar polling (setInterval 5s)
- [ ] Consultar `/v1/pix/{id}` na API
- [ ] Verificar campo `paid_at` (não `status`)
- [ ] Retornar `{ pago: true/false }`

### Passo 5: Liberar Acesso

- [ ] Detectar `paid_at` preenchido
- [ ] Marcar como pago no banco (opcional)
- [ ] Enviar email de confirmação (opcional)
- [ ] Redirecionar para produto/área
- [ ] Ou alterar role/permissão do usuário

### Passo 6: Testes

- [ ] Testar com valor mínimo (R$ 5,00)
- [ ] Pagar PIX de verdade
- [ ] Verificar se detecta pagamento
- [ ] Conferir se libera acesso
- [ ] Testar PIX expirado

---

## 💻 Código Mínimo Funcional

### Backend Completo (PHP):

```php
<?php
// config.php
define('PAGOU_API_KEY', 'sua-chave-aqui');
define('PAGOU_API_URL', 'https://api.pagou.com.br');

// criar-pix.php
function criarPix($nome, $cpf, $email, $valor) {
    $payload = [
        'amount' => $valor,
        'description' => 'Produto',
        'expiration' => 3600,
        'payer' => [
            'name' => $nome,
            'document' => preg_replace('/\D/', '', $cpf)
        ]
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => PAGOU_API_URL . '/v1/pix',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'X-API-KEY: ' . PAGOU_API_KEY,
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);
    
    $response = curl_exec($ch);
    return json_decode($response, true);
}

// verificar.php
function verificarPagamento($chargeId) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => PAGOU_API_URL . "/v1/pix/{$chargeId}",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'X-API-KEY: ' . PAGOU_API_KEY,
            'Content-Type: application/json'
        ]
    ]);
    
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    
    // ✅ VALIDAÇÃO CORRETA
    return !empty($data['paid_at']);
}
```

### Frontend Completo (HTML + JS):

```html
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento PIX</title>
</head>
<body>
    <div id="form">
        <input id="nome" placeholder="Nome">
        <input id="cpf" placeholder="CPF">
        <button onclick="gerarPix()">Pagar R$ 5,00</button>
    </div>
    
    <div id="qrcode" style="display:none">
        <img id="img">
        <p id="status">Aguardando...</p>
    </div>
    
    <script>
        let chargeId;
        
        async function gerarPix() {
            const res = await fetch('/criar-pix.php', {
                method: 'POST',
                body: JSON.stringify({
                    nome: document.getElementById('nome').value,
                    cpf: document.getElementById('cpf').value,
                    valor: 5.00
                })
            });
            
            const data = await res.json();
            chargeId = data.charge_id;
            
            document.getElementById('img').src = 
                'data:image/png;base64,' + data.qrcode_image;
            
            document.getElementById('form').style.display = 'none';
            document.getElementById('qrcode').style.display = 'block';
            
            setInterval(verificar, 5000);
        }
        
        async function verificar() {
            const res = await fetch('/verificar.php?id=' + chargeId);
            const data = await res.json();
            
            if (data.pago) {
                document.getElementById('status').innerHTML = '✅ Pago!';
                window.location = '/produto';
            }
        }
    </script>
</body>
</html>
```

---

## 🎯 Resumo - 3 Regras de Ouro

### 1. ✅ Sempre Verificar `paid_at`

```php
// NÃO confie no status
if (!empty($data['paid_at'])) {
    // PAGO DE VERDADE!
}
```

### 2. ✅ Polling a Cada 5 Segundos

```javascript
setInterval(verificarPagamento, 5000);
```

### 3. ✅ Base64 Precisa de Prefixo

```javascript
img.src = 'data:image/png;base64,' + base64;
```

---

## 📞 Suporte

**Documentação Oficial:** https://docs.pagou.com.br  
**Painel Pagou:** https://app.pagou.com.br  
**Autor:** [Dante Testa](https://dantetesta.com.br)

---

## 📝 Notas Finais

- ✅ Este manual funciona para **qualquer linguagem/framework**
- ✅ Adapte os exemplos para Node.js, Python, etc
- ✅ Sempre use chave de **PRODUÇÃO** (não teste)
- ✅ Implemente logs para debug
- ✅ Teste com pagamentos reais antes de lançar

**Boa sorte com sua integração!** 🚀
