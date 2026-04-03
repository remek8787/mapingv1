<?php
if (session_status() === PHP_SESSION_NONE) session_start();

const ADMIN_USER = 'admin';
const ADMIN_PASS = 'admin12345';

function is_logged_in(): bool {
  return !empty($_SESSION['logged_in']);
}

function require_login(): void {
  if (!is_logged_in()) {
    header('Location: login.php');
    exit;
  }
}
