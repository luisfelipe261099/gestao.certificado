<?php
/**
 * ============================================================================
 * PÁGINA DE LOGIN - SISTEMA DE CERTIFICADOS
 * ============================================================================
 *
 * Esta é a página onde os usuários fazem login no sistema.
 * Aqui o usuário entra com seu email e senha para acessar o sistema.
 *
 * Padrão MVP - Camada de Apresentação
 * (Apresentação = o que o usuário vê na tela)
 * ============================================================================
 */

// Inclui o arquivo de configuração (com as funções e configurações)
require_once 'app/config/config.php';
require_once 'app/models/Contrato.php';

// ============================================================================
// PASSO 1: VERIFICAR SE JÁ ESTÁ LOGADO
// ============================================================================
// Se o usuário já está logado, não precisa fazer login novamente
// Então redirecionamos para o dashboard (painel) apropriado
if (isAuthenticated()) {
    // Verifica se é admin ou parceiro
    if (hasRole(ROLE_ADMIN)) {
        // Se é admin, vai para o dashboard do admin
        redirect(DIR_ADMIN . '/dashboard-admin.php');
    } else {
        // Se é parceiro, vai para o dashboard do parceiro
        redirect(DIR_PARCEIRO . '/dashboard-parceiro.php');
    }
}

// ============================================================================
// PASSO 2: INICIALIZAR VARIÁVEIS DE MENSAGEM
// ============================================================================
// Essas variáveis guardam mensagens de erro ou sucesso
$error = '';    // Mensagem de erro (se houver)
$success = '';  // Mensagem de sucesso (se houver)

// ============================================================================
// PASSO 3: PROCESSAR O FORMULÁRIO DE LOGIN
// ============================================================================
// Verifica se o usuário enviou o formulário (método POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Pega o email e a senha que o usuário digitou
    // sanitize() limpa os dados para evitar problemas de segurança
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // ========================================================================
    // VALIDAÇÃO 1: Verifica se email e senha foram preenchidos
    // ========================================================================
    if (empty($email) || empty($password)) {
        $error = 'Email e senha são obrigatórios.';
    }
    // ========================================================================
    // VALIDAÇÃO 2: Verifica se o email tem formato válido
    // ========================================================================
    elseif (!isValidEmail($email)) {
        $error = 'Email inválido.';
    }
    // ========================================================================
    // VALIDAÇÃO 3: Se passou nas validações, tenta fazer login
    // ========================================================================
    else {
        // Conecta ao banco de dados
        $conn = getDBConnection();
        $user = null;        // Variável para guardar dados do usuário
        $user_type = null;   // Variável para guardar tipo (admin ou parceiro)

        // ====================================================================
        // PASSO 3.1: PROCURAR USUÁRIO NA TABELA DE ADMINISTRADORES
        // ====================================================================
        // Primeiro, procuramos se é um admin
        // prepare() = prepara a consulta SQL de forma segura
        $stmt = $conn->prepare("
            SELECT id, email, nome, senha_hash, 'admin' as tipo_usuario
            FROM administradores
            WHERE email = ? AND ativo = 1
        ");

        if ($stmt) {
            // bind_param("s", $email) = substitui o ? pelo email
            // "s" significa que é uma string (texto)
            $stmt->bind_param("s", $email);
            $stmt->execute();  // Executa a consulta
            $result = $stmt->get_result(); // Pega o resultado

            // Se encontrou exatamente 1 resultado
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc(); // Pega os dados do usuário
                $user_type = 'admin'; // Marca que é admin
            }
            $stmt->close(); // Fecha a consulta
        }

        // ====================================================================
        // PASSO 3.2: SE NÃO ENCONTROU ADMIN, PROCURA NA TABELA DE PARCEIROS
        // ====================================================================
        if (!$user) {
            $stmt = $conn->prepare("
                SELECT id, email, nome, senha_hash, parceiro_id, 'parceiro' as tipo_usuario
                FROM usuarios_parceiro
                WHERE email = ? AND ativo = 1
            ");

            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    $user_type = 'parceiro'; // Marca que é parceiro
                }
                $stmt->close();
            }
        }

        // ====================================================================
        // PASSO 3.3: VERIFICAR SENHA
        // ====================================================================
        if ($user) {
            // password_verify() compara a senha digitada com a senha guardada
            // A senha guardada é "hasheada" (criptografada) por segurança
            if (password_verify($password, $user['senha_hash'])) {
                // ============================================================
                // SENHA CORRETA! CRIAR SESSÃO
                // ============================================================
                // Sessão = arquivo temporário com dados do usuário logado
                $_SESSION['user_id'] = $user['id'];           // ID do usuário
                $_SESSION['user_email'] = $user['email'];     // Email do usuário
                $_SESSION['user_name'] = $user['nome'];       // Nome do usuário
                $_SESSION['user_role'] = $user_type;          // Tipo (admin ou parceiro)
                $_SESSION['login_time'] = time();             // Hora do login

                // Se for parceiro, também guarda o ID do parceiro
                if ($user_type === 'parceiro' && isset($user['parceiro_id'])) {
                    $_SESSION['parceiro_id'] = $user['parceiro_id'];
                }

                // ============================================================
                // VERIFICAR SE PRECISA ACEITAR TERMOS (APENAS PARA PARCEIROS)
                // ============================================================
                // Apenas parceiros precisam aceitar termos
                if ($user_type === 'parceiro') {
                    $contrato_model = new Contrato($conn);
                    // Usar parceiro_id para parceiros, não user_id
                    $usuario_id_para_verificar = $_SESSION['parceiro_id'] ?? $_SESSION['user_id'];
                    // Verificar se aceitou o contrato de parceiro (tipo = 'contrato_parceiro')
                    $precisa_aceitar = !$contrato_model->usuario_aceitou_termos($usuario_id_para_verificar, $user_type, 'contrato_parceiro');

                    if ($precisa_aceitar) {
                        // Redirecionar para aceitar termos
                        redirect('aceitar-termos.php');
                    }
                }

                // Redireciona para o dashboard apropriado
                if ($user_type === 'admin') {
                    redirect(DIR_ADMIN . '/dashboard-admin.php');
                } else {
                    redirect(DIR_PARCEIRO . '/dashboard-parceiro.php');
                }
            } else {
                // Senha incorreta
                $error = 'Email ou senha incorretos.';
            }
        } else {
            // Email não encontrado
            $error = 'Email ou senha incorretos.';
        }

        $conn->close(); // Fecha a conexão com o banco
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Login - Sistema de Certificados">
    <title>Login - Sistema de Certificados</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>

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
            background: linear-gradient(135deg, #d946a6 0%, #8b3fa0 100%);
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
        }

        .login-left img {
            max-width: 200px;
            margin-bottom: 30px;
            filter: brightness(0) invert(1);
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
            border-color: #8b3fa0;
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
            color: #8b3fa0;
            text-decoration: none;
            font-weight: 500;
        }

        .forgot-password:hover {
            text-decoration: underline;
        }

        .recaptcha-container {
            margin-bottom: 20px;
            display: flex;
            justify-content: center;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #8b3fa0 0%, #d946a6 100%);
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
            box-shadow: 0 5px 20px rgba(139, 63, 160, 0.4);
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
            <img src="https://faciencia.edu.br/logo.png?v=1761920653447" alt="FaCiencia Logo">
            <h1>Gestão de Certificados</h1>
            <p>Acesso de parceiros FaCiencia</p>
        </div>

        <!-- Lado Direito -->
        <div class="login-right">
            <h2>Acesso ao Portal</h2>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <span class="material-icons-outlined">error_outline</span>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <span class="material-icons-outlined">check_circle_outline</span>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <span class="material-icons-outlined icon">person_outline</span>
                    <input type="email" name="email" placeholder="faciencia" required>
                </div>

                <div class="form-group">
                    <span class="material-icons-outlined icon">lock_outline</span>
                    <input type="password" name="password" id="password" placeholder="••••••••••" required>
                    <span class="material-icons-outlined password-toggle"
                        onclick="togglePassword()">visibility_off</span>
                </div>

                <div class="form-options">
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Lembrar-me</label>
                    </div>
                    <a href="recuperar-senha.php" class="forgot-password">Esqueceu a senha?</a>
                </div>

                <div class="recaptcha-container">
                    <div class="g-recaptcha" data-sitekey="6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI"></div>
                </div>

                <button type="submit" class="btn-login">Entrar</button>
            </form>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
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