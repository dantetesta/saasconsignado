<?php
/**
 * Logout do Super Admin
 * 
 * @author Dante Testa <https://dantetesta.com.br>
 * @version 2.0.0
 */

session_start();
require_once '../classes/SuperAdmin.php';

SuperAdmin::logout();
header('Location: /admin/login.php');
exit;
