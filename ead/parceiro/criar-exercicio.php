<?php
/**
 * Página de Criar Exercício com Questões
 * Sistema EAD Pro
 */

require_once '../config/database.php';
require_once '../app/models/Aula.php';
require_once '../app/models/Exercicio.php';
require_once '../app/models/Questao.php';

// Verificar autenticação
verificar_autenticacao('parceiro');

$parceiro_id = $_SESSION['ead_parceiro_id'];
$aula_model = new Aula($pdo);
$exercicio_model = new Exercicio($pdo);
$questao_model = new Questao($pdo);
$aula_id = (int)($_GET['aula_id'] ?? $_POST['aula_id'] ?? 0);
$erros = [];
$aula = null;

// Criar tabela opcoes_questoes se não existir
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS opcoes_questoes (
            id INT(11) PRIMARY KEY AUTO_INCREMENT,
            questao_id INT(11) NOT NULL,
            texto VARCHAR(500) NOT NULL,
            eh_correta TINYINT(1) DEFAULT 0,
            ordem INT(11) DEFAULT 1,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (questao_id) REFERENCES questoes_exercicios(id) ON DELETE CASCADE,
            KEY idx_questao (questao_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (Exception $e) {
    // Tabela já existe
}

// Obter todas as aulas do parceiro
$stmt = $pdo->prepare('
    SELECT a.*, c.nome as curso_nome FROM aulas a
    INNER JOIN cursos c ON a.curso_id = c.id
    WHERE c.parceiro_id = ? AND a.ativa = 1
    ORDER BY c.nome, a.ordem
');
$stmt->execute([$parceiro_id]);
$todas_aulas = $stmt->fetchAll();

if ($aula_id > 0) {
    $aula = $aula_model->obter_por_id($aula_id);

    if (!$aula) {
        $_SESSION['mensagem'] = 'Aula não encontrada!';
        $_SESSION['tipo_mensagem'] = 'danger';
        header('Location: aulas.php');
        exit;
    }

    // Verificar se a aula pertence ao parceiro
    $stmt = $pdo->prepare('SELECT parceiro_id FROM cursos WHERE id = ?');
    $stmt->execute([$aula['curso_id']]);
    $curso_check = $stmt->fetch();

    if ($curso_check['parceiro_id'] != $parceiro_id) {
        $_SESSION['mensagem'] = 'Acesso negado!';
        $_SESSION['tipo_mensagem'] = 'danger';
        header('Location: aulas.php');
        exit;
    }
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['criar_exercicio'])) {
    $titulo = sanitizar($_POST['titulo'] ?? '');
    $descricao = sanitizar($_POST['descricao'] ?? '');
    $questoes_data = json_decode($_POST['questoes_json'] ?? '[]', true);
    
    // Validações
    if (empty($titulo)) {
        $erros[] = 'Título do exercício é obrigatório';
    }
    if (empty($questoes_data)) {
        $erros[] = 'Adicione pelo menos uma questão';
    }
    
    // Validar questões
    foreach ($questoes_data as $index => $questao) {
        $num = $index + 1;
        if (empty($questao['titulo'])) {
            $erros[] = "Questão $num: Enunciado é obrigatório";
        }
        if ($questao['tipo'] === 'multipla_escolha') {
            if (empty($questao['opcoes']) || count($questao['opcoes']) < 2) {
                $erros[] = "Questão $num: Adicione pelo menos 2 alternativas";
            }
            $tem_correta = false;
            foreach ($questao['opcoes'] as $opcao) {
                if ($opcao['correta']) {
                    $tem_correta = true;
                    break;
                }
            }
            if (!$tem_correta) {
                $erros[] = "Questão $num: Marque a alternativa correta";
            }
        }
    }
    
    // Se não houver erros, criar exercício e questões
    if (empty($erros)) {
        try {
            $pdo->beginTransaction();
            
            // Criar exercício
            $resultado_exercicio = $exercicio_model->criar([
                'aula_id' => $aula_id,
                'titulo' => $titulo,
                'descricao' => $descricao,
                'tipo' => 'multipla_escolha',
                'pontuacao_maxima' => count($questoes_data)
            ]);
            
            if (!$resultado_exercicio['sucesso']) {
                throw new Exception($resultado_exercicio['erro']);
            }
            
            $exercicio_id = $resultado_exercicio['id'];
            
            // Criar questões
            foreach ($questoes_data as $index => $questao) {
                $resultado_questao = $questao_model->criar([
                    'exercicio_id' => $exercicio_id,
                    'titulo' => $questao['titulo'],
                    'descricao' => $questao['descricao'] ?? null,
                    'tipo' => $questao['tipo'],
                    'ordem' => $index + 1,
                    'pontuacao' => 1
                ]);
                
                if (!$resultado_questao['sucesso']) {
                    throw new Exception($resultado_questao['erro']);
                }
                
                $questao_id = $resultado_questao['id'];
                
                // Criar opções (se for múltipla escolha)
                if ($questao['tipo'] === 'multipla_escolha' && !empty($questao['opcoes'])) {
                    foreach ($questao['opcoes'] as $ordem => $opcao) {
                        $stmt = $pdo->prepare('
                            INSERT INTO opcoes_questoes (questao_id, texto, eh_correta, ordem)
                            VALUES (?, ?, ?, ?)
                        ');
                        $stmt->execute([
                            $questao_id,
                            $opcao['texto'],
                            $opcao['correta'] ? 1 : 0,
                            $ordem + 1
                        ]);
                    }
                }
            }
            
            $pdo->commit();
            
            $_SESSION['mensagem'] = 'Exercício criado com sucesso!';
            $_SESSION['tipo_mensagem'] = 'success';
            header('Location: exercicios.php?aula_id=' . $aula_id);
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $erros[] = 'Erro ao criar exercício: ' . $e->getMessage();
        }
    }
}

$titulo_pagina = 'Criar Exercício';
require_once '../includes/ead-layout-header.php';
?>

<!-- Cabeçalho -->
<div style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.08) 0%, rgba(139, 95, 214, 0.04) 100%); border-radius: 16px; padding: 28px 32px; margin-bottom: 28px;">
    <div style="display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="width: 56px; height: 56px; background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); border-radius: 14px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(110, 65, 193, 0.3);">
                <span class="material-icons-outlined" style="font-size: 28px; color: white;">quiz</span>
            </div>
            <div>
                <h1 style="font-size: 28px; font-weight: 700; color: #1D1D1F; margin: 0;">Criar Exercício</h1>
                <p style="color: #86868B; font-size: 14px; margin: 4px 0 0 0;">Monte questões de múltipla escolha para avaliar seus alunos</p>
            </div>
        </div>
        <a href="exercicios.php<?php echo $aula_id > 0 ? '?aula_id=' . $aula_id : ''; ?>" class="button button-secondary" style="text-decoration: none;">
            <span class="material-icons-outlined">arrow_back</span>
            <span>Voltar</span>
        </a>
    </div>
</div>

<?php if ($aula): ?>
    <!-- Aula Selecionada -->
    <div class="card" style="margin-bottom: 24px;">
        <div style="display: flex; align-items: center; gap: 12px; padding: 16px; background: linear-gradient(135deg, rgba(110, 65, 193, 0.05) 0%, rgba(139, 95, 214, 0.02) 100%); border-radius: 12px;">
            <span class="material-icons-outlined" style="color: #6E41C1; font-size: 32px;">school</span>
            <div>
                <div style="font-size: 13px; color: #86868B; margin-bottom: 2px;">Aula Selecionada</div>
                <div style="font-size: 16px; font-weight: 600; color: #1D1D1F;"><?php echo htmlspecialchars($aula['titulo']); ?></div>
            </div>
        </div>
    </div>

    <!-- Erros -->
    <?php if (!empty($erros)): ?>
        <div class="alert alert-danger" style="margin-bottom: 24px;">
            <div style="display: flex; align-items: start; gap: 12px;">
                <span class="material-icons-outlined" style="color: #FF3B30; flex-shrink: 0;">error</span>
                <div>
                    <strong>Erros encontrados:</strong>
                    <ul style="margin: 8px 0 0 0; padding-left: 20px;">
                        <?php foreach ($erros as $erro): ?>
                            <li><?php echo htmlspecialchars($erro); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Formulário -->
    <form method="POST" id="form-exercicio">
        <input type="hidden" name="criar_exercicio" value="1">
        <input type="hidden" name="aula_id" value="<?php echo $aula_id; ?>">
        <input type="hidden" name="questoes_json" id="questoes_json" value="">

        <!-- Dados do Exercício -->
        <div class="card" style="margin-bottom: 24px;">
            <h2>
                <span class="material-icons-outlined">description</span>
                Dados do Exercício
            </h2>

            <div style="margin-bottom: 20px;">
                <label for="titulo" style="display: block; font-weight: 600; color: #1D1D1F; margin-bottom: 8px;">
                    Título do Exercício <span style="color: #FF3B30;">*</span>
                </label>
                <input type="text" class="form-control" id="titulo" name="titulo"
                       placeholder="Ex: Avaliação sobre Variáveis em PHP" required
                       value="<?php echo htmlspecialchars($_POST['titulo'] ?? ''); ?>">
            </div>

            <div>
                <label for="descricao" style="display: block; font-weight: 600; color: #1D1D1F; margin-bottom: 8px;">
                    Descrição (Opcional)
                </label>
                <textarea class="form-control" id="descricao" name="descricao" rows="3"
                          placeholder="Instruções para o aluno..."><?php echo htmlspecialchars($_POST['descricao'] ?? ''); ?></textarea>
            </div>
        </div>

        <!-- Questões -->
        <div class="card">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                <h2 style="margin: 0;">
                    <span class="material-icons-outlined">quiz</span>
                    Questões
                </h2>
                <button type="button" onclick="adicionarQuestao()" class="button button-primary" style="padding: 10px 16px; font-size: 14px;">
                    <span class="material-icons-outlined">add</span>
                    <span>Adicionar Questão</span>
                </button>
            </div>

            <div id="questoes-container">
                <!-- Questões serão adicionadas aqui via JavaScript -->
            </div>

            <div id="empty-state" style="text-align: center; padding: 48px 24px; color: #86868B;">
                <span class="material-icons-outlined" style="font-size: 64px; color: #E5E5E7; margin-bottom: 16px;">quiz</span>
                <p style="font-size: 16px; margin: 0;">Nenhuma questão adicionada</p>
                <p style="font-size: 14px; margin: 8px 0 0 0;">Clique em "Adicionar Questão" para começar</p>
            </div>
        </div>

        <!-- Botões -->
        <div style="display: flex; gap: 12px; margin-top: 24px;">
            <button type="submit" class="button button-primary">
                <span class="material-icons-outlined">save</span>
                <span>Criar Exercício</span>
            </button>
            <a href="exercicios.php?aula_id=<?php echo $aula_id; ?>" class="button button-secondary" style="text-decoration: none;">
                <span class="material-icons-outlined">close</span>
                <span>Cancelar</span>
            </a>
        </div>
    </form>

<?php else: ?>
    <!-- Seleção de Aula -->
    <?php if (empty($todas_aulas)): ?>
        <div class="card">
            <div style="text-align: center; padding: 48px 24px; color: #86868B;">
                <span class="material-icons-outlined" style="font-size: 64px; color: #E5E5E7; margin-bottom: 16px;">school</span>
                <p style="font-size: 16px; margin: 0 0 8px 0; color: #1D1D1F; font-weight: 600;">Nenhuma aula disponível</p>
                <p style="font-size: 14px; margin: 0 0 20px 0;">Crie uma aula primeiro para adicionar exercícios</p>
                <a href="criar-aula.php" class="button button-primary" style="text-decoration: none;">
                    <span class="material-icons-outlined">add</span>
                    <span>Criar Aula</span>
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <h2>
                <span class="material-icons-outlined">school</span>
                Selecione uma Aula
            </h2>

            <div style="display: grid; gap: 16px;">
                <?php foreach ($todas_aulas as $a): ?>
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 20px; background: #F5F5F7; border-radius: 12px; border: 2px solid #E5E5E7; transition: all 0.2s ease;"
                         onmouseover="this.style.borderColor='#6E41C1'; this.style.background='rgba(110, 65, 193, 0.05)';"
                         onmouseout="this.style.borderColor='#E5E5E7'; this.style.background='#F5F5F7';">
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                                <span class="material-icons-outlined" style="color: #6E41C1; font-size: 24px;">play_circle</span>
                                <h3 style="font-size: 16px; font-weight: 600; color: #1D1D1F; margin: 0;">
                                    <?php echo htmlspecialchars($a['titulo']); ?>
                                </h3>
                            </div>
                            <div style="display: flex; gap: 20px; margin-left: 36px;">
                                <div style="display: flex; align-items: center; gap: 6px; color: #86868B; font-size: 13px;">
                                    <span class="material-icons-outlined" style="font-size: 16px;">book</span>
                                    <span><?php echo htmlspecialchars($a['curso_nome']); ?></span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 6px; color: #86868B; font-size: 13px;">
                                    <span class="material-icons-outlined" style="font-size: 16px;">schedule</span>
                                    <span><?php echo $a['duracao_minutos'] ? $a['duracao_minutos'] . ' min' : 'Não definida'; ?></span>
                                </div>
                            </div>
                        </div>
                        <a href="criar-exercicio.php?aula_id=<?php echo $a['id']; ?>" class="button button-primary" style="text-decoration: none;">
                            <span class="material-icons-outlined">add</span>
                            <span>Criar Exercício</span>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<style>
.questao-card {
    background: white;
    border: 2px solid #E5E5E7;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 16px;
    transition: all 0.2s ease;
}

.questao-card:hover {
    border-color: #6E41C1;
    box-shadow: 0 4px 12px rgba(110, 65, 193, 0.1);
}

.opcao-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #F5F5F7;
    border-radius: 8px;
    margin-bottom: 8px;
}

.opcao-item input[type="text"] {
    flex: 1;
    border: 1px solid #E5E5E7;
    border-radius: 6px;
    padding: 8px 12px;
    font-size: 14px;
}

.opcao-item input[type="radio"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
    accent-color: #6E41C1;
}

.btn-remove {
    background: #FF3B30;
    color: white;
    border: none;
    border-radius: 6px;
    padding: 6px 12px;
    cursor: pointer;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 4px;
    transition: all 0.2s ease;
}

.btn-remove:hover {
    background: #E02020;
    transform: translateY(-1px);
}
</style>

<script>
let questoes = [];
let questaoCounter = 0;

function adicionarQuestao() {
    questaoCounter++;
    const questaoId = 'questao-' + questaoCounter;

    questoes.push({
        id: questaoId,
        titulo: '',
        descricao: '',
        tipo: 'multipla_escolha',
        opcoes: [
            { texto: '', correta: false },
            { texto: '', correta: false }
        ]
    });

    renderizarQuestoes();
}

function removerQuestao(questaoId) {
    if (confirm('Tem certeza que deseja remover esta questão?')) {
        questoes = questoes.filter(q => q.id !== questaoId);
        renderizarQuestoes();
    }
}

function adicionarOpcao(questaoId) {
    const questao = questoes.find(q => q.id === questaoId);
    if (questao) {
        questao.opcoes.push({ texto: '', correta: false });
        renderizarQuestoes();
    }
}

function removerOpcao(questaoId, opcaoIndex) {
    const questao = questoes.find(q => q.id === questaoId);
    if (questao && questao.opcoes.length > 2) {
        questao.opcoes.splice(opcaoIndex, 1);
        renderizarQuestoes();
    } else {
        alert('Mantenha pelo menos 2 alternativas');
    }
}

function marcarCorreta(questaoId, opcaoIndex) {
    const questao = questoes.find(q => q.id === questaoId);
    if (questao) {
        questao.opcoes.forEach((opcao, index) => {
            opcao.correta = (index === opcaoIndex);
        });
        renderizarQuestoes();
    }
}

function atualizarQuestao(questaoId, campo, valor) {
    const questao = questoes.find(q => q.id === questaoId);
    if (questao) {
        questao[campo] = valor;
    }
}

function atualizarOpcao(questaoId, opcaoIndex, valor) {
    const questao = questoes.find(q => q.id === questaoId);
    if (questao && questao.opcoes[opcaoIndex]) {
        questao.opcoes[opcaoIndex].texto = valor;
    }
}

function renderizarQuestoes() {
    const container = document.getElementById('questoes-container');
    const emptyState = document.getElementById('empty-state');

    if (questoes.length === 0) {
        container.innerHTML = '';
        emptyState.style.display = 'block';
        return;
    }

    emptyState.style.display = 'none';

    container.innerHTML = questoes.map((questao, qIndex) => `
        <div class="questao-card">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 32px; height: 32px; background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 14px;">
                        ${qIndex + 1}
                    </div>
                    <span style="font-weight: 600; color: #1D1D1F;">Questão ${qIndex + 1}</span>
                </div>
                <button type="button" onclick="removerQuestao('${questao.id}')" class="btn-remove">
                    <span class="material-icons-outlined" style="font-size: 16px;">delete</span>
                    Remover
                </button>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 600; color: #1D1D1F; margin-bottom: 8px;">
                    Enunciado <span style="color: #FF3B30;">*</span>
                </label>
                <textarea class="form-control" rows="2" placeholder="Digite o enunciado da questão..."
                          onchange="atualizarQuestao('${questao.id}', 'titulo', this.value)">${questao.titulo}</textarea>
            </div>

            <div style="margin-bottom: 16px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                    <label style="font-weight: 600; color: #1D1D1F; margin: 0;">
                        Alternativas <span style="color: #FF3B30;">*</span>
                    </label>
                    <button type="button" onclick="adicionarOpcao('${questao.id}')" class="button button-secondary" style="padding: 6px 12px; font-size: 13px;">
                        <span class="material-icons-outlined" style="font-size: 16px;">add</span>
                        Adicionar
                    </button>
                </div>

                ${questao.opcoes.map((opcao, oIndex) => `
                    <div class="opcao-item">
                        <input type="radio" name="correta-${questao.id}" ${opcao.correta ? 'checked' : ''}
                               onclick="marcarCorreta('${questao.id}', ${oIndex})"
                               title="Marcar como correta">
                        <input type="text" placeholder="Digite a alternativa ${String.fromCharCode(65 + oIndex)}..."
                               value="${opcao.texto}"
                               onchange="atualizarOpcao('${questao.id}', ${oIndex}, this.value)">
                        ${questao.opcoes.length > 2 ? `
                            <button type="button" onclick="removerOpcao('${questao.id}', ${oIndex})"
                                    style="background: #FF3B30; color: white; border: none; border-radius: 6px; padding: 6px; cursor: pointer;">
                                <span class="material-icons-outlined" style="font-size: 18px;">close</span>
                            </button>
                        ` : ''}
                    </div>
                `).join('')}

                <small style="display: block; margin-top: 8px; color: #86868B; font-size: 12px;">
                    <span class="material-icons-outlined" style="font-size: 14px; vertical-align: middle;">info</span>
                    Marque o círculo da alternativa correta
                </small>
            </div>
        </div>
    `).join('');
}

// Antes de enviar o formulário, salvar questões em JSON
document.getElementById('form-exercicio').addEventListener('submit', function(e) {
    if (questoes.length === 0) {
        e.preventDefault();
        alert('Adicione pelo menos uma questão!');
        return false;
    }

    // Salvar questões em JSON
    document.getElementById('questoes_json').value = JSON.stringify(questoes);
});

// Inicializar
renderizarQuestoes();
</script>

<?php
require_once '../includes/ead-layout-footer.php';
?>

