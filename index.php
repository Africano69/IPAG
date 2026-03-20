<?php
// index.php — redireciona para login ou dashboard
require_once __DIR__ . '/config.php';
iniciarSessao();
if (!empty($_SESSION['professor_id'])) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
