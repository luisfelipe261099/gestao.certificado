<?php
/**
 * Classe de Integração com API Asaas
 * Gerencia boletos, pagamentos e webhooks
 */

class AsaasAPI
{
    private $api_key;
    private $wallet_id;
    private $base_url;
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->loadConfig();
    }

    /**
     * Carrega configurações do banco de dados
     */
    private function loadConfig()
    {
        $result = $this->conn->query("SELECT * FROM asaas_config WHERE ativo = 1 ORDER BY id DESC LIMIT 1");
        if ($result && $result->num_rows > 0) {
            $config = $result->fetch_assoc();
            // Normaliza/limpa valores vindos do BD
            $this->api_key = isset($config['api_key']) ? trim((string) $config['api_key']) : '';
            $this->wallet_id = isset($config['wallet_id']) ? trim((string) $config['wallet_id']) : '';
            $ambiente_raw = isset($config['ambiente']) ? trim(mb_strtolower((string) $config['ambiente'])) : '';
            // Aceita variantes: 'sandbox', 'producao', 'produção', 'prod'
            if ($ambiente_raw === 'sandbox' || $ambiente_raw === 'homolog' || $ambiente_raw === 'teste') {
                $ambiente_norm = 'sandbox';
            } else {
                $ambiente_norm = 'producao';
            }
            // URLs conforme documentação Asaas
            $this->base_url = ($ambiente_norm === 'sandbox')
                ? 'https://sandbox.asaas.com/api/v3'
                : 'https://api.asaas.com/v3';
        }
    }

    /**
     * Verifica se API está configurada
     */
    public function isConfigured()
    {
        return !empty($this->api_key);
    }

    /**
     * Cria um boleto no Asaas
     */
    public function criarBoleto($dados)
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Asaas não configurado'];
        }

        // Buscar ou criar cliente e usar o ID no pagamento
        $customer_result = $this->criarOuObterCliente($dados);
        if (!$customer_result['success']) {
            return $customer_result;
        }
        $customer_id = $customer_result['data']['id'];

        $payload = [
            'customer' => $customer_id,
            'billingType' => 'BOLETO',
            'value' => (float) $dados['valor'],
            'dueDate' => $dados['data_vencimento'],
            'description' => $dados['descricao'] ?? 'Fatura',
            'externalReference' => $dados['referencia_externa'] ?? '',
            'notificationDisabled' => false
        ];

        // Parcelamento (se aplicável)
        if (isset($dados['parcelas']) && $dados['parcelas'] > 1) {
            $payload['installmentCount'] = (int) $dados['parcelas'];
            $payload['installmentValue'] = round($dados['valor'] / $dados['parcelas'], 2);
        }

        // Callback URL para redirecionar após pagamento
        if (!empty($dados['success_url'])) {
            $payload['callback'] = [
                'successUrl' => $dados['success_url'],
                'autoRedirect' => true
            ];
        }

        if (!empty($this->wallet_id)) {
            $payload['walletId'] = $this->wallet_id;
        }

        return $this->fazerRequisicao('POST', '/payments', $payload);
    }

    /**
     * Obtém informações de um boleto
     */
    public function obterBoleto($asaas_id)
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Asaas não configurado'];
        }

        return $this->fazerRequisicao('GET', "/payments/$asaas_id");
    }

    /**
     * Cancela um boleto
     */
    public function cancelarBoleto($asaas_id)
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Asaas não configurado'];
        }

        return $this->fazerRequisicao('DELETE', "/payments/$asaas_id");
    }

    /**
     * Cria uma cobrança PIX no Asaas
     */
    public function criarCobrancaPix($dados)
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Asaas não configurado'];
        }

        // Buscar ou criar cliente e usar o ID no pagamento PIX
        $customer_result = $this->criarOuObterCliente($dados);
        if (!$customer_result['success']) {
            return $customer_result;
        }
        $customer_id = $customer_result['data']['id'];

        $payload = [
            'customer' => $customer_id,
            'billingType' => 'PIX',
            'value' => (float) $dados['valor'],
            'dueDate' => $dados['data_vencimento'],
            'description' => $dados['descricao'] ?? 'Pagamento via PIX',
            'externalReference' => $dados['referencia_externa'] ?? '',
            'notificationDisabled' => false
        ];

        if (!empty($this->wallet_id)) {
            $payload['walletId'] = $this->wallet_id;
        }

        return $this->fazerRequisicao('POST', '/payments', $payload);
    }

    /**
     * Cria uma cobrança hospedada no Asaas e retorna invoiceUrl
     * Permite delegar o checkout (Pix/Cartão/Boleto) para a página segura do Asaas
     */
    public function criarCobrancaHosted($dados, $tipo = 'UNDEFINED')
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Asaas não configurado'];
        }

        // Buscar ou criar cliente
        $customer_result = $this->criarOuObterCliente($dados);
        if (!$customer_result['success']) {
            return $customer_result;
        }
        $customer_id = $customer_result['data']['id'];

        $billingType = strtoupper($tipo);
        $permitidos = ['UNDEFINED', 'PIX', 'BOLETO', 'CREDIT_CARD'];
        if (!in_array($billingType, $permitidos)) {
            $billingType = 'UNDEFINED';
        }

        $payload = [
            'customer' => $customer_id,
            'billingType' => $billingType,
            'value' => (float) $dados['valor'],
            'dueDate' => $dados['data_vencimento'],
            'description' => $dados['descricao'] ?? 'Fatura',
            'externalReference' => $dados['referencia_externa'] ?? '',
            'notificationDisabled' => false
        ];

        if (!empty($this->wallet_id)) {
            $payload['walletId'] = $this->wallet_id;
        }

        // Callback opcional de sucesso (se fornecido)
        if (!empty($dados['success_url'])) {
            $payload['callback'] = ['successUrl' => $dados['success_url']];
            // SEMPRE ativar auto redirect para retornar ao sistema
            $payload['callback']['autoRedirect'] = true;
        }


        return $this->fazerRequisicao('POST', '/payments', $payload);
    }



    /**
     * Cria uma cobrança com Cartão de Crédito no Asaas
     */
    public function criarCobrancaCartao($dados)
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Asaas não configurado'];
        }

        // Buscar ou criar cliente e usar o ID no pagamento com cartão
        $customer_result = $this->criarOuObterCliente($dados);
        if (!$customer_result['success']) {
            return $customer_result;
        }
        $customer_id = $customer_result['data']['id'];

        $payload = [
            'customer' => $customer_id,
            'billingType' => 'CREDIT_CARD',
            'value' => (float) $dados['valor'],
            'dueDate' => $dados['data_vencimento'],
            'description' => $dados['descricao'] ?? 'Pagamento via Cartão',
            'externalReference' => $dados['referencia_externa'] ?? '',
            'creditCard' => [
                'holderName' => $dados['cartao_nome'],
                'number' => $dados['cartao_numero'],
                'expiryMonth' => $dados['cartao_mes'],
                'expiryYear' => $dados['cartao_ano'],
                'ccv' => $dados['cartao_cvv']
            ],
            'creditCardHolderInfo' => [
                'name' => $dados['nome_cliente'],
                'email' => $dados['email_cliente'],
                'cpfCnpj' => $this->limparCPFCNPJ($dados['cpf_cnpj']),
                'postalCode' => $this->limparCEP($dados['cep'] ?? ''),
                'addressNumber' => $dados['numero'] ?? '',
                'phone' => $dados['telefone'] ?? ''
            ]
        ];

        // Parcelamento (se aplicável)
        if (isset($dados['parcelas']) && $dados['parcelas'] > 1) {
            $payload['installmentCount'] = (int) $dados['parcelas'];
            $payload['installmentValue'] = round($dados['valor'] / $dados['parcelas'], 2);
        }

        if (!empty($this->wallet_id)) {
            $payload['walletId'] = $this->wallet_id;
        }

        return $this->fazerRequisicao('POST', '/payments', $payload);
    }

    /**
     * Cria ou obtém um cliente no Asaas
     */
    public function criarOuObterCliente($dados)
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Asaas não configurado'];
        }

        $cpf_cnpj = $this->limparCPFCNPJ($dados['cpf_cnpj']);

        // Tentar buscar cliente existente
        $resultado = $this->fazerRequisicao('GET', "/customers?cpfCnpj=$cpf_cnpj");

        if ($resultado['success'] && isset($resultado['data']['data']) && count($resultado['data']['data']) > 0) {
            // Cliente já existe
            return [
                'success' => true,
                'data' => $resultado['data']['data'][0]
            ];
        }

        // Cliente não existe, criar novo
        $customer_data = $this->prepararDadosCliente($dados);
        return $this->fazerRequisicao('POST', '/customers', $customer_data);
    }

    /**
     * Cria uma assinatura recorrente no Asaas
     */
    public function criarAssinatura($dados)
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Asaas não configurado'];
        }

        // Primeiro, criar ou obter o cliente
        $customer_result = $this->criarOuObterCliente($dados);

        if (!$customer_result['success']) {
            return $customer_result;
        }

        $customer_id = $customer_result['data']['id'];

        // Mapear forma de pagamento
        $billing_type = match ($dados['forma_pagamento'] ?? 'boleto') {
            'pix' => 'PIX',
            'cartao_credito' => 'CREDIT_CARD',
            default => 'BOLETO'
        };

        // Criar assinatura
        $payload = [
            'customer' => $customer_id,
            'billingType' => $billing_type,
            'value' => (float) $dados['valor'],
            'nextDueDate' => $dados['proxima_cobranca'] ?? date('Y-m-d', strtotime('+30 days')),
            'cycle' => 'MONTHLY', // Mensal
            'description' => $dados['descricao'] ?? 'Assinatura Mensal',
            'externalReference' => $dados['referencia_externa'] ?? ''
        ];

        if (!empty($this->wallet_id)) {
            $payload['walletId'] = $this->wallet_id;
        }

        return $this->fazerRequisicao('POST', '/subscriptions', $payload);
    }

    /**
     * Cancela uma assinatura recorrente
     */
    public function cancelarAssinatura($asaas_subscription_id)
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Asaas não configurado'];
        }

        return $this->fazerRequisicao('DELETE', "/subscriptions/$asaas_subscription_id");
    }

    /**
     * Obtém informações de uma assinatura
     */
    public function obterAssinatura($asaas_subscription_id)
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Asaas não configurado'];
        }

        return $this->fazerRequisicao('GET', "/subscriptions/$asaas_subscription_id");
    }

    /**
     * Prepara dados do cliente para Asaas (método auxiliar)
     */
    private function prepararDadosCliente($dados)
    {
        return [
            'name' => $dados['nome_cliente'],
            'email' => $dados['email_cliente'],
            'cpfCnpj' => $this->limparCPFCNPJ($dados['cpf_cnpj']),
            'phone' => $dados['telefone'] ?? '',
            'address' => $dados['endereco'] ?? '',
            'addressNumber' => $dados['numero'] ?? '',
            'complement' => $dados['complemento'] ?? '',
            'province' => $dados['bairro'] ?? '',
            'city' => $dados['cidade'] ?? '',
            'state' => $dados['estado'] ?? '',
            'postalCode' => $this->limparCEP($dados['cep'] ?? '')
        ];
    }

    /**
     * Faz requisição à API do Asaas
     */
    public function fazerRequisicao($metodo, $endpoint, $dados = null)
    {
        // Verificar se está configurado
        if (empty($this->api_key) || empty($this->base_url)) {
            return [
                'success' => false,
                'error' => 'API não configurada (API Key ou Base URL ausente)',
                'api_key_configured' => !empty($this->api_key),
                'base_url_configured' => !empty($this->base_url)
            ];
        }

        $url = $this->base_url . $endpoint;

        // Montar headers como string (formato correto para file_get_contents)
        $ua_name = defined('APP_NAME') ? APP_NAME : 'GestaoCertificado';
        $ua_version = defined('APP_VERSION') ? APP_VERSION : '1.0';
        $ua_url = defined('APP_URL') ? APP_URL : 'http://localhost';
        $token = trim((string) $this->api_key);
        $headers = "User-Agent: {$ua_name}/{$ua_version} ({$ua_url})\r\n"
            . "Content-Type: application/json\r\n"
            . "Accept: application/json\r\n"
            . "access_token: " . $token . "\r\n"
            . "Authorization: Bearer " . $token . "\r\n";

        $opcoes = [
            'http' => [
                'method' => $metodo,
                'header' => $headers,
                'timeout' => 30,
                'ignore_errors' => true // Importante para capturar erros HTTP
            ]
        ];

        if ($dados && in_array($metodo, ['POST', 'PUT'])) {
            $opcoes['http']['content'] = json_encode($dados);
        }

        $contexto = stream_context_create($opcoes);

        try {
            $resposta = @file_get_contents($url, false, $contexto);

            // Pegar informações do HTTP response
            $http_response_header_array = $http_response_header ?? [];
            $http_code = 0;

            if (!empty($http_response_header_array)) {
                preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header_array[0], $matches);
                $http_code = isset($matches[1]) ? (int) $matches[1] : 0;
            }

            // Se resposta é false E não temos headers, houve erro de conexão
            if ($resposta === false && empty($http_response_header_array)) {
                $error_msg = error_get_last()['message'] ?? 'Erro ao conectar com Asaas';
                return [
                    'success' => false,
                    'error' => $error_msg,
                    'http_code' => 0,
                    'url' => $url,
                    'api_key_configured' => !empty($this->api_key),
                    'base_url' => $this->base_url
                ];
            }

            // Decodificar resposta (mesmo se for erro HTTP)
            $dados_resposta = null;
            if (!empty($resposta)) {
                $dados_resposta = json_decode($resposta, true);
            }

            // Se HTTP code indica erro (4xx ou 5xx)
            if ($http_code >= 400) {
                $error_msg = 'Erro HTTP ' . $http_code;

                // Tentar extrair mensagem de erro da resposta
                if (isset($dados_resposta['errors']) && is_array($dados_resposta['errors'])) {
                    $error_details = [];
                    foreach ($dados_resposta['errors'] as $error) {
                        $error_details[] = $error['description'] ?? $error['detail'] ?? $error['code'] ?? 'Erro desconhecido';
                    }
                    $error_msg = implode(', ', $error_details);
                } elseif (isset($dados_resposta['error'])) {
                    $error_msg = $dados_resposta['error'];
                } elseif (isset($dados_resposta['message'])) {
                    $error_msg = $dados_resposta['message'];
                }

                return [
                    'success' => false,
                    'error' => $error_msg,
                    'http_code' => $http_code,
                    'url' => $url,
                    'api_key_configured' => !empty($this->api_key),
                    'response_data' => $dados_resposta,
                    'raw_response' => $resposta
                ];
            }

            // Sucesso
            return [
                'success' => true,
                'data' => $dados_resposta,
                'http_code' => $http_code,
                'url' => $url
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'url' => $url ?? 'N/A'
            ];
        }
    }

    /**
     * Limpa CPF/CNPJ
     */
    private function limparCPFCNPJ($valor)
    {
        return preg_replace('/[^0-9]/', '', $valor);
    }

    /**
     * Limpa CEP
     */
    private function limparCEP($valor)
    {
        return preg_replace('/[^0-9]/', '', $valor);
    }

    /**
     * Processa webhook do Asaas (suporta Boleto, PIX e Cartão)
     */
    public function processarWebhook($dados_webhook)
    {
        $tipo_evento = $dados_webhook['event'] ?? '';
        $asaas_id = $dados_webhook['payment']['id'] ?? '';
        $status_asaas = $dados_webhook['payment']['status'] ?? '';
        $billing_type = $dados_webhook['payment']['billingType'] ?? '';

        // Mapear tipo de cobrança
        $tipo_local = $this->mapearTipoCobranca($billing_type);

        // Mapear status
        $status_local = $this->mapearStatus($status_asaas);

        // Buscar cobrança no banco (tenta asaas_cobrancas primeiro, depois asaas_boletos para compatibilidade)
        $cobranca = null;
        $cobranca_id = null;
        $tabela_usada = null;

        // Tentar buscar em asaas_cobrancas (nova tabela)
        $stmt = $this->conn->prepare("SELECT id, fatura_id, assinatura_id FROM asaas_cobrancas WHERE asaas_id = ?");
        if ($stmt) {
            $stmt->bind_param("s", $asaas_id);
            $stmt->execute();
            $resultado = $stmt->get_result();

            if ($resultado->num_rows > 0) {
                $cobranca = $resultado->fetch_assoc();
                $cobranca_id = $cobranca['id'];
                $tabela_usada = 'asaas_cobrancas';
            }
            $stmt->close();
        }

        // Se não encontrou, tentar em asaas_boletos (tabela antiga - compatibilidade)
        if (!$cobranca) {
            $stmt = $this->conn->prepare("SELECT id, fatura_id, assinatura_id FROM asaas_boletos WHERE asaas_id = ?");
            if ($stmt) {
                $stmt->bind_param("s", $asaas_id);
                $stmt->execute();
                $resultado = $stmt->get_result();

                if ($resultado->num_rows > 0) {
                    $cobranca = $resultado->fetch_assoc();
                    $cobranca_id = $cobranca['id'];
                    $tabela_usada = 'asaas_boletos';
                }
                $stmt->close();
            }
        }

        if (!$cobranca) {
            return ['success' => false, 'error' => 'Cobrança não encontrada'];
        }

        $fatura_id = $cobranca['fatura_id'];
        $assinatura_id = $cobranca['assinatura_id'];

        // Atualizar status da cobrança
        if ($tabela_usada === 'asaas_cobrancas') {
            $stmt = $this->conn->prepare("UPDATE asaas_cobrancas SET status = ?, tipo_cobranca = ? WHERE id = ?");
            $stmt->bind_param("ssi", $status_local, $tipo_local, $cobranca_id);
        } else {
            $stmt = $this->conn->prepare("UPDATE asaas_boletos SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status_local, $cobranca_id);
        }
        $stmt->execute();
        $stmt->close();

        // Registrar webhook
        $dados_json = json_encode($dados_webhook);
        // Detectar coluna disponível em asaas_webhooks (compatibilidade: boleto_id vs cobranca_id)
        $colunaPreferencial = ($tabela_usada === 'asaas_cobrancas') ? 'cobranca_id' : 'boleto_id';
        $coluna_id = $colunaPreferencial;
        try {
            // Verificar se existe coluna 'cobranca_id'
            $temCobrancaId = false;
            if ($stmt = $this->conn->prepare("SHOW COLUMNS FROM asaas_webhooks LIKE 'cobranca_id'")) {
                $stmt->execute();
                $res = $stmt->get_result();
                $temCobrancaId = ($res && $res->num_rows > 0);
                $stmt->close();
            }
            // Verificar se existe coluna 'boleto_id'
            $temBoletoId = false;
            if ($stmt = $this->conn->prepare("SHOW COLUMNS FROM asaas_webhooks LIKE 'boleto_id'")) {
                $stmt->execute();
                $res = $stmt->get_result();
                $temBoletoId = ($res && $res->num_rows > 0);
                $stmt->close();
            }
            if ($colunaPreferencial === 'cobranca_id' && !$temCobrancaId && $temBoletoId) {
                $coluna_id = 'boleto_id';
            } elseif ($colunaPreferencial === 'boleto_id' && !$temBoletoId && $temCobrancaId) {
                $coluna_id = 'cobranca_id';
            }
        } catch (\Throwable $e) {
            // mantém $coluna_id como preferencial se checagem falhar
        }

        $stmt = $this->conn->prepare("
            INSERT INTO asaas_webhooks ($coluna_id, tipo_evento, dados_evento, status_processamento)
            VALUES (?, ?, ?, 'processado')
        ");
        $stmt->bind_param("iss", $cobranca_id, $tipo_evento, $dados_json);
        $stmt->execute();
        $stmt->close();

        // Se pagamento confirmado ou recebido
        if (in_array($status_local, ['confirmado', 'recebido'])) {
            // Atualizar fatura
            if ($fatura_id) {
                $this->atualizarFaturaPaga($fatura_id);
            }

            // Renovar assinatura (se houver)
            if ($assinatura_id) {
                $this->renovarAssinatura($assinatura_id);
            }
        }

        return ['success' => true];
    }

    /**
     * Mapeia tipo de cobrança do Asaas para tipo local
     */
    private function mapearTipoCobranca($billing_type)
    {
        $mapa = [
            'BOLETO' => 'boleto',
            'PIX' => 'pix',
            'CREDIT_CARD' => 'cartao_credito',
            'DEBIT_CARD' => 'cartao_credito',
            'UNDEFINED' => 'boleto'
        ];

        return $mapa[$billing_type] ?? 'boleto';
    }

    /**
     * Renova assinatura após pagamento confirmado
     */
    private function renovarAssinatura($assinatura_id)
    {
        // Buscar assinatura
        $stmt = $this->conn->prepare("
            SELECT id, data_vencimento, certificados_totais, status
            FROM assinaturas
            WHERE id = ?
        ");
        $stmt->bind_param("i", $assinatura_id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 0) {
            return false;
        }

        $assinatura = $resultado->fetch_assoc();
        $stmt->close();

        // Calcular nova data de vencimento (+30 dias)
        $nova_data = date('Y-m-d', strtotime('+30 days'));

        // Atualizar assinatura
        $stmt = $this->conn->prepare("
            UPDATE assinaturas
            SET status = 'ativa',
                data_vencimento = ?,
                certificados_usados = 0,
                certificados_disponiveis = certificados_totais,
                atualizado_em = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param("si", $nova_data, $assinatura_id);
        $stmt->execute();
        $stmt->close();

        return true;
    }

    /**
     * Mapeia status do Asaas para status local
     */
    private function mapearStatus($status_asaas)
    {
        $mapa = [
            'PENDING' => 'pendente',
            'CONFIRMED' => 'confirmado',
            'RECEIVED' => 'recebido',
            'OVERDUE' => 'expirado',
            'CANCELLED' => 'cancelado',
            'REFUNDED' => 'cancelado'
        ];

        return $mapa[$status_asaas] ?? 'pendente';
    }

    /**
     * Atualiza fatura como paga
     */
    private function atualizarFaturaPaga($fatura_id)
    {
        if (!$fatura_id) {
            return false;
        }

        $status = 'paga';
        $stmt = $this->conn->prepare("UPDATE faturas SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $fatura_id);
        $stmt->execute();
        $stmt->close();

        // Criar registro de pagamento
        $stmt = $this->conn->prepare("
            SELECT parceiro_id, valor, forma_pagamento
            FROM faturas
            WHERE id = ?
        ");
        $stmt->bind_param("i", $fatura_id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0) {
            $fatura = $resultado->fetch_assoc();
            $stmt->close();

            // Inserir em pagamentos
            $stmt = $this->conn->prepare("
                INSERT INTO pagamentos (parceiro_id, descricao, valor, data_pagamento, metodo, status, criado_em)
                VALUES (?, ?, ?, NOW(), ?, 'pago', NOW())
            ");

            $descricao = "Pagamento da fatura #" . $fatura_id;
            $metodo = $fatura['forma_pagamento'] ?? 'boleto';

            $stmt->bind_param(
                "isds",
                $fatura['parceiro_id'],
                $descricao,
                $fatura['valor'],
                $metodo
            );
            $stmt->execute();
            $stmt->close();
        }

        return true;
    }
}
?>