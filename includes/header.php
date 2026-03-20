<?php
// includes/header.php
// Recebe: $paginaTitulo (string) e $paginaAtiva (string)
$paginaTitulo = $paginaTitulo ?? 'Dashboard';
$paginaAtiva  = $paginaAtiva  ?? 'dashboard';
$prof = professorLogado();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= h($paginaTitulo) ?> — <?= SITE_NAME ?></title>
  <link rel="stylesheet" href="/sistema_escolar/assets/css/main.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
