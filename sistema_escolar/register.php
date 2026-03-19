<?php
// register.php
require_once __DIR__ . '/config.php';
iniciarSessao();

if (!empty($_SESSION['professor_id'])) {
    header('Location: dashboard.php');
    exit;
}

$erro  = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome  = trim($_POST['nome']  ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha']      ?? '';
    $conf  = $_POST['confirmar']  ?? '';

    if ($nome === '' || $email === '' || $senha === '') {
        $erro = 'Preencha todos os campos obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'E-mail inválido.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter pelo menos 6 caracteres.';
    } elseif ($senha !== $conf) {
        $erro = 'As senhas não coincidem.';
    } else {
        $db   = getDB();
        $chk  = $db->prepare('SELECT id FROM professores WHERE email = ? LIMIT 1');
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $erro = 'Este e-mail já está cadastrado.';
        } else {
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $ins  = $db->prepare('INSERT INTO professores (nome, email, senha) VALUES (?, ?, ?)');
            $ins->execute([$nome, $email, $hash]);
            $sucesso = 'Conta criada com sucesso! <a href="login.php">Faça login</a>.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cadastro — <?= SITE_NAME ?></title>
  <link rel="stylesheet" href="assets/css/auth.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="auth-body">
  <div class="auth-card">
    <div class="auth-logo">
      <i class="fa-solid fa-book-open-reader"></i>
      <h1><?= SITE_NAME ?></h1>
      <p>Crie sua conta de professor</p>
    </div>

    <?php if ($erro): ?>
      <div class="alert alert-error"><i class="fa fa-circle-xmark"></i> <?= h($erro) ?></div>
    <?php endif; ?>
    <?php if ($sucesso): ?>
      <div class="alert alert-success"><i class="fa fa-circle-check"></i> <?= $sucesso ?></div>
    <?php endif; ?>

    <?php if (!$sucesso): ?>
    <form method="POST" class="auth-form" novalidate>
      <div class="form-group">
        <label for="nome"><i class="fa fa-user"></i> Nome completo</label>
        <input type="text" id="nome" name="nome" placeholder="Seu nome completo"
               value="<?= h($_POST['nome'] ?? '') ?>" required autofocus>
      </div>
      <div class="form-group">
        <label for="email"><i class="fa fa-envelope"></i> E-mail</label>
        <input type="email" id="email" name="email" placeholder="professor@escola.ao"
               value="<?= h($_POST['email'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label for="senha"><i class="fa fa-lock"></i> Senha</label>
        <div class="input-icon-right">
          <input type="password" id="senha" name="senha" placeholder="Mín. 6 caracteres" required>
          <span class="toggle-pass" onclick="togglePass('senha')"><i class="fa fa-eye" id="ico-senha"></i></span>
        </div>
      </div>
      <div class="form-group">
        <label for="confirmar"><i class="fa fa-lock"></i> Confirmar senha</label>
        <div class="input-icon-right">
          <input type="password" id="confirmar" name="confirmar" placeholder="Repita a senha" required>
          <span class="toggle-pass" onclick="togglePass('confirmar')"><i class="fa fa-eye" id="ico-confirmar"></i></span>
        </div>
      </div>
      <button type="submit" class="btn-primary btn-block">
        <i class="fa fa-user-plus"></i> Criar conta
      </button>
    </form>
    <?php endif; ?>

    <p class="auth-footer">
      Já tem conta? <a href="login.php">Fazer login</a>
    </p>
  </div>
  <script>
    function togglePass(id) {
      const inp = document.getElementById(id);
      const ico = document.getElementById('ico-' + id);
      if (inp.type === 'password') { inp.type = 'text'; ico.className = 'fa fa-eye-slash'; }
      else                         { inp.type = 'password'; ico.className = 'fa fa-eye'; }
    }
  </script>
</body>
</html>
