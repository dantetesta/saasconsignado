# 🚀 Instalação Rápida - Sistema de Consignados

**Desenvolvido por:** [Dante Testa](https://dantetesta.com.br)  
**Versão:** 1.2.5

---

## 📋 Pré-requisitos

- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Servidor web (Apache/Nginx)
- Composer (para dependências)

---

## ⚡ Instalação em 3 Passos

### 1️⃣ **Upload dos Arquivos**

Faça upload de todos os arquivos para o servidor web.

### 2️⃣ **Instalar Dependências**

```bash
composer install
```

### 3️⃣ **Executar Instalador**

Acesse no navegador:

```
http://seudominio.com/install.php
# OU
http://seudominio.com/controle/install.php
```

Preencha os dados solicitados:
- **Banco de Dados:** host, nome, usuário, senha
- **Administrador:** nome, email, senha

Clique em **"Instalar Sistema"**

✅ **Pronto!** O sistema está instalado e pronto para uso.

---

## 🔒 Segurança

Após a instalação:
- ✅ O arquivo `.installed` é criado automaticamente
- ✅ O `install.php` não pode ser executado novamente
- ✅ Para reinstalar, delete o arquivo `.installed`

---

## 📧 Configurar Email (Opcional)

1. Copie o arquivo de exemplo:
```bash
cp config/email.example.php config/email.php
```

2. Edite `config/email.php` com suas credenciais SMTP

---

## 🆘 Suporte

Em caso de problemas:
- Verifique as permissões das pastas `uploads/` e `logs/`
- Verifique se o PHP tem as extensões: PDO, PDO_MySQL, mbstring
- Consulte a documentação em: https://dantetesta.com.br

---

**© 2025 Dante Testa - Todos os direitos reservados**
