<?php
/**
 * Registro de Novo Parceiro
 * Sistema EAD Pro
 */

require_once '../config/database.php';
require_once '../app/models/Parceiro.php';

iniciar_sessao();

// Se já está logado, redirecionar para dashboard
if (isset($_SESSION['usuario_id']) && $_SESSION['tipo_usuario'] === 'parceiro') {
    header('Location: dashboard.php');
    exit;
}

$erro = '';
$sucesso = '';
$dados_form = [];

// Processar formulário de registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados_form = [
        'nome' => sanitizar($_POST['nome'] ?? ''),
        'email' => sanitizar($_POST['email'] ?? ''),
        'empresa' => sanitizar($_POST['empresa'] ?? ''),
        'cpf' => sanitizar($_POST['cpf'] ?? ''),
        'telefone' => sanitizar($_POST['telefone'] ?? ''),
        'descricao' => sanitizar($_POST['descricao'] ?? ''),
        'senha' => $_POST['senha'] ?? '',
        'confirmar_senha' => $_POST['confirmar_senha'] ?? ''
    ];
    
    // Validações
    if (empty($dados_form['nome'])) {
        $erro = 'Nome é obrigatório';
    } elseif (empty($dados_form['email'])) {
        $erro = 'Email é obrigatório';
    } elseif (!filter_var($dados_form['email'], FILTER_VALIDATE_EMAIL)) {
        $erro = 'Email inválido';
    } elseif (empty($dados_form['senha'])) {
        $erro = 'Senha é obrigatória';
    } elseif (strlen($dados_form['senha']) < 8) {
        $erro = 'Senha deve ter no mínimo 8 caracteres';
    } elseif ($dados_form['senha'] !== $dados_form['confirmar_senha']) {
        $erro = 'Senhas não conferem';
    } elseif (!isset($_POST['termos'])) {
        $erro = 'Você deve aceitar os termos de serviço';
    } else {
        // Tentar registrar
        $parceiro_model = new Parceiro($pdo);
        $resultado = $parceiro_model->registrar($dados_form);
        
        if ($resultado['sucesso']) {
            $sucesso = 'Registro realizado com sucesso! Verifique seu email para ativar a conta.';
            $dados_form = []; // Limpar formulário
        } else {
            $erro = $resultado['mensagem'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Parceiro EAD Pro</title>
    <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #9c166f 0%, #6b0f52 100%);
            min-height: 100vh;
            padding: 40px 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .registro-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            padding: 40px;
        }
        
        .registro-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .registro-header h1 {
            color: #667eea;
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .registro-header p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group label {
            color: #333;
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .form-control {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 12px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .form-row.full {
            grid-template-columns: 1fr;
        }
        
        .btn-registro {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px;
            font-weight: 600;
            border-radius: 5px;
            width: 100%;
            margin-top: 20px;
            transition: all 0.3s;
        }
        
        .btn-registro:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .alert {
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .form-links {
            text-align: center;
            margin-top: 20px;
            font-size: 13px;
        }
        
        .form-links a {
            color: #667eea;
            text-decoration: none;
        }
        
        .form-links a:hover {
            text-decoration: underline;
        }
        
        .custom-control-label {
            font-size: 13px;
            color: #666;
        }
        
        .password-strength {
            margin-top: 5px;
            font-size: 12px;
        }
        
        .strength-bar {
            height: 4px;
            background: #ddd;
            border-radius: 2px;
            margin-top: 5px;
            overflow: hidden;
        }
        
        .strength-fill {
            height: 100%;
            width: 0%;
            transition: all 0.3s;
        }
    </style>
</head>
<body>
    <div class="registro-container">
        <div class="registro-header">
            <h1><i class="fas fa-graduation-cap"></i> EAD Pro</h1>
            <p>Criar Conta de Parceiro</p>
        </div>
        
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
        
        <form method="POST" action="">
            <div class="form-row full">
                <div class="form-group">
                    <label for="nome">Nome Completo *</label>
                    <input type="text" class="form-control" id="nome" name="nome" 
                           value="<?php echo $dados_form['nome'] ?? ''; ?>" required>
                </div>
            </div>
            
            <div class="form-row full">
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo $dados_form['email'] ?? ''; ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="empresa">Empresa</label>
                    <input type="text" class="form-control" id="empresa" name="empresa" 
                           value="<?php echo $dados_form['empresa'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label for="cpf">CPF</label>
                    <input type="text" class="form-control" id="cpf" name="cpf" 
                           placeholder="000.000.000-00" value="<?php echo $dados_form['cpf'] ?? ''; ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="telefone">Telefone</label>
                    <input type="tel" class="form-control" id="telefone" name="telefone" 
                           placeholder="(00) 00000-0000" value="<?php echo $dados_form['telefone'] ?? ''; ?>">
                </div>
            </div>
            
            <div class="form-row full">
                <div class="form-group">
                    <label for="descricao">Descrição (Sobre você)</label>
                    <textarea class="form-control" id="descricao" name="descricao" 
                              rows="3"><?php echo $dados_form['descricao'] ?? ''; ?></textarea>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="senha">Senha *</label>
                    <input type="password" class="form-control" id="senha" name="senha" 
                           placeholder="Mínimo 8 caracteres" required>
                    <div class="strength-bar">
                        <div class="strength-fill" id="strengthFill"></div>
                    </div>
                    <small class="text-muted">Mínimo 8 caracteres, com letras e números</small>
                </div>
                <div class="form-group">
                    <label for="confirmar_senha">Confirmar Senha *</label>
                    <input type="password" class="form-control" id="confirmar_senha" 
                           name="confirmar_senha" placeholder="Confirme a senha" required>
                </div>
            </div>
            
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="termos" name="termos" required>
                <label class="custom-control-label" for="termos">
                    Concordo com os <a href="#" style="color: #667eea;">termos de serviço</a> e 
                    <a href="#" style="color: #667eea;">política de privacidade</a>
                </label>
            </div>
            
            <button type="submit" class="btn btn-registro">
                <i class="fas fa-user-plus"></i> Criar Conta
            </button>
            
            <div class="form-links">
                Já tem conta? <a href="login.php">Faça login aqui</a>
            </div>
        </form>
    </div>
    
    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        // Verificar força da senha
        document.getElementById('senha').addEventListener('input', function() {
            const senha = this.value;
            let forca = 0;
            
            if (senha.length >= 8) forca += 25;
            if (senha.length >= 12) forca += 25;
            if (/[a-z]/.test(senha) && /[A-Z]/.test(senha)) forca += 25;
            if (/[0-9]/.test(senha)) forca += 25;
            
            document.getElementById('strengthFill').style.width = forca + '%';
            
            if (forca < 50) {
                document.getElementById('strengthFill').style.background = '#dc3545';
            } else if (forca < 75) {
                document.getElementById('strengthFill').style.background = '#ffc107';
            } else {
                document.getElementById('strengthFill').style.background = '#28a745';
            }
        });
    </script>
</body>
</html>

