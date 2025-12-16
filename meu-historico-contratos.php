<?php
/**
 * Página de Histórico de Contratos
 * Visualizar contratos assinados anteriormente
 */

require_once 'app/config/config.php';
require_once 'app/models/Contrato.php';

// Verificar se está logado
if (!isAuthenticated()) {
    redirect('login.php');
}

$user = getCurrentUser();
$user_role = $_SESSION['user_role'];

// Para parceiros, usar o parceiro_id; para outros, usar user_id
if ($user_role === 'parceiro' && isset($_SESSION['parceiro_id'])) {
    $user_id = $_SESSION['parceiro_id'];
} else {
    $user_id = $_SESSION['user_id'];
}

$conn = getDBConnection();
$contrato_model = new Contrato($conn);

// Obter contratos do usuário
$contratos = $contrato_model->obter_contratos_usuario($user_id, $user_role);

$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Histórico de Contratos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f5f5;
            padding: 20px 0;
        }
        
        .container-historico {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .header-historico {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .header-historico h1 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }
        
        .header-historico p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        
        .card-contrato {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .card-contrato:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .card-header-contrato {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header-contrato h3 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
        }
        
        .badge-tipo {
            background: rgba(255,255,255,0.3);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
        }
        
        .card-body-contrato {
            padding: 20px;
        }
        
        .info-contrato {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-weight: bold;
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .info-valor {
            color: #333;
            font-size: 16px;
        }
        
        .assinatura-preview {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            background: #f9f9f9;
            text-align: center;
        }
        
        .assinatura-preview img {
            max-width: 100%;
            max-height: 100px;
        }
        
        .botoes-acao {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .botoes-acao button {
            flex: 1;
        }
        
        .vazio {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .vazio i {
            font-size: 48px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-ativo {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inativo {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container-historico">
        <div class="header-historico">
            <h1>📋 Meu Histórico de Contratos</h1>
            <p>Visualize todos os contratos que você assinou</p>
        </div>
        
        <?php if (empty($contratos)): ?>
            <div class="card-contrato">
                <div class="vazio">
                    <i class="fas fa-file-contract"></i>
                    <h3>Nenhum contrato assinado</h3>
                    <p>Você ainda não assinou nenhum contrato.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($contratos as $contrato): ?>
                <div class="card-contrato">
                    <div class="card-header-contrato">
                        <div>
                            <h3><?php echo htmlspecialchars($contrato['titulo']); ?></h3>
                            <small>Versão <?php echo $contrato['versao']; ?></small>
                        </div>
                        <div class="badge-tipo">
                            <?php echo ucfirst(str_replace('_', ' ', $contrato['tipo'])); ?>
                        </div>
                    </div>
                    
                    <div class="card-body-contrato">
                        <div class="info-contrato">
                            <div class="info-item">
                                <span class="info-label">📅 Data de Assinatura</span>
                                <span class="info-valor">
                                    <?php echo date('d/m/Y H:i', strtotime($contrato['data_assinatura'])); ?>
                                </span>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label">✅ Status</span>
                                <span class="info-valor">
                                    <span class="status-badge <?php echo $contrato['ativo'] ? 'status-ativo' : 'status-inativo'; ?>">
                                        <?php echo $contrato['ativo'] ? '✅ Ativo' : '❌ Inativo'; ?>
                                    </span>
                                </span>
                            </div>
                            
                            <?php if ($contrato['data_validade']): ?>
                                <div class="info-item">
                                    <span class="info-label">📆 Data de Validade</span>
                                    <span class="info-valor">
                                        <?php echo date('d/m/Y', strtotime($contrato['data_validade'])); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="info-item">
                                <span class="info-label">🌐 IP de Assinatura</span>
                                <span class="info-valor">
                                    <?php echo htmlspecialchars($contrato['ip_assinatura']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <?php if ($contrato['assinatura_digital']): ?>
                            <div class="info-item" style="margin-bottom: 20px;">
                                <span class="info-label">✍️ Assinatura Digital</span>
                                <div class="assinatura-preview">
                                    <img src="<?php echo htmlspecialchars($contrato['assinatura_digital']); ?>" alt="Assinatura">
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="botoes-acao">
                            <button class="btn btn-primary" onclick="visualizarContrato(<?php echo $contrato['id']; ?>)">
                                <i class="fas fa-eye"></i> Visualizar Contrato
                            </button>
                            <button class="btn btn-secondary" onclick="downloadContrato(<?php echo $contrato['id']; ?>)">
                                <i class="fas fa-download"></i> Baixar PDF
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="<?php echo $user_role === 'admin' ? DIR_ADMIN . '/dashboard-admin.php' : DIR_PARCEIRO . '/dashboard-parceiro.php'; ?>" class="btn btn-outline-primary">
                ← Voltar ao Dashboard
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function visualizarContrato(id) {
            // Implementar visualização do contrato
            alert('Funcionalidade em desenvolvimento: Visualizar contrato #' + id);
        }
        
        function downloadContrato(id) {
            // Implementar download do contrato em PDF
            alert('Funcionalidade em desenvolvimento: Baixar contrato #' + id);
        }
    </script>
</body>
</html>

