<?php
/**
 * Configuração de Email (SMTP) - EXEMPLO
 * 
 * INSTRUÇÕES:
 * 1. Copie este arquivo para: config/email.php
 * 2. Preencha com seus dados SMTP
 * 3. Nunca versione o arquivo email.php (está no .gitignore)
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 1.2.0
 */

// ============================================================================
// CONFIGURAÇÕES SMTP
// ============================================================================

// Servidor SMTP
define('SMTP_HOST', 'smtp.gmail.com');  // Ex: smtp.gmail.com, smtp.office365.com, smtp.hostinger.com
define('SMTP_PORT', 587);                // 587 (TLS) ou 465 (SSL)
define('SMTP_SECURE', 'tls');            // 'tls' ou 'ssl'

// Autenticação
define('SMTP_USERNAME', 'seu-email@gmail.com');  // Seu email completo
define('SMTP_PASSWORD', 'sua-senha-app');        // Senha do email ou senha de app

// Remetente padrão
define('SMTP_FROM_EMAIL', 'seu-email@gmail.com');
define('SMTP_FROM_NAME', 'Sistema de Consignados');

// Configurações adicionais
define('SMTP_DEBUG', 0);  // 0 = sem debug, 1 = mensagens do cliente, 2 = mensagens do cliente e servidor
define('SMTP_CHARSET', 'UTF-8');

// ============================================================================
// INSTRUÇÕES DE CONFIGURAÇÃO POR PROVEDOR
// ============================================================================

/*
 * 📧 GMAIL:
 * ---------
 * 1. Ative a verificação em 2 etapas: https://myaccount.google.com/security
 * 2. Gere uma "Senha de app": https://myaccount.google.com/apppasswords
 * 3. Use a senha de app gerada no SMTP_PASSWORD (16 caracteres)
 * 
 * SMTP_HOST: smtp.gmail.com
 * SMTP_PORT: 587
 * SMTP_SECURE: tls
 * SMTP_USERNAME: seu-email@gmail.com
 * SMTP_PASSWORD: xxxx xxxx xxxx xxxx (senha de app)
 * 
 * 
 * 📧 OUTLOOK/HOTMAIL:
 * -------------------
 * SMTP_HOST: smtp-mail.outlook.com
 * SMTP_PORT: 587
 * SMTP_SECURE: tls
 * SMTP_USERNAME: seu-email@outlook.com
 * SMTP_PASSWORD: sua-senha-normal
 * 
 * 
 * 📧 HOSTINGER:
 * -------------
 * SMTP_HOST: smtp.hostinger.com
 * SMTP_PORT: 587
 * SMTP_SECURE: tls
 * SMTP_USERNAME: seu-email@seudominio.com
 * SMTP_PASSWORD: senha-do-email
 * 
 * 
 * 📧 OUTROS PROVEDORES:
 * ---------------------
 * Consulte a documentação do seu provedor de email para obter:
 * - Servidor SMTP (host)
 * - Porta SMTP
 * - Tipo de segurança (TLS/SSL)
 * - Credenciais de autenticação
 */
