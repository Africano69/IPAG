<?php
// login.php
require_once __DIR__ . '/config.php';
iniciarSessao();

// Se já estiver logado, redireciona ao dashboard
if (!empty($_SESSION['professor_id'])) {
    header('Location: dashboard.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($email === '' || $senha === '') {
        $erro = 'Por favor, preencha todos os campos.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare('SELECT id, nome, email, senha FROM professores WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $prof = $stmt->fetch();

        if ($prof && password_verify($senha, $prof['senha'])) {
            $_SESSION['professor_id']    = $prof['id'];
            $_SESSION['professor_nome']  = $prof['nome'];
            $_SESSION['professor_email'] = $prof['email'];
            header('Location: dashboard.php');
            exit;
        } else {
            $erro = 'E-mail ou senha inválidos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — <?= SITE_NAME ?></title>
  <link rel="stylesheet" href="assets/css/auth.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="auth-body">
  <div class="auth-card">
    <div class="auth-logo">
      <i class="fa-solid fa-book-open-reader"></i>
      <h1><?= SITE_NAME ?></h1>
      <p>Sistema de Sumário e Presenças</p>
    </div>

    <?php if ($erro): ?>
      <div class="alert alert-error"><i class="fa fa-circle-xmark"></i> <?= h($erro) ?></div>
    <?php endif; ?>

    <form method="POST" class="auth-form" novalidate>
      <div class="form-group">
        <label for="email"><i class="fa fa-envelope"></i> E-mail</label>
        <input type="email" id="email" name="email" placeholder="professor@escola.ao"
               value="<?= h($_POST['email'] ?? '') ?>" required autofocus>
      </div>
      <div class="form-group">
        <label for="senha"><i class="fa fa-lock"></i> Senha</label>
        <div class="input-icon-right">
          <input type="password" id="senha" name="senha" placeholder="••••••••" required>
          <span class="toggle-pass" onclick="togglePass('senha')"><i class="fa fa-eye" id="ico-senha"></i></span>
        </div>
      </div>
      <button type="submit" class="btn-primary btn-block">
        <i class="fa fa-right-to-bracket"></i> Entrar
      </button>
    </form>

    <p class="auth-footer">
      Ainda não tem conta? <a href="register.php">Cadastre-se</a>
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
