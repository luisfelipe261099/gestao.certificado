# Referência de Estrutura do Banco de Dados
## Sistema EAD FaCiencia

Este documento contém a estrutura completa das tabelas do banco de dados para referência rápida durante o desenvolvimento.

---

## Tabelas Principais

### `parceiros`
```sql
- id (int)
- nome_empresa (varchar 200) ⚠️ NÃO É "nome"
- cnpj (varchar 18)
- email (varchar 150)
- telefone (varchar 20)
- endereco (text)
- cidade (varchar 100)
- estado (varchar 2)
- cep (varchar 10)
- pais (varchar 100)
- website (varchar 255)
- logo_url (varchar 255)
- ativo (tinyint 1)
- ead_ativo (tinyint 1)
- termos_aceitos (tinyint 1)
- verificado (tinyint 1)
- data_verificacao (timestamp)
- criado_em (timestamp)
- atualizado_em (timestamp)
```

### `alunos`
```sql
- id (bigint)
- parceiro_id (int)
- nome (varchar 200)
- email (varchar 150)
- senha_hash (varchar 255) ⚠️ ADICIONADA POR MIGRAÇÃO
- cpf (varchar 14)
- telefone (varchar 20)
- data_nascimento (date)
- endereco (text)
- cidade (varchar 100)
- estado (varchar 2)
- cep (varchar 10)
- ativo (tinyint 1)
- criado_em (timestamp)
- atualizado_em (timestamp)
```

### `cursos`
```sql
- id (int)
- parceiro_id (int)
- nome (varchar 255)
- descricao (text)
- carga_horaria (int)
- instrutor (varchar 200)
- categoria (varchar 100)
- nivel (enum: 'iniciante', 'intermediario', 'avancado')
- ativo (tinyint 1)
- criado_em (timestamp)
- atualizado_em (timestamp)
```

### `inscricoes_alunos`
```sql
- id (bigint)
- aluno_id (bigint)
- curso_id (int)
- status (enum: 'inscrito', 'em_progresso', 'concluido', 'cancelado')
- data_inscricao (timestamp)
- data_conclusao (datetime)
- nota_final (decimal 5,2)
- criado_em (timestamp)
```

### `ead_aulas`
```sql
- id (int)
- curso_id (int)
- titulo (varchar 255)
- descricao (text)
- ordem (int)
- duracao_minutos (int)
- ativa (tinyint 1)
- criado_em (timestamp)
```

### `conteudo_aulas`
```sql
- id (int)
- aula_id (int)
- tipo (enum: 'video', 'pdf', 'texto', 'link')
- titulo (varchar 255)
- conteudo (text)
- url (varchar 500)
- ordem (int)
- criado_em (timestamp)
```

### `ead_exercicios`
```sql
- id (int)
- aula_id (int)
- titulo (varchar 255)
- descricao (text)
- tipo (enum: 'multipla_escolha', 'dissertativa', 'pratica')
- pontuacao_maxima (int)
- criado_em (timestamp)
⚠️ NÃO TEM COLUNA "ativo"
```

### `questoes_exercicios`
```sql
- id (int)
- exercicio_id (int)
- enunciado (text)
- tipo (enum: 'multipla_escolha', 'verdadeiro_falso', 'dissertativa')
- pontuacao (int)
- ordem (int)
- criado_em (timestamp)
```

### `opcoes_questoes`
```sql
- id (int)
- questao_id (int)
- texto (text)
- correta (tinyint 1)
- ordem (int)
- criado_em (timestamp)
```

### `ead_progresso_aluno`
```sql
- id (int)
- inscricao_id (bigint)
- aula_id (int)
- conteudo_id (int)
- visualizado (tinyint 1)
- tempo_gasto_minutos (int)
- data_visualizacao (datetime)
- data_conclusao (datetime)
```

### `ead_respostas_exercicios`
```sql
- id (int)
- inscricao_id (bigint)
- exercicio_id (int)
- questao_id (int)
- resposta (text)
- opcao_id (int)
- correta (tinyint 1)
- pontuacao_obtida (decimal 5,2)
- data_resposta (timestamp)
```

### `certificados`
```sql
- id (int)
- parceiro_id (int)
- nome (varchar 255)
- descricao (text)
- template (text)
- ativo (tinyint 1)
- criado_em (timestamp)
- atualizado_em (timestamp)
```

### `usuarios_parceiro`
```sql
- id (int)
- parceiro_id (int)
- nome (varchar 200)
- email (varchar 150)
- senha (varchar 255)
- cargo (varchar 100)
- ativo (tinyint 1)
- criado_em (timestamp)
- atualizado_em (timestamp)
```

### `administradores`
```sql
- id (int)
- nome (varchar 200)
- email (varchar 150)
- senha (varchar 255)
- nivel (enum: 'super', 'admin', 'moderador')
- ativo (tinyint 1)
- criado_em (timestamp)
- atualizado_em (timestamp)
```

---

## ⚠️ ATENÇÃO - Erros Comuns

### 1. Tabela `parceiros`
- ❌ `p.nome` 
- ✅ `p.nome_empresa`

### 2. Tabela `alunos`
- ❌ `senha`
- ✅ `senha_hash`

### 3. Tabela `ead_exercicios`
- ❌ `WHERE ativo = 1`
- ✅ Não tem coluna `ativo`, use apenas `WHERE aula_id = ?`

### 4. Tabela `inscricoes_alunos`
- Status válidos: `'inscrito'`, `'em_progresso'`, `'concluido'`, `'cancelado'`
- ❌ `'suspenso'` ou `'salvo'`
- ✅ Use `'cancelado'` para cursos suspensos

---

## Queries Comuns Corretas

### Buscar cursos do aluno com progresso
```sql
SELECT 
    ia.id as inscricao_id,
    ia.curso_id,
    ia.status,
    c.nome as curso_nome,
    c.descricao,
    c.carga_horaria,
    c.instrutor,
    p.nome_empresa as parceiro_nome,  -- ⚠️ nome_empresa, não nome
    COUNT(DISTINCT a.id) as total_aulas,
    COUNT(DISTINCT CASE WHEN pa.visualizado = 1 THEN pa.aula_id END) as aulas_concluidas,
    ROUND(
        (COUNT(DISTINCT CASE WHEN pa.visualizado = 1 THEN pa.aula_id END) * 100.0) / 
        NULLIF(COUNT(DISTINCT a.id), 0), 
        0
    ) as progresso
FROM inscricoes_alunos ia
INNER JOIN cursos c ON ia.curso_id = c.id
INNER JOIN parceiros p ON c.parceiro_id = p.id
LEFT JOIN ead_aulas a ON c.id = a.curso_id AND a.ativa = 1
LEFT JOIN ead_progresso_aluno pa ON ia.id = pa.inscricao_id AND a.id = pa.aula_id
WHERE ia.aluno_id = ?
GROUP BY ia.id, ia.curso_id, ia.status, c.nome, c.descricao, c.carga_horaria, c.instrutor, p.nome_empresa
ORDER BY ia.data_inscricao DESC
```

### Buscar aulas com exercícios
```sql
SELECT 
    a.id,
    a.titulo,
    a.descricao,
    a.ordem,
    a.duracao_minutos,
    (SELECT COUNT(*) FROM conteudo_aulas WHERE aula_id = a.id) as total_conteudos,
    (SELECT COUNT(*) FROM ead_exercicios WHERE aula_id = a.id) as total_exercicios  -- ⚠️ SEM "AND ativo = 1"
FROM ead_aulas a
WHERE a.curso_id = ? AND a.ativa = 1
ORDER BY a.ordem ASC
```

### Login do aluno
```sql
SELECT * FROM alunos 
WHERE email = ? AND ativo = 1
-- Depois verificar: password_verify($senha, $aluno['senha_hash'])
```

---

## Relacionamentos

```
parceiros (1) ----< (N) cursos
parceiros (1) ----< (N) alunos
parceiros (1) ----< (N) certificados
parceiros (1) ----< (N) usuarios_parceiro

cursos (1) ----< (N) ead_aulas
cursos (1) ----< (N) inscricoes_alunos

ead_aulas (1) ----< (N) conteudo_aulas
ead_aulas (1) ----< (N) ead_exercicios

ead_exercicios (1) ----< (N) questoes_exercicios
questoes_exercicios (1) ----< (N) opcoes_questoes

inscricoes_alunos (1) ----< (N) ead_progresso_aluno
inscricoes_alunos (1) ----< (N) ead_respostas_exercicios
```

---

**Última atualização:** 2025-11-06
**Fonte:** sistema_parceiro_murilo.sql

