<?php
/**
 * Login do Parceiro
 * Sistema EAD Pro
 */

require_once '../config/database.php';
require_once '../app/models/Parceiro.php';

iniciar_sessao();

// Se já está logado no EAD, redirecionar para dashboard
if (isset($_SESSION['ead_autenticado']) && $_SESSION['ead_autenticado'] === true) {
    header('Location: dashboard.php');
    exit;
}

$erro = '';
$sucesso = '';

// Processar formulário de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizar($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    
    if (empty($email) || empty($senha)) {
        $erro = 'Email e senha são obrigatórios';
    } else {
        $parceiro_model = new Parceiro($pdo);
        $resultado = $parceiro_model->login($email, $senha);
        
        if ($resultado['sucesso']) {
            // Criar sessão com prefixo 'ead_' para integração
            $_SESSION['ead_usuario_id'] = $resultado['parceiro']['id'];
            $_SESSION['ead_email'] = $resultado['parceiro']['email'];
            $_SESSION['ead_nome'] = $resultado['parceiro']['nome'];
            $_SESSION['ead_parceiro_id'] = $resultado['parceiro']['parceiro_id'];
            $_SESSION['ead_parceiro_nome'] = $resultado['parceiro']['empresa'];
            $_SESSION['ead_autenticado'] = true;
            $_SESSION['ead_token_gerado'] = time();

            // Redirecionar para dashboard
            header('Location: dashboard.php');
            exit;
        } else {
            $erro = $resultado['mensagem'];
        }
    }
}

// Verificar se há mensagem de timeout
$timeout = isset($_GET['timeout']) ? true : false;
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Login - Parceiro EAD Pro</title>

    <!-- Custom fonts for this template-->
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">

</head>

<body class="bg-gradient-primary">

    <div class="container">

        <!-- Outer Row -->
        <div class="row justify-content-center">

            <div class="col-xl-10 col-lg-12 col-md-9">

                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-0">
                        <!-- Nested Row within Card Body -->
                        <div class="row">
                            <div class="col-lg-6 d-none d-lg-block bg-login-image"></div>
                            <div class="col-lg-6">
                                <div class="p-5">
                                    <div class="text-center">
                                        <h1 class="h4 text-gray-900 mb-4">
                                            <i class="fas fa-graduation-cap"></i> EAD Pro
                                        </h1>
                                        <p class="text-gray-600 small mb-4">Acesso para Parceiros</p>
                                    </div>

                                    <?php if ($timeout): ?>
                                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                            <i class="fas fa-exclamation-triangle"></i> Sua sessão expirou. Faça login novamente.
                                            <button type="button" class="close" data-dismiss="alert">
                                                <span>&times;</span>
                                            </button>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($erro): ?>
                                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            <i class="fas fa-exclamation-circle"></i> <?php echo $erro; ?>
                                            <button type="button" class="close" data-dismiss="alert">
                                                <span>&times;</span>
                                            </button>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($sucesso): ?>
                                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                                            <i class="fas fa-check-circle"></i> <?php echo $sucesso; ?>
                                            <button type="button" class="close" data-dismiss="alert">
                                                <span>&times;</span>
                                            </button>
                                        </div>
                                    <?php endif; ?>

                                    <form method="POST" action="" class="user">
                                        <div class="form-group">
                                            <input type="email" class="form-control form-control-user" id="email" name="email"
                                                aria-describedby="emailHelp" placeholder="Email" required>
                                        </div>
                                        <div class="form-group">
                                            <input type="password" class="form-control form-control-user" id="senha" name="senha"
                                                placeholder="Senha" required>
                                        </div>
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox small">
                                                <input type="checkbox" class="custom-control-input" id="lembrar" name="lembrar">
                                                <label class="custom-control-label" for="lembrar">Lembrar-me</label>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-user btn-block">
                                            <i class="fas fa-sign-in-alt"></i> Entrar
                                        </button>
                                        <hr>
                                    </form>
                                    <hr>
                                    <div class="text-center">
                                        <a class="small" href="recuperar-senha.php">
                                            <i class="fas fa-key"></i> Esqueceu a senha?
                                        </a>
                                    </div>
                                    <div class="text-center">
                                        <a class="small" href="registro.php">
                                            <i class="fas fa-user-plus"></i> Criar Conta de Parceiro
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="../js/sb-admin-2.min.js"></script>

</body>

</html>

