# Sistema de Gestão de Certificados

Sistema web completo para gestão e emissão de certificados digitais, desenvolvido em PHP com arquitetura MVP.

## 📋 Funcionalidades

### Painel do Parceiro
- Dashboard com estatísticas de certificados emitidos
- Gestão de cursos e alunos
- Emissão de certificados personalizados
- Templates personalizáveis com editor visual
- Integração com gateway de pagamento (Asaas)
- Módulo EAD integrado

### Painel Administrativo
- Gestão de parceiros e planos
- Aprovação de mudanças de plano
- Templates de certificados do sistema
- Configurações de integração

### Recursos Técnicos
- Geração de PDF com FPDF
- QR Code para validação de certificados
- Múltiplos métodos de pagamento (Boleto, PIX, Cartão)
- Webhooks para confirmação automática
- Sistema de assinaturas recorrentes

## 🛠️ Tecnologias

- **Backend:** PHP 7.4+
- **Banco de dados:** MySQL/MariaDB
- **Frontend:** HTML5, CSS3, JavaScript, Bootstrap
- **PDF:** FPDF
- **Pagamentos:** Asaas API
- **EAD:** Next.js (módulo separado)

## 📦 Instalação

### Requisitos
- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Composer
- Apache/Nginx

### Passos

1. Clone o repositório:
```bash
git clone https://github.com/seu-usuario/gestao-certificados.git
cd gestao-certificados
```

2. Instale as dependências:
```bash
composer install
```

3. Configure o banco de dados:
```bash
cp app/config/config.example.php app/config/config.php
# Edite config.php com suas credenciais
```

4. Importe o schema do banco:
```bash
mysql -u root -p < migrations/schema.sql
```

5. Configure o servidor web para apontar para a pasta do projeto.

## 📁 Estrutura do Projeto

```
├── admin/          # Painel administrativo
├── app/
│   ├── actions/    # Processamento de formulários
│   ├── config/     # Configurações
│   ├── lib/        # Bibliotecas (AsaasAPI, FPDF)
│   ├── models/     # Models do banco
│   ├── presenters/ # Lógica de apresentação
│   └── views/      # Componentes de view
├── assets/         # Imagens e recursos
├── css/            # Estilos CSS
├── ead/            # Módulo EAD (Next.js)
├── js/             # Scripts JavaScript
├── migrations/     # Scripts de migração
├── parceiro/       # Painel do parceiro
└── uploads/        # Arquivos enviados
```

## 🔐 Variáveis de Ambiente

Configure as seguintes variáveis no arquivo `config.php`:

| Variável | Descrição |
|----------|-----------|
| DB_HOST | Host do banco de dados |
| DB_USER | Usuário do banco |
| DB_PASS | Senha do banco |
| DB_NAME | Nome do banco |

## 📄 Licença

Este projeto está sob a licença MIT. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.

## 👤 Autor

**Luis Felipe da Silva**

- GitHub: [@luisfelipe](https://github.com/luisfelipe)
- LinkedIn: [Luis Felipe](https://linkedin.com/in/luisfelipe)
