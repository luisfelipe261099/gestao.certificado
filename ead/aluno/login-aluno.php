<?php
/**
 * Login do Aluno
 * Sistema EAD Pro
 */

require_once '../config/database.php';
require_once '../app/models/Aluno.php';

iniciar_sessao();

// Se já está logado, redirecionar para dashboard
if (isset($_SESSION['usuario_id']) && $_SESSION['tipo_usuario'] === 'aluno') {
    header('Location: dashboard-aluno.php');
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
        $aluno_model = new Aluno($pdo);
        $resultado = $aluno_model->login($email, $senha);
        
        if ($resultado['sucesso']) {
            // Criar sessão
            $_SESSION['usuario_id'] = $resultado['aluno']['id'];
            $_SESSION['tipo_usuario'] = 'aluno';
            $_SESSION['nome'] = $resultado['aluno']['nome'];
            $_SESSION['email'] = $resultado['aluno']['email'];
            $_SESSION['foto_url'] = $resultado['aluno']['foto_url'] ?? 'https://via.placeholder.com/32';
            
            // Redirecionar para dashboard
            header('Location: dashboard-aluno.php');
            exit;
        } else {
            $erro = $resultado['mensagem'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Login - Aluno EAD FaCiencia">
    <title>Login - Aluno EAD FaCiencia</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f0f0f5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        .login-container {
            display: flex;
            width: 100%;
            max-width: 1000px;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        .login-left {
            flex: 1;
            background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%);
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
        }

        .login-left .icon-wrapper {
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
        }

        .login-left .icon-wrapper .material-icons-outlined {
            font-size: 64px;
            color: white;
        }

        .login-left h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .login-left p {
            font-size: 0.95rem;
            line-height: 1.6;
            opacity: 0.95;
        }

        .login-right {
            flex: 1;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-right h2 {
            font-size: 1.75rem;
            font-weight: 600;
            color: #2d2d2d;
            margin-bottom: 10px;
        }

        .login-right .subtitle {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group .icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 20px;
        }

        .form-group input {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s;
            background: #f8f8fc;
        }

        .form-group input:focus {
            outline: none;
            border-color: #6E41C1;
            background: white;
        }

        .form-group input[type="password"] {
            font-family: 'Inter', sans-serif;
            letter-spacing: 2px;
        }

        .form-group input::placeholder {
            letter-spacing: normal;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
            font-size: 20px;
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            font-size: 0.875rem;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .remember-me input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        .remember-me label {
            cursor: pointer;
            color: #666;
            margin: 0;
        }

        .forgot-password {
            color: #6E41C1;
            text-decoration: none;
            font-weight: 500;
        }

        .forgot-password:hover {
            text-decoration: underline;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(110, 65, 193, 0.4);
        }

        .alert {
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-danger {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .alert-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }

        .divider {
            text-align: center;
            margin: 25px 0;
            color: #999;
            font-size: 0.875rem;
        }

        .back-link {
            text-align: center;
        }

        .back-link a {
            color: #6E41C1;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
            }

            .login-left {
                padding: 40px 30px;
            }

            .login-right {
                padding: 40px 30px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Lado Esquerdo -->
        <div class="login-left">
            <div class="icon-wrapper">
                <span class="material-icons-outlined">school</span>
            </div>
            <h1>Portal do Aluno</h1>
            <p>Acesse sua área de estudos com suas credenciais para acompanhar cursos, aulas e exercícios.</p>
        </div>

        <!-- Lado Direito -->
        <div class="login-right">
            <h2>Bem-vindo de volta!</h2>
            <p class="subtitle">Entre com suas credenciais de acesso</p>

            <?php if (!empty($erro)): ?>
                <div class="alert alert-danger">
                    <span class="material-icons-outlined">error_outline</span>
                    <?php echo htmlspecialchars($erro); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($sucesso)): ?>
                <div class="alert alert-success">
                    <span class="material-icons-outlined">check_circle_outline</span>
                    <?php echo htmlspecialchars($sucesso); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <span class="material-icons-outlined icon">person_outline</span>
                    <input type="email" name="email" placeholder="Seu email" required>
                </div>

                <div class="form-group">
                    <span class="material-icons-outlined icon">lock_outline</span>
                    <input type="password" name="senha" id="senha" placeholder="••••••••••" required>
                    <span class="material-icons-outlined password-toggle" onclick="togglePassword()">visibility_off</span>
                </div>

                <div class="form-options">
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Lembrar-me</label>
                    </div>
                    <a href="recuperar-senha.php" class="forgot-password">Esqueceu a senha?</a>
                </div>

                <button type="submit" class="btn-login">Entrar</button>
            </form>

            <div class="divider">───────</div>

            <div class="back-link">
                <a href="../../login.php">← Voltar para login principal</a>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('senha');
            const toggleIcon = document.querySelector('.password-toggle');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.textContent = 'visibility';
            } else {
                passwordInput.type = 'password';
                toggleIcon.textContent = 'visibility_off';
            }
        }
    </script>
</body>
</html>

