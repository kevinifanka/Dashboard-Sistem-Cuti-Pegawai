<?php
// public/index.php — Entry Point & Simple Router

declare(strict_types=1);

// ---- Session (must start before any output) ----
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// ---- Load Global Config + DB constants ----
require_once dirname(__DIR__) . '/app/config/config.php';
require_once dirname(__DIR__) . '/app/config/database.php';

// ---- Autoload: controllers, models ----
spl_autoload_register(function (string $class): void {
  $dirs = [
    APP_DIR . '/controllers/',
    APP_DIR . '/models/',
  ];
  foreach ($dirs as $dir) {
    $file = $dir . $class . '.php';
    if (file_exists($file)) {
      require_once $file;
      return;
    }
  }
});

// ---- Route Map ----
// Maps ?page=xxx to [ControllerClass, method]
$routes = [
  // ── Auth ──
  'login'               => ['AuthController', 'login'],
  'logout'              => ['AuthController', 'logout'],
  'register'            => ['AuthController', 'register'],

  // ── Admin ──
  'dashboard'           => ['AdminDashboardController', 'dashboard'],
  'profile'             => ['AdminDashboardController', 'profile'],
  'leave-submission'    => ['AdminDashboardController', 'leaveSubmission'],
  'overtime-submission' => ['AdminDashboardController', 'overtimeSubmission'],
  'requests'            => ['AdminDashboardController', 'requests'],
  'overtime-requests'   => ['AdminDashboardController', 'overtimeManagement'],
  'employees'           => ['AdminDashboardController', 'employees'],
  'calendar'            => ['AdminDashboardController', 'calendar'],
  'reports'             => ['AdminDashboardController', 'reports'],
  'settings'            => ['AdminDashboardController', 'settings'],
];

// ---- Dispatch ----
$page = trim($_GET['page'] ?? 'dashboard');

// Sanitize: only allow alphanumeric + hyphens
$page = preg_replace('/[^a-z0-9\-]/', '', strtolower($page));

if (isset($routes[$page])) {
  [$controllerClass, $method] = $routes[$page];
  $controller = new $controllerClass();
  $controller->$method();
} else {
  // Fallback: redirect to dashboard
  header('Location: ' . PUBLIC_URL . '/?page=dashboard');
  exit;
}
