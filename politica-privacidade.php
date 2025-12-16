<?php
/**
 * Política de Privacidade - Sistema de Certificados
 * Exibe a política de privacidade da plataforma
 */

require_once 'app/config/config.php';

$page_title = 'Política de Privacidade - ' . APP_NAME;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: #1a1a1a;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 50px;
        }
        
        h1 {
            color: #6E41C1;
            font-size: 2.5rem;
            margin-bottom: 10px;
            border-bottom: 3px solid #6E41C1;
            padding-bottom: 15px;
        }
        
        .last-update {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 30px;
        }
        
        h2 {
            color: #6E41C1;
            font-size: 1.5rem;
            margin-top: 35px;
            margin-bottom: 15px;
        }
        
        h3 {
            color: #8b3fa0;
            font-size: 1.2rem;
            margin-top: 25px;
            margin-bottom: 12px;
        }
        
        p {
            margin-bottom: 15px;
            text-align: justify;
        }
        
        ul, ol {
            margin-left: 30px;
            margin-bottom: 15px;
        }
        
        li {
            margin-bottom: 8px;
        }
        
        strong {
            color: #6E41C1;
        }
        
        .highlight-box {
            background: #f0f9ff;
            border: 2px solid #6E41C1;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        
        .back-button {
            display: inline-block;
            margin-top: 30px;
            padding: 12px 30px;
            background: #6E41C1;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: background 0.3s;
        }
        
        .back-button:hover {
            background: #8b3fa0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📋 Política de Privacidade</h1>
        <p class="last-update">Última atualização: <?php echo date('d/m/Y'); ?></p>
        
        <div class="highlight-box">
            <p><strong>⚠️ IMPORTANTE:</strong> Esta Política de Privacidade descreve como a Faculdade FaCiencia coleta, usa, armazena e protege suas informações pessoais em conformidade com a Lei Geral de Proteção de Dados (LGPD - Lei nº 13.709/2018).</p>
        </div>

        <h2>1. Informações que Coletamos</h2>
        <p>Coletamos as seguintes informações pessoais quando você utiliza nossa plataforma:</p>
        <ul>
            <li><strong>Dados de Identificação:</strong> Nome completo, CPF/CNPJ, data de nascimento, RG</li>
            <li><strong>Dados de Contato:</strong> E-mail, telefone, endereço completo</li>
            <li><strong>Dados Profissionais:</strong> Nome da empresa, cargo, área de atuação</li>
            <li><strong>Dados Acadêmicos:</strong> Cursos realizados, certificados emitidos, histórico de aprendizagem</li>
            <li><strong>Dados de Navegação:</strong> Endereço IP, tipo de navegador, páginas visitadas, tempo de acesso</li>
            <li><strong>Dados de Pagamento:</strong> Informações de cobrança e histórico de transações (processadas por terceiros seguros)</li>
        </ul>

        <h2>2. Como Utilizamos Suas Informações</h2>
        <p>Utilizamos suas informações pessoais para as seguintes finalidades:</p>
        <ul>
            <li>Fornecer acesso à plataforma e aos serviços educacionais</li>
            <li>Emitir certificados de extensão universitária</li>
            <li>Processar pagamentos e gerenciar assinaturas</li>
            <li>Comunicar sobre cursos, atualizações e novidades</li>
            <li>Melhorar nossos serviços e experiência do usuário</li>
            <li>Cumprir obrigações legais e regulatórias</li>
            <li>Prevenir fraudes e garantir a segurança da plataforma</li>
        </ul>

        <h2>3. Base Legal para o Tratamento de Dados</h2>
        <p>O tratamento de seus dados pessoais é realizado com base nas seguintes hipóteses legais previstas na LGPD:</p>
        <ul>
            <li><strong>Execução de Contrato:</strong> Para fornecer os serviços educacionais contratados</li>
            <li><strong>Consentimento:</strong> Quando você autoriza expressamente o uso de seus dados</li>
            <li><strong>Obrigação Legal:</strong> Para cumprimento de obrigações legais e regulatórias</li>
            <li><strong>Legítimo Interesse:</strong> Para melhorar nossos serviços e prevenir fraudes</li>
        </ul>

        <h2>4. Compartilhamento de Informações</h2>
        <p>Podemos compartilhar suas informações pessoais nas seguintes situações:</p>
        <ul>
            <li><strong>Com Parceiros Educacionais:</strong> Quando você se matricula em cursos oferecidos por nossos parceiros</li>
            <li><strong>Com Prestadores de Serviços:</strong> Empresas que nos auxiliam em processamento de pagamentos, hospedagem de dados e suporte técnico</li>
            <li><strong>Por Obrigação Legal:</strong> Quando exigido por lei, ordem judicial ou autoridade competente</li>
            <li><strong>Com Seu Consentimento:</strong> Em outras situações mediante sua autorização expressa</li>
        </ul>
        <p><strong>Importante:</strong> Não vendemos, alugamos ou comercializamos suas informações pessoais para terceiros.</p>

        <h2>5. Segurança dos Dados</h2>
        <p>Implementamos medidas técnicas e organizacionais para proteger suas informações pessoais:</p>
        <ul>
            <li>Criptografia de dados em trânsito e em repouso</li>
            <li>Controles de acesso rigorosos e autenticação multifator</li>
            <li>Monitoramento contínuo de segurança e detecção de ameaças</li>
            <li>Backups regulares e planos de recuperação de desastres</li>
            <li>Treinamento regular de nossa equipe sobre proteção de dados</li>
            <li>Auditorias periódicas de segurança</li>
        </ul>

        <h2>6. Retenção de Dados</h2>
        <p>Mantemos suas informações pessoais pelo tempo necessário para:</p>
        <ul>
            <li>Cumprir as finalidades para as quais foram coletadas</li>
            <li>Atender obrigações legais, contratuais e regulatórias</li>
            <li>Resolver disputas e fazer cumprir nossos acordos</li>
        </ul>
        <p>Após o término desses períodos, seus dados serão eliminados ou anonimizados de forma segura.</p>

        <h2>7. Seus Direitos como Titular de Dados</h2>
        <p>De acordo com a LGPD, você tem os seguintes direitos:</p>
        <ul>
            <li><strong>Confirmação e Acesso:</strong> Confirmar se tratamos seus dados e solicitar acesso a eles</li>
            <li><strong>Correção:</strong> Solicitar a correção de dados incompletos, inexatos ou desatualizados</li>
            <li><strong>Anonimização, Bloqueio ou Eliminação:</strong> Solicitar a anonimização, bloqueio ou eliminação de dados desnecessários ou excessivos</li>
            <li><strong>Portabilidade:</strong> Solicitar a portabilidade de seus dados a outro fornecedor</li>
            <li><strong>Eliminação:</strong> Solicitar a eliminação de dados tratados com base no consentimento</li>
            <li><strong>Informação:</strong> Obter informações sobre entidades públicas e privadas com as quais compartilhamos seus dados</li>
            <li><strong>Revogação do Consentimento:</strong> Revogar o consentimento a qualquer momento</li>
            <li><strong>Oposição:</strong> Opor-se ao tratamento de dados em determinadas situações</li>
        </ul>

        <h2>8. Como Exercer Seus Direitos</h2>
        <p>Para exercer qualquer um dos direitos acima, entre em contato conosco através de:</p>
        <ul>
            <li><strong>E-mail:</strong> contato@faciencia.edu.br</li>
            <li><strong>Telefone:</strong> (41) 3333-3333</li>
            <li><strong>Endereço:</strong> Rua Marechal Deodoro, 630 - Centro, Curitiba - PR, CEP: 80010-010</li>
        </ul>
        <p>Responderemos sua solicitação em até 15 dias úteis.</p>

        <h2>9. Cookies e Tecnologias Similares</h2>
        <p>Utilizamos cookies e tecnologias similares para:</p>
        <ul>
            <li>Manter você conectado à plataforma</li>
            <li>Lembrar suas preferências e configurações</li>
            <li>Analisar o uso da plataforma e melhorar a experiência</li>
            <li>Personalizar conteúdo e recomendações</li>
        </ul>
        <p>Você pode gerenciar suas preferências de cookies através das configurações do seu navegador.</p>

        <h2>10. Transferência Internacional de Dados</h2>
        <p>Seus dados podem ser armazenados e processados em servidores localizados no Brasil ou em outros países. Quando houver transferência internacional, garantimos que sejam adotadas medidas adequadas de proteção conforme exigido pela LGPD.</p>

        <h2>11. Menores de Idade</h2>
        <p>Nossos serviços são destinados a pessoas maiores de 18 anos. Não coletamos intencionalmente informações de menores de idade sem o consentimento dos pais ou responsáveis legais.</p>

        <h2>12. Alterações nesta Política</h2>
        <p>Podemos atualizar esta Política de Privacidade periodicamente. Notificaremos você sobre alterações significativas através de:</p>
        <ul>
            <li>Aviso em nossa plataforma</li>
            <li>E-mail para o endereço cadastrado</li>
            <li>Atualização da data de "Última atualização" no topo desta página</li>
        </ul>

        <h2>13. Encarregado de Proteção de Dados (DPO)</h2>
        <p>Nosso Encarregado de Proteção de Dados está disponível para esclarecer dúvidas sobre esta política e sobre o tratamento de seus dados pessoais:</p>
        <ul>
            <li><strong>Nome:</strong> Luciane Zen Nocera</li>
            <li><strong>E-mail:</strong> dpo@faciencia.edu.br</li>
        </ul>

        <h2>14. Legislação Aplicável</h2>
        <p>Esta Política de Privacidade é regida pela legislação brasileira, especialmente pela Lei Geral de Proteção de Dados (Lei nº 13.709/2018) e pelo Marco Civil da Internet (Lei nº 12.965/2014).</p>

        <div class="highlight-box">
            <p><strong>📞 Dúvidas?</strong> Se você tiver qualquer dúvida sobre esta Política de Privacidade ou sobre como tratamos seus dados pessoais, entre em contato conosco através dos canais indicados acima.</p>
        </div>

        <a href="<?php echo APP_URL; ?>" class="back-button">← Voltar para o Início</a>
    </div>
</body>
</html>
