<?php
/**
 * Ajuda e FAQ do Aluno
 * Sistema EAD Pro
 */

require_once '../config/database.php';

iniciar_sessao();

$page_title = 'Ajuda';
include '../includes/header-aluno.php';
?>

<!-- Cabeçalho da Página -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Ajuda e FAQ</h1>
</div>

<!-- Busca -->
<div class="card shadow mb-4">
    <div class="card-body">
        <div class="input-group">
            <input type="text" class="form-control" placeholder="Buscar na ajuda..." id="searchHelp">
            <div class="input-group-append">
                <button class="btn btn-primary" type="button">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Categorias de Ajuda -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card shadow h-100">
            <div class="card-body text-center">
                <i class="fas fa-book fa-2x text-primary mb-3"></i>
                <h6 class="font-weight-bold">Cursos</h6>
                <p class="text-gray-600 small">Como acessar e gerenciar seus cursos</p>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow h-100">
            <div class="card-body text-center">
                <i class="fas fa-video fa-2x text-success mb-3"></i>
                <h6 class="font-weight-bold">Vídeos</h6>
                <p class="text-gray-600 small">Dúvidas sobre reprodução de vídeos</p>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow h-100">
            <div class="card-body text-center">
                <i class="fas fa-certificate fa-2x text-warning mb-3"></i>
                <h6 class="font-weight-bold">Certificados</h6>
                <p class="text-gray-600 small">Informações sobre certificados</p>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow h-100">
            <div class="card-body text-center">
                <i class="fas fa-user fa-2x text-info mb-3"></i>
                <h6 class="font-weight-bold">Conta</h6>
                <p class="text-gray-600 small">Gerenciar sua conta e perfil</p>
            </div>
        </div>
    </div>
</div>

<!-- FAQ -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-question-circle"></i> Perguntas Frequentes
        </h6>
    </div>
    <div class="card-body">
        <div class="accordion" id="faqAccordion">
            <!-- FAQ 1 -->
            <div class="card border-0 mb-2">
                <div class="card-header bg-light p-0" id="faqHeading1">
                    <h2 class="mb-0">
                        <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#faqCollapse1">
                            Como faço para me inscrever em um curso?
                        </button>
                    </h2>
                </div>
                <div id="faqCollapse1" class="collapse" data-parent="#faqAccordion">
                    <div class="card-body">
                        Para se inscrever em um curso, acesse a seção "Meus Cursos" e clique em "Novo Curso". 
                        Selecione o curso desejado e clique em "Inscrever-se". Você terá acesso imediato ao conteúdo.
                    </div>
                </div>
            </div>

            <!-- FAQ 2 -->
            <div class="card border-0 mb-2">
                <div class="card-header bg-light p-0" id="faqHeading2">
                    <h2 class="mb-0">
                        <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#faqCollapse2">
                            Como baixo os materiais do curso?
                        </button>
                    </h2>
                </div>
                <div id="faqCollapse2" class="collapse" data-parent="#faqAccordion">
                    <div class="card-body">
                        Ao acessar uma aula, você verá a aba "Materiais". Clique no material desejado e ele será baixado automaticamente.
                    </div>
                </div>
            </div>

            <!-- FAQ 3 -->
            <div class="card border-0 mb-2">
                <div class="card-header bg-light p-0" id="faqHeading3">
                    <h2 class="mb-0">
                        <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#faqCollapse3">
                            Como obtenho meu certificado?
                        </button>
                    </h2>
                </div>
                <div id="faqCollapse3" class="collapse" data-parent="#faqAccordion">
                    <div class="card-body">
                        Após completar todas as aulas e exercícios de um curso, você receberá automaticamente seu certificado. 
                        Você pode acessá-lo na seção "Certificados".
                    </div>
                </div>
            </div>

            <!-- FAQ 4 -->
            <div class="card border-0 mb-2">
                <div class="card-header bg-light p-0" id="faqHeading4">
                    <h2 class="mb-0">
                        <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#faqCollapse4">
                            Posso assistir aos vídeos offline?
                        </button>
                    </h2>
                </div>
                <div id="faqCollapse4" class="collapse" data-parent="#faqAccordion">
                    <div class="card-body">
                        No momento, os vídeos devem ser assistidos online. Porém, você pode baixar os materiais em PDF para estudar offline.
                    </div>
                </div>
            </div>

            <!-- FAQ 5 -->
            <div class="card border-0 mb-2">
                <div class="card-header bg-light p-0" id="faqHeading5">
                    <h2 class="mb-0">
                        <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#faqCollapse5">
                            Como faço para cancelar minha inscrição?
                        </button>
                    </h2>
                </div>
                <div id="faqCollapse5" class="collapse" data-parent="#faqAccordion">
                    <div class="card-body">
                        Você pode cancelar sua inscrição a qualquer momento acessando "Meus Cursos" e clicando em "Cancelar Inscrição". 
                        Seu progresso será mantido se você se inscrever novamente.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Contato -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-envelope"></i> Não encontrou o que procura?
        </h6>
    </div>
    <div class="card-body">
        <p class="text-gray-600">Entre em contato conosco para mais informações:</p>
        <div class="row">
            <div class="col-md-6">
                <p>
                    <i class="fas fa-envelope text-primary"></i> 
                    <strong>Email:</strong> suporte@eadpro.com
                </p>
            </div>
            <div class="col-md-6">
                <p>
                    <i class="fas fa-phone text-primary"></i> 
                    <strong>Telefone:</strong> (11) 3000-0000
                </p>
            </div>
        </div>
        <a href="mailto:suporte@eadpro.com" class="btn btn-primary">
            <i class="fas fa-envelope"></i> Enviar Email
        </a>
    </div>
</div>

<?php include '../includes/footer-aluno.php'; ?>

