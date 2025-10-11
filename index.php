<?php
/**
 * Index - Sistema de Roteamento Avançado
 * 
 * Gerencia todas as rotas do sistema com URLs amigáveis e SEO-friendly
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.0.0
 */

// Carregar configurações
require_once 'config/config.php';

// Carregar classe Router
require_once 'classes/Router.php';

// Carregar e executar rotas
$router = require 'config/routes.php';

// Executar o roteamento
$router->dispatch();
