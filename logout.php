<?php
/**
 * Logout do Sistema
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 1.0.0
 */

session_start();
session_destroy();
header("Location: /login.php");
exit;
