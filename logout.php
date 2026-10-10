<?php
/**
 * Logout Endpoint
 * ApexFinance - Secure Smart Loan System
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/core/Auth.php';

Auth::logout();
setFlash('success', 'You have been safely logged out.');
redirect('login.php');
