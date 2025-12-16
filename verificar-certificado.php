<?php
require_once __DIR__ . '/app/actions/bootstrap.php';

$codigo = isset($_GET['codigo']) ? trim($_GET['codigo']) : '';
$certificado = null;
$erro = null;

if ($codigo) {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("
            SELECT c.*, a.nome as aluno_nome, a.cpf as aluno_cpf, 
                   cur.nome as curso_nome, cur.carga_horaria, 
                   p.nome_fantasia, p.razao_social
            FROM certificados c
            JOIN alunos a ON c.aluno_id = a.id
            JOIN cursos cur ON c.curso_id = cur.id
            JOIN parceiros p ON c.parceiro_id = p.id
            WHERE c.numero_certificado = ?
        ");
        $stmt->bind_param("s", $codigo);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $certificado = $result->fetch_assoc();
        } else {
            $erro = "Certificado não encontrado. Verifique o código e tente novamente.";
        }
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        $erro = "Erro ao verificar certificado: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação de Certificado - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }

        .verification-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            padding: 2.5rem;
            width: 100%;
            max-width: 600px;
            border-top: 5px solid #0d6efd;
        }

        .status-badge {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
        }

        .status-valid {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .status-invalid {
            background-color: #f8d7da;
            color: #842029;
        }

        .detail-row {
            margin-bottom: 1rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 1rem;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-size: 0.85rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }

        .detail-value {
            font-size: 1.1rem;
            font-weight: 500;
            color: #212529;
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="<?php echo APP_URL; ?>">
                <?php echo APP_NAME; ?>
            </a>
        </div>
    </nav>

    <div class="main-content container">
        <div class="verification-card">
            <h2 class="text-center mb-4 fw-bold">Verificação de Certificado</h2>

            <form action="" method="GET" class="mb-5">
                <div class="input-group input-group-lg">
                    <input type="text" class="form-control" name="codigo" placeholder="Digite o código do certificado"
                        value="<?php echo htmlspecialchars($codigo); ?>" required>
                    <button class="btn btn-primary" type="submit">Verificar</button>
                </div>
            </form>

            <?php if ($erro): ?>
                <div class="alert alert-danger text-center" role="alert">
                    <i class="bi bi-x-circle me-2"></i> <?php echo $erro; ?>
                </div>
            <?php endif; ?>

            <?php if ($certificado): ?>
                <div class="text-center mb-4">
                    <span class="status-badge status-valid">
                        <i class="bi bi-check-circle-fill me-1"></i> Certificado Válido
                    </span>
                </div>

                <div class="details-container">
                    <div class="detail-row">
                        <div class="detail-label">Aluno</div>
                        <div class="detail-value"><?php echo htmlspecialchars($certificado['aluno_nome']); ?></div>
                    </div>

                    <div class="detail-row">
                        <div class="detail-label">Curso</div>
                        <div class="detail-value"><?php echo htmlspecialchars($certificado['curso_nome']); ?></div>
                    </div>

                    <div class="detail-row">
                        <div class="detail-label">Carga Horária</div>
                        <div class="detail-value"><?php echo htmlspecialchars($certificado['carga_horaria']); ?> horas</div>
                    </div>

                    <div class="detail-row">
                        <div class="detail-label">Data de Conclusão</div>
                        <div class="detail-value">
                            <?php
                            // Tentar pegar data de conclusão da tabela de inscrições se possível, ou usar data de emissão/validade
                            // Aqui vamos usar a data de emissão como referência se não tiver outra
                            echo date('d/m/Y', strtotime($certificado['criado_em']));
                            ?>
                        </div>
                    </div>

                    <div class="detail-row">
                        <div class="detail-label">Instituição</div>
                        <div class="detail-value">
                            <?php echo htmlspecialchars($certificado['nome_fantasia'] ?: $certificado['razao_social']); ?>
                        </div>
                    </div>

                    <div class="detail-row">
                        <div class="detail-label">Código de Autenticidade</div>
                        <div class="detail-value text-primary font-monospace">
                            <?php echo htmlspecialchars($certificado['numero_certificado']); ?>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <a href="<?php echo $certificado['arquivo_url']; ?>" target="_blank" class="btn btn-outline-primary">
                        <i class="bi bi-file-earmark-pdf me-2"></i> Visualizar PDF Original
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer class="bg-white py-4 mt-auto border-top">
        <div class="container text-center text-muted">
            <small>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Todos os direitos reservados.</small>
        </div>
    </footer>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</body>

</html>