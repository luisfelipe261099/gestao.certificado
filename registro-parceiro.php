<?php
/**
 * Registro de Parceiro - Sistema de Certificados
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

// Processar formulário de registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitize($_POST['nome'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $cnpj = sanitize($_POST['cnpj'] ?? '');
    $telefone = sanitize($_POST['telefone'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    // Validações
    if (empty($nome) || empty($email) || empty($password)) {
        $error = 'Nome, email e senha são obrigatórios.';
    } elseif (!isValidEmail($email)) {
        $error = 'Email inválido.';
    } elseif (strlen($password) < 6) {
        $error = 'Senha deve ter no mínimo 6 caracteres.';
    } elseif ($password !== $password_confirm) {
        $error = 'As senhas não conferem.';
    } else {
        // Conectar ao banco de dados
        $conn = getDBConnection();
        
        // Verificar se email já existe
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = 'Este email já está registrado.';
            } else {
                // Criar novo parceiro
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("
                    INSERT INTO usuarios (nome, email, senha, tipo_usuario, ativo, data_criacao)
                    VALUES (?, ?, ?, ?, 1, NOW())
                ");
                
                if ($stmt) {
                    $tipo = ROLE_PARCEIRO;
                    $stmt->bind_param("ssss", $nome, $email, $hashed_password, $tipo);
                    
                    if ($stmt->execute()) {
                        $usuario_id = $conn->insert_id;
                        
                        // Criar registro de parceiro
                        $stmt = $conn->prepare("
                            INSERT INTO parceiros (id, nome, email, cnpj, telefone, ativo, data_criacao)
                            VALUES (?, ?, ?, ?, ?, 1, NOW())
                        ");
                        
                        if ($stmt) {
                            $stmt->bind_param("issss", $usuario_id, $nome, $email, $cnpj, $telefone);
                            
                            if ($stmt->execute()) {
                                $success = 'Registro realizado com sucesso! Você pode fazer login agora.';
                            } else {
                                $error = 'Erro ao criar registro de parceiro.';
                            }
                            $stmt->close();
                        }
                    } else {
                        $error = 'Erro ao criar usuário.';
                    }
                    $stmt->close();
                }
            }
            $stmt->close();
        }
        
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Registro de Parceiro - Sistema de Certificados">
    <title>Registro de Parceiro - Sistema de Certificados</title>
    
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
                            <div class="col-lg-5 d-none d-lg-block bg-register-image"></div>
                            <div class="col-lg-7">
                                <div class="p-5">
                                    <div class="text-center">
                                        <h1 class="h4 text-gray-900 mb-4">Criar Conta de Parceiro</h1>
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
                                            <a href="login.php" class="alert-link">Ir para Login</a>
                                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <form class="user" method="POST" action="">
                                        <div class="form-group">
                                            <input type="text" class="form-control form-control-user" 
                                                   id="nome" name="nome" placeholder="Nome da Empresa" required>
                                        </div>
                                        <div class="form-group">
                                            <input type="email" class="form-control form-control-user" 
                                                   id="email" name="email" placeholder="Email" required>
                                        </div>
                                        <div class="form-group">
                                            <input type="text" class="form-control form-control-user" 
                                                   id="cnpj" name="cnpj" placeholder="CNPJ (opcional)">
                                        </div>
                                        <div class="form-group">
                                            <input type="tel" class="form-control form-control-user" 
                                                   id="telefone" name="telefone" placeholder="Telefone (opcional)">
                                        </div>
                                        <div class="form-group row">
                                            <div class="col-sm-6 mb-3 mb-sm-0">
                                                <input type="password" class="form-control form-control-user" 
                                                       id="password" name="password" placeholder="Senha" required>
                                            </div>
                                            <div class="col-sm-6">
                                                <input type="password" class="form-control form-control-user" 
                                                       id="password_confirm" name="password_confirm" placeholder="Confirmar Senha" required>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-user btn-block">
                                            Registrar Conta
                                        </button>
                                    </form>
                                    
                                    <hr>
                                    
                                    <div class="text-center">
                                        <a class="small" href="login.php">Já tem uma conta? Faça login!</a>
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

