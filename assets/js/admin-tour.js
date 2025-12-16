// admin-tour.js

document.addEventListener('DOMContentLoaded', function () {
    const path = window.location.pathname;
    const page = path.split('/').pop(); // Get filename (e.g., dashboard-admin.php)

    // Helper for "Create" buttons which are usually on the right
    const createBtnPosition = 'left';

    // Configuration for each page
    const tourConfig = {
        'dashboard-admin.php': {
            key: 'tour_seen_dashboard',
            steps: [
                {
                    title: 'Bem-vindo!',
                    intro: 'Bem-vindo ao Painel Administrativo do Gestão Certificado. Vamos fazer um tour rápido?',
                },
                {
                    element: '#accordionSidebar',
                    title: 'Menu Principal',
                    intro: 'Aqui você navega por todas as funcionalidades do sistema.',
                    position: 'right'
                },
                {
                    element: '.card-stats-parceiros',
                    title: 'Parceiros',
                    intro: 'Visualize rapidamente o total de parceiros cadastrados.',
                    position: 'bottom'
                },
                {
                    element: '#userDropdown',
                    title: 'Perfil',
                    intro: 'Acesse suas configurações de perfil e faça logout aqui.',
                    position: 'left'
                }
            ]
        },
        'parceiros-admin.php': {
            key: 'tour_seen_parceiros',
            steps: [
                {
                    title: 'Gerenciamento de Parceiros',
                    intro: 'Aqui você gerencia todas as empresas parceiras do sistema.'
                },
                {
                    element: '.button-primary',
                    title: 'Novo Parceiro',
                    intro: 'Clique aqui para cadastrar uma nova empresa parceira.',
                    position: createBtnPosition
                },
                {
                    element: '.table-responsive',
                    title: 'Lista de Parceiros',
                    intro: 'Aqui você vê a lista de todos os parceiros. Use os botões de ação para editar ou ver detalhes.',
                    position: 'top'
                }
            ]
        },
        'planos-admin.php': {
            key: 'tour_seen_planos',
            steps: [
                {
                    title: 'Planos de Assinatura',
                    intro: 'Gerencie os planos disponíveis para os parceiros.'
                },
                {
                    element: '.button-primary',
                    title: 'Novo Plano',
                    intro: 'Crie novos planos de assinatura com diferentes limites e valores.',
                    position: createBtnPosition
                },
                {
                    element: '.table-responsive',
                    title: 'Lista de Planos',
                    intro: 'Visualize e edite os planos existentes.',
                    position: 'top'
                }
            ]
        },
        'assinaturas-admin.php': {
            key: 'tour_seen_assinaturas',
            steps: [
                {
                    title: 'Assinaturas',
                    intro: 'Acompanhe todas as assinaturas ativas e seu status.'
                },
                {
                    element: '.table-responsive',
                    title: 'Lista de Assinaturas',
                    intro: 'Veja detalhes como data de início, fim e status de pagamento.',
                    position: 'top'
                }
            ]
        },
        'solicitacoes-planos.php': {
            key: 'tour_seen_solicitacoes',
            steps: [
                {
                    title: 'Solicitações de Planos',
                    intro: 'Gerencie solicitações de mudança de plano feitas pelos parceiros.'
                },
                {
                    element: '.table-responsive',
                    title: 'Solicitações Pendentes',
                    intro: 'Aprove ou rejeite as solicitações aqui.',
                    position: 'top'
                }
            ]
        },
        'contratos-admin.php': {
            key: 'tour_seen_contratos',
            steps: [
                {
                    title: 'Gerenciar Contratos',
                    intro: 'Configure os modelos de contrato que os parceiros devem aceitar.'
                },
                {
                    element: '#editor', // Assuming there is an editor or form
                    title: 'Editor de Contrato',
                    intro: 'Edite o texto do contrato aqui.',
                    position: 'top'
                }
            ]
        },
        'termos-admin.php': {
            key: 'tour_seen_termos',
            steps: [
                {
                    title: 'Gerenciar Termos',
                    intro: 'Configure os termos de uso do sistema.'
                }
            ]
        },
        'templates-sistema.php': {
            key: 'tour_seen_templates_sistema',
            steps: [
                {
                    title: 'Templates do Sistema',
                    intro: 'Gerencie os modelos de certificado padrão disponíveis para todos os parceiros.'
                },
                {
                    element: '.button-primary',
                    title: 'Novo Template',
                    intro: 'Adicione um novo modelo de certificado.',
                    position: createBtnPosition
                }
            ]
        },
        'faturas-admin.php': {
            key: 'tour_seen_faturas',
            steps: [
                {
                    title: 'Faturas',
                    intro: 'Visualize todas as faturas geradas pelo sistema.'
                }
            ]
        },
        'pagamentos-admin.php': {
            key: 'tour_seen_pagamentos',
            steps: [
                {
                    title: 'Pagamentos',
                    intro: 'Histórico de pagamentos recebidos.'
                }
            ]
        },
        'receitas-admin.php': {
            key: 'tour_seen_receitas',
            steps: [
                {
                    title: 'Receitas',
                    intro: 'Relatórios financeiros e gráficos de receita.'
                }
            ]
        },
        'boletos-asaas.php': {
            key: 'tour_seen_boletos',
            steps: [
                {
                    title: 'Boletos Asaas',
                    intro: 'Gerencie os boletos emitidos via integração Asaas.'
                }
            ]
        },
        'asaas-config.php': {
            key: 'tour_seen_asaas_config',
            steps: [
                {
                    title: 'Configuração Asaas',
                    intro: 'Configure sua chave de API e outras definições da integração Asaas.'
                }
            ]
        },
        'usuarios-admin.php': {
            key: 'tour_seen_usuarios_admin',
            steps: [
                {
                    title: 'Usuários Administrativos',
                    intro: 'Gerencie quem tem acesso ao painel administrativo.'
                },
                {
                    element: '.button-primary',
                    title: 'Novo Administrador',
                    intro: 'Adicione novos administradores ao sistema.',
                    position: createBtnPosition
                }
            ]
        },
        'tutoriais.php': {
            key: 'tour_seen_tutoriais',
            steps: [
                {
                    title: 'Central de Ajuda',
                    intro: 'Aqui você encontra guias detalhados sobre como usar o sistema.'
                },
                {
                    element: '#tutorialTabs',
                    title: 'Abas de Conteúdo',
                    intro: 'Alterne entre guias para Administradores e Parceiros.',
                    position: 'bottom'
                }
            ]
        }
    };

    // Check if configuration exists for current page
    if (tourConfig[page]) {
        const config = tourConfig[page];

        // Check if tour has been seen for this specific page
        if (!localStorage.getItem(config.key)) {
            startTour(config.steps, config.key);
        }
    }
});

function startTour(steps, storageKey) {
    // Filter steps to ensure elements exist
    const validSteps = steps.filter(step => {
        if (!step.element) return true; // Steps without element (like welcome) are always valid
        return document.querySelector(step.element);
    });

    if (validSteps.length === 0) return;

    introJs().setOptions({
        steps: validSteps,
        nextLabel: 'Próximo',
        prevLabel: 'Anterior',
        doneLabel: 'Entendi',
        showProgress: true,
        exitOnOverlayClick: false
    }).oncomplete(function () {
        localStorage.setItem(storageKey, 'true');
    }).onexit(function () {
        localStorage.setItem(storageKey, 'true');
    }).start();
}
