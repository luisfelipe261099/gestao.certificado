<?php
/**
 * Recuperar Senha - Sistema de Certificados
 * Padrão MVP - Camada de Apresentação
 */

require_once 'app/config/config.php';

// Se já está autenticado, redireciona
if (isAuthenticated()) {
    if (hasRole(ROLE_ADMIN)) {
        redirect(DIR_ADMIN . '/dashboard-admin.php');
    } else {
        redirect(DIR_PARCEIRO . '/dashboard-parceiro.php');
    }
}

$error = '';
$success = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Email é obrigatório.';
    } elseif (!isValidEmail($email)) {
        $error = 'Email inválido.';
    } else {
        // Aqui você implementaria a lógica de envio de email
        // Por enquanto, apenas mostramos uma mensagem de sucesso
        $success = 'Se o email existe em nosso sistema, você receberá um link para recuperar sua senha.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Recuperar Senha - Sistema de Certificados">
    <title>Recuperar Senha - Sistema de Certificados</title>
    
    <link href="<?php echo APP_URL; ?>/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="<?php echo APP_URL; ?>/ead/css/sb-admin-2.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-primary">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-12 col-md-9">
                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-0">
                        <div class="row">
                            <div class="col-lg-6 d-none d-lg-block bg-login-image"></div>
                            <div class="col-lg-6">
                                <div class="p-5">
                                    <div class="text-center">
                                        <h1 class="h4 text-gray-900 mb-4">Recuperar Senha</h1>
                                    </div>
                                    
                                    <?php if (!empty($error)): ?>
                                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($success)): ?>
                                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                                            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <form class="user" method="POST" action="">
                                        <div class="form-group">
                                            <input type="email" class="form-control form-control-user" 
                                                   id="email" name="email" placeholder="Digite seu email" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-user btn-block">
                                            Enviar Link de Recuperação
                                        </button>
                                    </form>
                                    
                                    <hr>
                                    
                                    <div class="text-center">
                                        <a class="small" href="login.php">Voltar para Login</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="<?php echo APP_URL; ?>/vendor/jquery/jquery.min.js"></script>
    <script src="<?php echo APP_URL; ?>/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo APP_URL; ?>/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="<?php echo APP_URL; ?>/ead/js/sb-admin-2.min.js"></script>
</body>
</html>

