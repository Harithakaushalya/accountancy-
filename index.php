<?php
/**
 * Main Application Index Entry Point
 * ApexFinance - Secure Smart Loan System
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/core/Auth.php';

if (Auth::check()) {
    redirect('dashboard.php');
} else {
    redirect('login.php');
}
