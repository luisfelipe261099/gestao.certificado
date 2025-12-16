<?php
require_once '../app/config/config.php';

if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    redirect(APP_URL . '/login.php');
}

$page_title = 'Tutoriais - ' . APP_NAME;
require_once '../app/views/admin-layout-header.php';
?>

<div class="page-header">
    <h1>Tutoriais e Ajuda</h1>
</div>

<div class="card">
    <div class="card-header py-3">
        <ul class="nav nav-tabs card-header-tabs" id="tutorialTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="admin-tab" data-toggle="tab" href="#admin" role="tab"
                    aria-controls="admin" aria-selected="true">Administrador</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="parceiro-tab" data-toggle="tab" href="#parceiro" role="tab"
                    aria-controls="parceiro" aria-selected="false">Parceiro</a>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content" id="tutorialTabsContent">
            <!-- Admin Tab -->
            <div class="tab-pane fade show active" id="admin" role="tabpanel" aria-labelledby="admin-tab">
                <h4 class="mb-3">Guias para Administradores</h4>

                <div class="accordion" id="accordionAdmin">
                    <div class="card mb-2">
                        <div class="card-header" id="headingOne">
                            <h5 class="mb-0">
                                <button class="btn btn-link" type="button" data-toggle="collapse"
                                    data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    Como criar um novo parceiro?
                                </button>
                            </h5>
                        </div>
                        <div id="collapseOne" class="collapse show" aria-labelledby="headingOne"
                            data-parent="#accordionAdmin">
                            <div class="card-body">
                                Vá até o menu <strong>Parceiros</strong>, clique em "Novo Parceiro" e preencha os dados
                                da empresa. O parceiro receberá um email com as credenciais de acesso.
                            </div>
                        </div>
                    </div>

                    <div class="card mb-2">
                        <div class="card-header" id="headingTwo">
                            <h5 class="mb-0">
                                <button class="btn btn-link collapsed" type="button" data-toggle="collapse"
                                    data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    Como gerenciar planos?
                                </button>
                            </h5>
                        </div>
                        <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo"
                            data-parent="#accordionAdmin">
                            <div class="card-body">
                                Acesse <strong>Sistema > Planos</strong>. Lá você pode editar os valores, limites de
                                certificados e opções de parcelamento de cada plano.
                            </div>
                        </div>
                    </div>

                    <div class="card mb-2">
                        <div class="card-header" id="headingThree">
                            <h5 class="mb-0">
                                <button class="btn btn-link collapsed" type="button" data-toggle="collapse"
                                    data-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    Como adicionar novos administradores?
                                </button>
                            </h5>
                        </div>
                        <div id="collapseThree" class="collapse" aria-labelledby="headingThree"
                            data-parent="#accordionAdmin">
                            <div class="card-body">
                                Vá em <strong>Sistema > Usuários Admin</strong>. Clique em "Novo Administrador" e defina
                                o nome, email e senha.
                            </div>
                        </div>
                    </div>

                    <div class="card mb-2">
                        <div class="card-header" id="headingFour">
                            <h5 class="mb-0">
                                <button class="btn btn-link collapsed" type="button" data-toggle="collapse"
                                    data-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                    Como editar templates do sistema?
                                </button>
                            </h5>
                        </div>
                        <div id="collapseFour" class="collapse" aria-labelledby="headingFour"
                            data-parent="#accordionAdmin">
                            <div class="card-body">
                                Acesse <strong>Sistema > Templates do Sistema</strong>. Lá você pode criar novos modelos
                                ou editar os existentes. Lembre-se que estes templates são visíveis para todos os
                                parceiros.
                            </div>
                        </div>
                    </div>

                    <div class="card mb-2">
                        <div class="card-header" id="headingFive">
                            <h5 class="mb-0">
                                <button class="btn btn-link collapsed" type="button" data-toggle="collapse"
                                    data-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                                    Como visualizar relatórios financeiros?
                                </button>
                            </h5>
                        </div>
                        <div id="collapseFive" class="collapse" aria-labelledby="headingFive"
                            data-parent="#accordionAdmin">
                            <div class="card-body">
                                No menu <strong>Financeiro</strong>, você tem acesso a Faturas, Pagamentos e Receitas. O
                                dashboard principal também mostra um resumo das assinaturas ativas.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button class="button button-primary"
                        onclick="localStorage.removeItem('admin_tour_seen'); window.location.href='dashboard-admin.php';">
                        Reiniciar Tour Interativo
                    </button>
                </div>
            </div>

            <!-- Parceiro Tab -->
            <div class="tab-pane fade" id="parceiro" role="tabpanel" aria-labelledby="parceiro-tab">
                <h4 class="mb-3">O que o Parceiro vê?</h4>
                <p class="text-muted">Abaixo estão os guias disponíveis para os parceiros.</p>

                <div class="accordion" id="accordionParceiro">
                    <div class="card mb-2">
                        <div class="card-header" id="headingPOne">
                            <h5 class="mb-0">
                                <button class="btn btn-link" type="button" data-toggle="collapse"
                                    data-target="#collapsePOne" aria-expanded="true" aria-controls="collapsePOne">
                                    Como emitir um certificado?
                                </button>
                            </h5>
                        </div>
                        <div id="collapsePOne" class="collapse show" aria-labelledby="headingPOne"
                            data-parent="#accordionParceiro">
                            <div class="card-body">
                                O parceiro deve ir em "Gerar Certificado", selecionar o aluno, o curso e o template
                                desejado. O sistema gerará o PDF automaticamente.
                            </div>
                        </div>
                    </div>

                    <div class="card mb-2">
                        <div class="card-header" id="headingPTwo">
                            <h5 class="mb-0">
                                <button class="btn btn-link collapsed" type="button" data-toggle="collapse"
                                    data-target="#collapsePTwo" aria-expanded="false" aria-controls="collapsePTwo">
                                    Como personalizar meu template?
                                </button>
                            </h5>
                        </div>
                        <div id="collapsePTwo" class="collapse" aria-labelledby="headingPTwo"
                            data-parent="#accordionParceiro">
                            <div class="card-body">
                                No menu "Meus Templates", o parceiro pode criar um novo template ou editar um existente.
                                É possível alterar a imagem de fundo, fontes, cores e posicionamento dos elementos.
                            </div>
                        </div>
                    </div>

                    <div class="card mb-2">
                        <div class="card-header" id="headingPThree">
                            <h5 class="mb-0">
                                <button class="btn btn-link collapsed" type="button" data-toggle="collapse"
                                    data-target="#collapsePThree" aria-expanded="false" aria-controls="collapsePThree">
                                    Como comprar mais créditos?
                                </button>
                            </h5>
                        </div>
                        <div id="collapsePThree" class="collapse" aria-labelledby="headingPThree"
                            data-parent="#accordionParceiro">
                            <div class="card-body">
                                O parceiro pode solicitar uma mudança de plano ou renovar sua assinatura através do
                                painel financeiro (se habilitado) ou entrando em contato com o administrador.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../app/views/admin-layout-footer.php'; ?>