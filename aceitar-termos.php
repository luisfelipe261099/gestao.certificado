<?php
/**
 * Página de Aceitar Termos de Serviço e Contrato
 * Aparece no primeiro acesso e quando muda de plano
 */

require_once 'app/config/config.php';
require_once 'app/models/Contrato.php';

// Verificar se está logado
if (!isAuthenticated()) {
    redirect('login.php');
}

$user = getCurrentUser();
$user_role = $_SESSION['user_role'];

// Apenas parceiros precisam aceitar termos
if ($user_role !== 'parceiro') {
    redirect('login.php');
}

$plano_id = $_GET['plano_id'] ?? null;

// Para parceiros, usar o parceiro_id; para outros, usar user_id
if ($user_role === 'parceiro' && isset($_SESSION['parceiro_id'])) {
    $user_id = $_SESSION['parceiro_id'];
} else {
    $user_id = $_SESSION['user_id'];
}

$conn = getDBConnection();
$contrato_model = new Contrato($conn);

// Buscar dados completos do parceiro logado
$parceiro = [];
$stmt = $conn->prepare("
    SELECT
        p.id,
        p.nome_empresa,
        p.cnpj,
        p.email,
        p.telefone,
        p.endereco,
        p.cidade,
        p.estado,
        p.cep,
        p.pais,
        u.nome as representante_legal,
        u.cargo
    FROM parceiros p
    LEFT JOIN usuarios_parceiro u ON u.parceiro_id = p.id AND u.id = ?
    WHERE p.id = ?
    LIMIT 1
");
if ($stmt) {
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $parceiro = $row;
    }
    $stmt->close();
}

// Obter termo ativo
$termo = $contrato_model->obter_termo_ativo('contrato_parceiro');

if (!$termo) {
    $_SESSION['error'] = 'Nenhum termo de serviço disponível.';
    redirect('login.php');
}

// Gerar conteúdo do contrato com dados do parceiro
$endereco_completo = trim(($parceiro['endereco'] ?? '') . ', ' . ($parceiro['cidade'] ?? '') . ' - ' . ($parceiro['estado'] ?? '') . ', CEP: ' . ($parceiro['cep'] ?? ''));
$representante = !empty($parceiro['representante_legal']) ? $parceiro['representante_legal'] : '[Nome do Representante Legal]';
$cargo_representante = !empty($parceiro['cargo']) ? $parceiro['cargo'] : '[Cargo]';

$conteudo_contrato = <<<HTML
<div style="font-family: 'Courier New', Courier, monospace; line-height: 1.8; color: #1a1a1a;">
    <h2 style="color: #6E41C1; font-size: 1.4rem; font-weight: 700; margin-bottom: 20px; border-bottom: 3px solid #6E41C1; padding-bottom: 10px;">
        📝 MINUTA DE CONTRATO DE PRESTAÇÃO DE SERVIÇOS EDUCACIONAIS E DE PARCERIA PARA EXTENSÃO
    </h2>

    <h3 style="color: #8b3fa0; font-size: 1.2rem; font-weight: 700; margin-top: 25px; margin-bottom: 15px;">
        PARTES:
    </h3>

    <div style="margin-bottom: 20px; padding: 15px; background: #f8f5ff; border-left: 4px solid #6E41C1; border-radius: 4px;">
        <p style="margin-bottom: 10px;"><strong style="color: #6E41C1; font-size: 1.05rem;">1. CONTRATADA (INSTITUIÇÃO DE ENSINO SUPERIOR - IES):</strong></p>
        <p style="margin: 5px 0; padding-left: 15px;"><strong style="color: #d946a6;">Razão Social:</strong> Faculdade FaCiencia (Mantenedora: Guindani Instituto de Ensino Pesquisa e Gestão S/S LTDA)</p>
        <p style="margin: 5px 0; padding-left: 15px;"><strong style="color: #d946a6;">CNPJ:</strong> 09.038.742/0001-80</p>
        <p style="margin: 5px 0; padding-left: 15px;"><strong style="color: #d946a6;">Credenciamento MEC:</strong> Portaria Ministerial nº 147 de 8 de março de 2022</p>
        <p style="margin: 5px 0; padding-left: 15px;"><strong style="color: #d946a6;">Endereço:</strong> Rua Marechal Deodoro, 630 - Centro, Curitiba - PR, CEP: 80010-010</p>
        <p style="margin: 5px 0; padding-left: 15px;">Neste ato representada por: <strong style="color: #6E41C1;">Luciane Zen Nocera, Diretora Geral</strong>.</p>
    </div>

    <div style="margin-bottom: 25px; padding: 15px; background: #fff5f5; border-left: 4px solid #d946a6; border-radius: 4px;">
        <p style="margin-bottom: 10px;"><strong style="color: #d946a6; font-size: 1.05rem;">2. CONTRATANTE (POLO PARCEIRO/EMPRESA):</strong></p>
        <p style="margin: 5px 0; padding-left: 15px;"><strong style="color: #6E41C1;">Razão Social:</strong> {$parceiro['nome_empresa']}</p>
        <p style="margin: 5px 0; padding-left: 15px;"><strong style="color: #6E41C1;">CNPJ:</strong> {$parceiro['cnpj']}</p>
        <p style="margin: 5px 0; padding-left: 15px;"><strong style="color: #6E41C1;">Endereço:</strong> {$endereco_completo}</p>
        <p style="margin: 5px 0; padding-left: 15px;">Neste ato representada por: <strong style="color: #d946a6;">{$representante}, {$cargo_representante}</strong>.</p>
    </div>

    <hr style="border: none; border-top: 2px solid #e0e0e0; margin: 30px 0;">

    <h3 style="color: #6E41C1; font-size: 1.15rem; font-weight: 700; margin-top: 25px; margin-bottom: 12px;">
        CLÁUSULA PRIMEIRA – DO OBJETO
    </h3>
    <p style="margin-bottom: 10px;"><strong>1.1.</strong> O presente Contrato tem por objeto a formalização de parceria educacional em Cursos de Extensão, mediante a qual a <strong style="color: #6E41C1;">CONTRATADA</strong> (Faculdade FaCiencia) disponibiliza e a <strong style="color: #d946a6;">CONTRATANTE</strong> (Parceiro) adquire um <strong>Plano de Emissão de Certificados</strong> para a realização e certificação dos Cursos de Extensão.</p>

    <p style="margin-bottom: 10px;"><strong>1.2.</strong> A <strong style="color: #6E41C1;">CONTRATADA</strong> é a única responsável legal pela <strong>concepção pedagógica</strong>, <strong>regulação acadêmica</strong> e <strong>emissão dos certificados</strong> dos Cursos de Extensão, que terão validade em todo o território nacional.</p>

    <p style="margin-bottom: 10px;"><strong>1.3.</strong> A <strong style="color: #d946a6;">CONTRATANTE</strong> atuará como <strong>Polo Parceiro/Promotor</strong>, sendo responsável pela matrícula, gestão operacional e logística dos alunos.</p>

    <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;">

    <h3 style="color: #6E41C1; font-size: 1.15rem; font-weight: 700; margin-top: 25px; margin-bottom: 12px;">
        CLÁUSULA SEGUNDA – DO PLANO CONTRATADO E VIGÊNCIA
    </h3>
    <p style="margin-bottom: 10px;"><strong>2.1.</strong> A <strong style="color: #d946a6;">CONTRATANTE</strong> adquire, neste ato, o seguinte Plano de Emissão de Certificados:</p>
    <ul style="margin-left: 30px; margin-bottom: 10px;">
        <li><strong style="color: #6E41C1;">Nº de Certificados (Créditos):</strong> Conforme plano contratado</li>
        <li><strong style="color: #6E41C1;">Valor Total do Plano:</strong> Conforme plano contratado</li>
        <li><strong style="color: #6E41C1;">Prazo de Utilização dos Créditos:</strong> 12 (doze) meses</li>
    </ul>

    <p style="margin-bottom: 10px;"><strong>2.2.</strong> A vigência deste Contrato perdurará até o prazo de utilização dos créditos ou até a utilização integral do número de certificados contratados.</p>

    <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;">

    <h3 style="color: #6E41C1; font-size: 1.15rem; font-weight: 700; margin-top: 25px; margin-bottom: 12px;">
        CLÁUSULA TERCEIRA – DAS OBRIGAÇÕES DA CONTRATADA
    </h3>
    <p style="margin-bottom: 10px;"><strong>3.1.</strong> Garantir a legalidade e a validade dos Cursos de Extensão, em conformidade com as normas do MEC.</p>

    <p style="margin-bottom: 10px;"><strong>3.2.</strong> <strong style="color: #6E41C1;">Emitir o Certificado de Conclusão</strong> de Curso de Extensão para cada aluno matriculado pela CONTRATANTE.</p>

    <p style="margin-bottom: 10px;"><strong>3.3.</strong> Fornecer à CONTRATANTE acesso ao <strong>Sistema de Gestão Acadêmica</strong> para a gestão das matrículas e emissão dos certificados.</p>

    <p style="margin-bottom: 10px;"><strong>3.4.</strong> Prestar o suporte técnico-acadêmico necessário à CONTRATANTE.</p>

    <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;">

    <h3 style="color: #6E41C1; font-size: 1.15rem; font-weight: 700; margin-top: 25px; margin-bottom: 12px;">
        CLÁUSULA QUARTA – DAS OBRIGAÇÕES DA CONTRATANTE
    </h3>
    <p style="margin-bottom: 10px;"><strong>4.1.</strong> A <strong style="color: #d946a6;">CONTRATANTE</strong> será responsável por:</p>

    <ul style="margin-left: 30px; margin-bottom: 10px; line-height: 1.9;">
        <li><strong style="color: #6E41C1;">a) Matrícula e Cadastro:</strong> Realizar a matrícula dos alunos e inserir seus dados cadastrais no Sistema.</li>
        <li><strong style="color: #6E41C1;">b) Gestão de Créditos:</strong> Gerenciar o número de créditos/certificados contratados.</li>
        <li><strong style="color: #6E41C1;">c) Execução:</strong> Garantir que o curso de extensão seja ministrado em conformidade com o plano pedagógico.</li>
        <li><strong style="color: #6E41C1;">d) Emissão:</strong> Gerar o Certificado de Conclusão através do Sistema.</li>
        <li><strong style="color: #6E41C1;">e) Divulgação:</strong> Promover os cursos, sempre indicando a <strong>Faculdade FaCiencia</strong> como a <strong>Instituição Credenciada e Certificadora</strong>.</li>
    </ul>

    <p style="margin-bottom: 10px;"><strong>4.2.</strong> A CONTRATANTE reconhece que a autonomia operacional está sujeita à fiscalização e auditoria da CONTRATADA.</p>

    <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;">

    <h3 style="color: #6E41C1; font-size: 1.15rem; font-weight: 700; margin-top: 25px; margin-bottom: 12px;">
        CLÁUSULA QUINTA – DAS PENALIDADES
    </h3>
    <p style="margin-bottom: 10px;"><strong>5.1.</strong> Em caso de rescisão antecipada por culpa da <strong style="color: #d946a6;">CONTRATANTE</strong>, as obrigações assumidas no âmbito da Parceria continuarão em vigor.</p>

    <p style="margin-bottom: 10px;"><strong>5.2.</strong> Caso qualquer das Partes venha a infringir qualquer cláusula da presente Parceria para a qual não esteja prevista penalidade específica, será concedido à Parte infratora o prazo de 5 dias úteis contados do recebimento da notificação de inadimplemento para que a infração seja resolvida, sob pena de aplicação, à Parte infratora, de multa não compensatória no valor correspondente a 10% da quantia equivalente a obrigação que deixou de ser prestada em virtude da infração contratual.</p>

    <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;">

    <h3 style="color: #6E41C1; font-size: 1.15rem; font-weight: 700; margin-top: 25px; margin-bottom: 12px;">
        CLÁUSULA SEXTA – RELAÇÃO JURÍDICA ENTRE AS PARTES
    </h3>
    <p style="margin-bottom: 10px;"><strong>6.1.</strong> Esta Parceria não estabelece, nem deve ser interpretado como um vínculo empregatício entre as Partes, bem como nenhuma das condições desta Parceria deve ser entendida como meio para constituir uma sociedade, "joint venture", relação de representação comercial entre as Partes, sendo cada uma única, integral e exclusivamente responsável por seus atos e obrigações.</p>

    <p style="margin-bottom: 10px;"><strong>6.2.</strong> Nada aqui contido deve ser julgado como constituinte de representação entre nenhuma das Partes de qualquer natureza seja cível, fiscal ou trabalhista, tampouco qualquer tipo de agenciamento, associação, mandato, consórcio, representação ou responsabilidade solidária entre si. O relacionamento das Partes deverá ser de contratantes independentes. Nenhuma das Partes deve ter nenhum direito, título ou autoridade para firmar nenhum contrato, acordo ou compromisso em nome da outra ou comprometer a outra Parte de nenhuma maneira.</p>

    <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;">

    <h3 style="color: #6E41C1; font-size: 1.15rem; font-weight: 700; margin-top: 25px; margin-bottom: 12px;">
        CLÁUSULA SÉTIMA – CONFIDENCIALIDADE
    </h3>
    <p style="margin-bottom: 10px;"><strong>7.1.</strong> Durante o prazo de vigência do instrumento da presente Parceria, e pelo prazo de 5 anos após o seu término, as Partes se comprometem a manter o mais completo sigilo sobre quaisquer informações, dados, materiais, conteúdo da Parceria, lista de alunos, documentos, preços que venham a lhes ser confiados (<strong style="text-decoration: underline;">Informações Confidenciais</strong>), não podendo as Partes, sob qualquer pretexto, divulgar, revelar, reproduzir, utilizar ou dar conhecimento de tais Informações Confidenciais a terceiros estranhos a esta contratação, sob pena do pagamento de indenização pelas perdas e danos provocados, no valor de R$ 10.000,00 (dez mil reais).</p>

    <p style="margin-bottom: 10px;"><strong>7.2.</strong> Todas as Informações Confidenciais devem ser tratadas pelas Partes com o mesmo cuidado conferido às suas próprias informações confidenciais, de forma a evitar que sejam reveladas a terceiros.</p>

    <p style="margin-bottom: 10px;"><strong>7.3.</strong> As Informações Confidenciais divulgadas para fins desta Parceria permanecerão sempre como propriedade da Parte originária.</p>

    <p style="margin-bottom: 10px;"><strong>7.4.</strong> As Partes se comprometem a não divulgar as Informações Confidenciais, salvo se estas últimas:</p>
    <ul style="margin-left: 30px; margin-bottom: 10px;">
        <li>Estejam ou se tornem de domínio público;</li>
        <li>Estejam livremente acessíveis a qualquer pessoa ou já forem conhecidas no momento de sua divulgação pela outra Parte, sem infração a quaisquer obrigações de confidencialidade pré-existentes;</li>
        <li>Foram legalmente divulgadas por terceiros, que com o prévio conhecimento da Parte receptora, não obtiveram ou revelaram tal informação por qualquer ato ilegal ou por violação a qualquer obrigação contratual;</li>
        <li>Foram desenvolvidos ou estão sendo desenvolvidos pela Parte receptora previamente ao seu acesso às Informações Confidenciais da outra Parte, de forma independente.</li>
    </ul>

    <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;">

    <h3 style="color: #6E41C1; font-size: 1.15rem; font-weight: 700; margin-top: 25px; margin-bottom: 12px;">
        CLÁUSULA OITAVA – PROTEÇÃO DE DADOS
    </h3>
    <p style="margin-bottom: 10px;"><strong>8.1.</strong> As Partes se obrigam a tratar os dados pessoais somente para executar as suas obrigações contratuais acima descritas. Igualmente, a <strong style="color: #6E41C1;">CONTRATADA</strong> não coletará, usará, acessará, manterá, modificará, divulgará, transferirá ou, de outra forma, tratará dados pessoais, sem a ciência e autorização do <strong style="color: #d946a6;">CONTRATANTE</strong>. As Partes tratarão os Dados Pessoais para atuar na presente Parceria em conformidade com a vigente Lei Geral de Proteção de Dados Pessoais ("LGPD" ou "Lei nº 13.709/2018"), e estrita obediência às determinações de órgãos reguladores/fiscalizadores de Proteção de Dados.</p>

    <p style="margin-bottom: 10px;"><strong>8.2.</strong> As Partes se responsabilizam pelo tratamento dos dados pessoais da outra Parte e dos alunos que venham a se matricular e os passar informações pessoais para quaisquer das Partes, além de observar e cumprir as normas legais vigentes aplicáveis, sob pena de arcar com as perdas e danos que eventualmente possa causar à parte inocente, seus colaboradores, clientes e fornecedores, sem prejuízo das demais sanções aplicáveis.</p>

    <p style="margin-bottom: 10px;"><strong>8.3.</strong> A <strong style="color: #6E41C1;">CONTRATADA</strong> assegurará que os Dados Pessoais não sejam acessados, compartilhados ou transferidos para terceiros (incluindo subcontratados, agentes autorizados e afiliados) sem o consentimento prévio por escrito do <strong style="color: #d946a6;">CONTRATANTE</strong>.</p>

    <p style="margin-bottom: 10px;"><strong>8.4.</strong> As Partes deverão implementar medidas técnicas e organizativas necessárias para proteger os dados aos quais tiveram acesso contra a destruição, acidental ou ilícita, a perda, a alteração, a comunicação ou difusão ou o acesso não autorizado, além de garantir que o ambiente (seja ele físico ou lógico) utilizado por ela para o tratamento de dados pessoais é estruturado de forma a atender os requisitos de segurança, os padrões de boas práticas de governança e os princípios gerais previstos na LGPD e nas demais normas regulamentares aplicáveis.</p>

    <p style="margin-bottom: 10px;"><strong>8.5.</strong> As Partes deverão comunicar aos titulares dos dados as reclamações e solicitações que venham a receber (por exemplo, sobre a correção, exclusão, complementação e bloqueio de dados), as ordens de tribunais, autoridade pública e reguladores competentes, assim como quaisquer outras exposições ou ameaças em relação à conformidade com a proteção de dados.</p>

    <p style="margin-bottom: 10px;"><strong>8.6.</strong> Em eventual descumprimento das obrigações contratuais relativas ao processamento e tratamento dos dados pessoais, bem como nos casos de violação da segurança e sigilo dos dados pessoais, deverá a Parte responsável comunicar em 72 (setenta e duas) horas à outra Parte acerca do ocorrido, informando as medidas que estão sendo tomadas para atenuação das consequências da ameaça ou do evento danoso, sem prejuízo da responsabilidade cível, penal e demais sanções aplicáveis.</p>

    <p style="margin-bottom: 10px;"><strong>8.7.</strong> As Partes se obrigam reciprocamente a prestar auxílio à outra no cumprimento das suas obrigações judiciais ou administrativas, de acordo com a Lei de Proteção de Dados aplicável, fornecendo informações relevantes disponíveis e qualquer outra assistência para documentar e eliminar a causa e os riscos impostos por quaisquer violações de segurança.</p>

    <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;">

    <h3 style="color: #6E41C1; font-size: 1.15rem; font-weight: 700; margin-top: 25px; margin-bottom: 12px;">
        CLÁUSULA NONA – PRÁTICAS ANTICORRUPÇÃO
    </h3>
    <p style="margin-bottom: 10px;"><strong>9.1.</strong> No desempenho de suas atividades, as Partes obrigam-se, sob pena de rescisão automática deste Contrato, a observar estritamente a obrigação de não pagar, se comprometer a pagar, oferecer, aceitar ou se comprometer a aceitar qualquer pagamento, doação ou vantagem (financeira ou não financeira), seja como compensação, presente ou contribuição, a qualquer pessoa ou organização, pública ou privada, por conta própria ou através de terceiros, que forem ou puderem ser considerados ilegais ou duvidosos. As Partes obrigam-se, ainda, a seguir sempre os mais elevados princípios éticos, morais e regulamentares que sejam aplicáveis às suas atividades e a obedecer, em qualquer circunstância, a legislação brasileira, particularmente a Lei 12.846/2013 (<strong style="text-decoration: underline;">Lei Brasileira Anticorrupção</strong>) e, sempre que aplicável, tratados e convenções internacionais visando a anticorrupção.</p>

    <p style="margin-bottom: 10px;"><strong>9.2.</strong> As Partes declaram e garantem ainda que, durante o desempenho deste Contrato, nenhuma taxa, dinheiro ou qualquer outro objeto de valor, foi ou será pago, oferecido, dado ou prometido pelas Partes a qualquer: (i) pessoa (seja física ou jurídica), (ii) partido político ou qualquer candidato a cargo político, qualquer executivo ou empregado de qualquer governo ou qualquer entidade controlada por qualquer governo, ou qualquer representante agindo por ou em nome de qualquer governo, ou (iii) qualquer empregado ou executivo de qualquer organização pública (<strong style="text-decoration: underline;">Agente Público</strong>), para fins de:</p>
    <ul style="margin-left: 30px; margin-bottom: 10px;">
        <li><strong>a)</strong> influenciar indevidamente qualquer Agente Público em sua capacidade oficial, corporativa ou de negócio;</li>
        <li><strong>b)</strong> induzir um Agente Público a fazer ou omitir qualquer ato em violação deste dever legal;</li>
        <li><strong>c)</strong> indevidamente induzir qualquer Agente Público a usar sua influência com qualquer governo ou entidade governamental para afetar ou influenciar qualquer ato ou decisão de tal governo ou entidade governamental;</li>
        <li><strong>d)</strong> obter qualquer vantagem indevida; ou</li>
        <li><strong>e)</strong> obter ou reter negócios para ou com, ou direcionar negócios para, qualquer pessoa.</li>
    </ul>

    <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;">

    <h3 style="color: #6E41C1; font-size: 1.15rem; font-weight: 700; margin-top: 25px; margin-bottom: 12px;">
        CLÁUSULA DÉCIMA – DA RESCISÃO
    </h3>
    <p style="margin-bottom: 10px;"><strong>10.1.</strong> O presente Contrato poderá ser rescindido mediante comunicação prévia de 30 (trinta) dias, nos seguintes casos:</p>
    <ul style="margin-left: 30px; margin-bottom: 10px;">
        <li><strong>a)</strong> Inadimplemento de quaisquer cláusulas do presente instrumento.</li>
        <li><strong>b)</strong> Uso indevido da marca da IES ou do Sistema de Emissão de Certificados.</li>
        <li><strong>c)</strong> Descumprimento das normas acadêmicas.</li>
        <li><strong>d)</strong> Violação das cláusulas de confidencialidade, proteção de dados ou práticas anticorrupção.</li>
    </ul>

    <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;">

    <h3 style="color: #6E41C1; font-size: 1.15rem; font-weight: 700; margin-top: 25px; margin-bottom: 12px;">
        CLÁUSULA DÉCIMA PRIMEIRA – DISPOSIÇÕES GERAIS
    </h3>
    <p style="margin-bottom: 10px;"><strong>11.1.</strong> A presente Parceria é celebrada em caráter irrevogável e irretratável, constituindo obrigações legais, válidas e vinculantes entre as Partes e seus sucessores.</p>

    <p style="margin-bottom: 10px;"><strong>11.2.</strong> Cada uma das Partes terá individual, total e exclusiva responsabilidade pelos atos que praticarem em relação à atividade exercida, especialmente nas áreas civil, penal, trabalhista, tributária e previdenciária.</p>

    <p style="margin-bottom: 10px;"><strong>11.3.</strong> Cada uma das Partes será responsável exclusiva pelo pagamento dos tributos que lhe cabem, de acordo com a lei, pela responsabilidade civil e penal advinda dos atos que praticarem bem como a arcar com todas as despesas relacionadas com a atividade que desenvolver, exceto se de outra forma disposta na presente Parceria.</p>

    <p style="margin-bottom: 10px;"><strong>11.4.</strong> Na hipótese de qualquer uma das Partes vir a ser acionada, judicial ou extrajudicialmente, para responder por quaisquer obrigações que, por meio da presente Parceria ou por força de lei, sejam de responsabilidade da outra Parte, a Parte demandada deverá requerer a denunciação à lide da Parte responsável. Caso a inclusão no polo passivo não seja admitida, a Parte demandada deverá informar o recebimento do processo, solicitar as informações pertinentes à Parte responsável e enviar relatório mensal sobre o andamento processual. Cumpridas todas essas condições precedentes, a Parte responsável deverá ressarcir a outra Parte, no prazo máximo de 5 dias úteis, de todos os custos despendidos para a finalização da ação, seja através de acordo, seja adimplindo o que for determinado em sentença, incluindo, mas não se limitando a, custas periciais, processuais, recursais, sucumbenciais e honorários advocatícios.</p>

    <p style="margin-bottom: 10px;"><strong>11.5.</strong> A presente Parceria representa o único e integral entendimento existente com respeito ao objeto nele tratado e substitui contratos ou acordos, verbais ou escritos, anteriormente celebrados ou verbalmente acordados, entre as Partes.</p>

    <p style="margin-bottom: 10px;"><strong>11.6.</strong> Todas as obrigações estabelecidas pela presente Parceria são sujeitas à execução específica, nos termos do Código de Processo Civil Brasileiro. Dessa forma, qualquer das Partes poderá pedir a execução específica das cláusulas e condições desta Parceria.</p>

    <p style="margin-bottom: 10px;"><strong>11.7.</strong> Todas as notificações e comunicações previstas nesta Parceria serão feitas por escrito e consideradas recebidas na data de sua transmissão, se por e-mail, e na data do efetivo recebimento pela Parte notificada, em seu endereço, se enviadas por courier com comprovante de entrega ou telegrama, o que ocorrer primeiro. As notificações serão enviadas aos endereços indicados no preâmbulo deste Instrumento, ou para outro endereço conforme diversamente informado por uma Parte às outras Partes.</p>

    <p style="margin-bottom: 10px; padding-left: 20px;"><strong>11.7.1.</strong> O e-mail oficial da <strong style="color: #6E41C1;">CONTRATADA</strong> é contato@faciencia.edu.br e o e-mail oficial da <strong style="color: #d946a6;">CONTRATANTE</strong> é {$parceiro['email']}.</p>

    <p style="margin-bottom: 10px;"><strong>11.8.</strong> Na hipótese em que qualquer cláusula ou disposição desta Parceria vier a ser declarada nula ou não aplicável, tal nulidade não afetará quaisquer outras cláusulas ou disposições aqui contidas, as quais permanecerão em pleno vigor e efeito.</p>

    <p style="margin-bottom: 10px;"><strong>11.9.</strong> As Partes não poderão ceder ou transferir a qualquer título, sem prévia anuência da outra Parte, os direitos e obrigações decorrentes do presente contrato.</p>

    <p style="margin-bottom: 10px;"><strong>11.10.</strong> O <strong style="color: #d946a6;">CONTRATANTE</strong> desde já declara e anui, que a <strong style="color: #6E41C1;">CONTRATADA</strong> poderá contratar terceiros para auxiliá-la no âmbito da Parceria, mas que não poderá se eximir das responsabilidades assumidas nesta Parceria, nem solicitar ao <strong style="color: #d946a6;">CONTRATANTE</strong> valores adicionais além da Remuneração.</p>

    <p style="margin-bottom: 10px;"><strong>11.11.</strong> O <strong style="color: #d946a6;">CONTRATANTE</strong> deverá incluir na sua divulgação e no contrato com o seu aluno(a) o seguinte texto: <em>"O curso contratado é ofertado pela <strong style="color: #d946a6;">CONTRATANTE</strong> em parceria com a <strong style="color: #6E41C1;">Faculdade FaCiencia</strong> e apesar de ao final do curso, o aluno ter direito a um certificado de extensão universitária, que possui validade nacional como prova da formação recebida por seu titular, evidencia apenas a formação nesta área, porém não garante o exercício de profissão, ficando a critério do respectivo órgão ou conselho de classe, aceitar ou não o respectivo certificado."</em></p>

    <p style="margin-bottom: 10px;"><strong>11.12.</strong> O <strong style="color: #d946a6;">CONTRATANTE</strong> não deve usar o termo que o seu curso é "reconhecido pelo MEC" ou qualquer selo que diga isso. O termo que o <strong style="color: #d946a6;">CONTRATANTE</strong> pode usar é que o curso é validado por uma faculdade credenciada pelo MEC e/ou um selo repassado pela <strong style="color: #6E41C1;">CONTRATADA</strong>.</p>

    <p style="margin-bottom: 10px;"><strong>11.13.</strong> O <strong style="color: #d946a6;">CONTRATANTE</strong> autoriza a colocação da sua logo e nome no site da <strong style="color: #6E41C1;">CONTRATADA</strong> que informa a parceria existente nos termos deste contrato.</p>

    <p style="margin-bottom: 10px;"><strong>11.14.</strong> Esta Parceria será regida e interpretada de acordo com as leis da República Federativa do Brasil.</p>

    <p style="margin-bottom: 10px;"><strong>11.15.</strong> As Partes elegem o foro da Comarca de Curitiba, Paraná, como competente para dirimir todas e quaisquer dúvidas oriundas e proceder à execução da presente Parceria, com a exclusão de qualquer outro, por mais privilegiado que seja.</p>

    <hr style="border: none; border-top: 2px solid #6E41C1; margin: 30px 0;">

    <div style="background: #f0f9ff; border: 2px solid #6E41C1; border-radius: 8px; padding: 20px; margin-top: 25px; text-align: center;">
        <p style="margin: 0; font-size: 1.05rem; color: #1a1a1a;"><strong style="color: #6E41C1;">⚠️ IMPORTANTE:</strong> Ao aceitar este contrato, você concorda com todos os termos e condições acima descritos.</p>
    </div>
</div>
HTML;

$erro = '';
$sucesso = '';

// Processar aceitação dos termos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aceita_termos = $_POST['aceita_termos'] ?? false;
    $assinatura = trim($_POST['assinatura'] ?? '');

    if (!$aceita_termos) {
        $erro = 'Você deve aceitar os termos para continuar.';
    } elseif (empty($assinatura)) {
        $erro = 'Você deve digitar seu nome completo para assinar o contrato.';
    } elseif (strlen($assinatura) < 3) {
        $erro = 'Por favor, digite seu nome completo.';
    } else {
        // Registrar assinatura
        $dados_assinatura = [
            'usuario_id' => $user_id,
            'tipo_usuario' => $user_role,
            'termo_id' => $termo['id'],
            'plano_id' => $plano_id,
            'assinatura_digital' => $assinatura
        ];

        if ($contrato_model->registrar_assinatura($dados_assinatura)) {
            // Marcar termos como aceitos
            $contrato_model->marcar_termos_aceitos($user_id, $user_role);

            $_SESSION['success'] = 'Termos aceitos com sucesso!';

            // Verificar se já tem pagamento confirmado
            if ($user_role === 'parceiro') {
                $stmt_pag = $conn->prepare("SELECT COUNT(*) as total FROM faturas WHERE parceiro_id = ? AND status = 'pago'");
                $stmt_pag->bind_param("i", $user_id);
                $stmt_pag->execute();
                $pag_result = $stmt_pag->get_result()->fetch_assoc();
                $stmt_pag->close();

                if ($pag_result['total'] == 0) {
                    // Não tem pagamento - redirecionar para página de primeiro pagamento
                    redirect(APP_URL . '/parceiro/primeiro-pagamento.php');
                } else {
                    // Já pagou - ir para dashboard
                    redirect(DIR_PARCEIRO . '/dashboard-parceiro.php');
                }
            } else {
                // Admin vai direto para dashboard
                redirect(DIR_ADMIN . '/dashboard-admin.php');
            }
        } else {
            $erro = 'Erro ao registrar assinatura. Tente novamente.';
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aceitar Termos de Serviço - FaCiencia</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Dancing+Script:wght@600&display=swap"
        rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">

    <style>
        :root {
            --primary-color: #0052CC;
            /* Azul Corporativo Profundo */
            --primary-hover: #0043A6;
            --success-color: #36B37E;
            --text-dark: #172B4D;
            --text-medium: #5E6C84;
            --bg-color: #F4F5F7;
            --card-bg: #FFFFFF;
            --border-color: #DFE1E6;
            --radius: 3px;
            /* Bordas mais retas = mais corporativo */
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 20px;
        }

        /* --- Stepper --- */
        .stepper-container {
            max-width: 900px;
            width: 100%;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            position: relative;
        }

        .stepper-container::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 2px;
            background: #EBECF0;
            z-index: 0;
            transform: translateY(-50%);
        }

        .step {
            position: relative;
            z-index: 1;
            background: var(--bg-color);
            padding: 0 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-medium);
            font-weight: 500;
            font-size: 14px;
        }

        .step.active {
            color: var(--primary-color);
            font-weight: 600;
        }

        .step-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #EBECF0;
            color: var(--text-medium);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            transition: all 0.3s;
        }

        .step.active .step-circle {
            background: var(--primary-color);
            color: white;
            box-shadow: 0 0 0 4px rgba(0, 82, 204, 0.1);
        }

        /* --- Main Container --- */
        .main-container {
            max-width: 900px;
            width: 100%;
            background: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.24);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .header-section {
            padding: 30px 40px;
            border-bottom: 1px solid var(--border-color);
            background: #fff;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-title h1 {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-title p {
            color: var(--text-medium);
            font-size: 14px;
            margin-top: 4px;
        }

        .btn-print {
            background: white;
            border: 1px solid var(--border-color);
            color: var(--text-medium);
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .btn-print:hover {
            background: #F4F5F7;
            color: var(--text-dark);
        }

        .content-section {
            padding: 40px;
            background: #FFFFFF;
        }

        .contract-wrapper {
            background: #F4F5F7;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .contract-box {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 40px;
            height: 450px;
            overflow-y: auto;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            font-size: 14px;
            line-height: 1.6;
        }

        /* Scrollbar customizada */
        .contract-box::-webkit-scrollbar {
            width: 8px;
        }

        .contract-box::-webkit-scrollbar-track {
            background: #F4F5F7;
        }

        .contract-box::-webkit-scrollbar-thumb {
            background: #C1C7D0;
            border-radius: 4px;
        }

        .contract-box::-webkit-scrollbar-thumb:hover {
            background: #A5ADBA;
        }

        .signature-card {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 30px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .signature-input-group {
            display: flex;
            flex-direction: column;
        }

        .form-label {
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            color: var(--text-medium);
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--border-color);
            border-radius: 4px;
            font-size: 16px;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s;
            background: #FAFBFC;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            background: white;
        }

        .signature-preview-box {
            border: 2px dashed var(--border-color);
            border-radius: 4px;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #FAFBFC;
            position: relative;
        }

        .signature-preview-text {
            font-family: 'Dancing Script', cursive;
            font-size: 32px;
            color: #000;
            transform: rotate(-2deg);
        }

        .signature-placeholder {
            color: #C1C7D0;
            font-size: 14px;
            font-style: italic;
        }

        .legal-check {
            grid-column: 1 / -1;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }

        .checkbox-wrapper {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            cursor: pointer;
        }

        .checkbox-wrapper input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin-top: 3px;
            accent-color: var(--primary-color);
        }

        .checkbox-wrapper label {
            font-size: 14px;
            color: var(--text-dark);
            cursor: pointer;
            line-height: 1.5;
        }

        .btn-submit {
            grid-column: 1 / -1;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 14px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 10px;
        }

        .btn-submit:hover {
            background: var(--primary-hover);
        }

        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-danger {
            background: #FFEBE6;
            color: #BF2600;
            border: 1px solid #FFBDAD;
        }

        .alert-success {
            background: #E3FCEF;
            color: #006644;
            border: 1px solid #ABF5D1;
        }

        @media (max-width: 768px) {
            .signature-card {
                grid-template-columns: 1fr;
            }
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .stepper-container,
            .btn-print,
            .signature-card,
            .header-section p {
                display: none;
            }

            .main-container {
                box-shadow: none;
                max-width: 100%;
            }

            .contract-box {
                height: auto;
                border: none;
                padding: 0;
                overflow: visible;
            }

            .header-section {
                border-bottom: 2px solid #000;
                padding: 20px 0;
            }
        }
    </style>
</head>

<body>

    <!-- Stepper -->
    <div class="stepper-container">
        <div class="step active">
            <div class="step-circle">1</div>
            <span>Aceite do Contrato</span>
        </div>
        <div class="step">
            <div class="step-circle">2</div>
            <span>Pagamento</span>
        </div>
        <div class="step">
            <div class="step-circle">3</div>
            <span>Conclusão</span>
        </div>
    </div>

    <div class="main-container">
        <div class="header-section">
            <div class="header-title">
                <h1>
                    <span class="material-icons-outlined" style="color: var(--primary-color);">description</span>
                    Contrato de Prestação de Serviços
                </h1>
                <p>Revise os termos e assine digitalmente abaixo.</p>
            </div>
            <button class="btn-print" onclick="window.print()">
                <span class="material-icons-outlined" style="font-size: 18px;">print</span>
                Imprimir / Salvar PDF
            </button>
        </div>

        <div class="content-section">
            <?php if ($erro): ?>
                <div class="alert alert-danger">
                    <span class="material-icons-outlined">error</span>
                    <?php echo htmlspecialchars($erro); ?>
                </div>
            <?php endif; ?>

            <?php if ($sucesso): ?>
                <div class="alert alert-success">
                    <span class="material-icons-outlined">check_circle</span>
                    <?php echo htmlspecialchars($sucesso); ?>
                </div>
            <?php endif; ?>

            <div class="contract-wrapper">
                <div class="contract-box">
                    <?php echo $conteudo_contrato; ?>
                </div>
            </div>

            <form method="POST" action="">
                <div class="signature-card">
                    <div class="signature-input-group">
                        <label for="assinatura" class="form-label">Assinatura Digital (Digite seu Nome)</label>
                        <input type="text" id="assinatura" name="assinatura" class="form-control"
                            placeholder="Ex: João da Silva" required autocomplete="off">
                        <p style="font-size: 12px; color: var(--text-medium); margin-top: 8px;">
                            <span class="material-icons-outlined"
                                style="font-size: 12px; vertical-align: middle;">lock</span>
                            Assinatura criptografada e com validade jurídica.
                        </p>
                    </div>

                    <div class="signature-input-group">
                        <label class="form-label">Pré-visualização da Assinatura</label>
                        <div class="signature-preview-box" id="signaturePreview">
                            <span class="signature-placeholder">Sua assinatura aparecerá aqui</span>
                        </div>
                    </div>

                    <div class="legal-check">
                        <div class="checkbox-wrapper" onclick="document.getElementById('aceita_termos').click()">
                            <input type="checkbox" id="aceita_termos" name="aceita_termos" value="1" required
                                onclick="event.stopPropagation()">
                            <label for="aceita_termos">
                                Declaro que li e concordo com os <strong>Termos de Uso</strong> e <strong>Política de
                                    Privacidade</strong>, e aceito este contrato eletronicamente.
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">
                        Confirmar e Continuar
                        <span class="material-icons-outlined">arrow_forward</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Script para simular assinatura
        const input = document.getElementById('assinatura');
        const preview = document.getElementById('signaturePreview');

        input.addEventListener('input', function () {
            if (this.value.trim().length > 0) {
                preview.innerHTML = `<span class="signature-preview-text">${this.value}</span>`;
                preview.style.borderColor = 'var(--primary-color)';
                preview.style.backgroundColor = '#FFF';
            } else {
                preview.innerHTML = `<span class="signature-placeholder">Sua assinatura aparecerá aqui</span>`;
                preview.style.borderColor = 'var(--border-color)';
                preview.style.backgroundColor = '#FAFBFC';
            }
        });
    </script>

</body>

</html>
```