# 🚀 Guia de Contribuição - Duralux CRM

Obrigado por considerar contribuir para o Duralux CRM! Este documento contém diretrizes para ajudar você a contribuir de forma efetiva.

## 📋 Índice
- [Código de Conduta](#código-de-conduta)
- [Como Contribuir](#como-contribuir)
- [Reportar Bugs](#reportar-bugs)
- [Sugerir Funcionalidades](#sugerir-funcionalidades)
- [Pull Requests](#pull-requests)
- [Padrões de Código](#padrões-de-código)
- [Configuração do Ambiente](#configuração-do-ambiente)

## 🤝 Código de Conduta

Este projeto adere ao [Contributor Covenant Code of Conduct](https://www.contributor-covenant.org/). Ao participar, você concorda em manter este código.

### Comportamentos Esperados:
- Usar linguagem acolhedora e inclusiva
- Respeitar diferentes pontos de vista
- Aceitar críticas construtivas
- Focar no que é melhor para a comunidade
- Mostrar empatia com outros membros

## 🛠️ Como Contribuir

### 1. **Fork e Clone**
```bash
# 1. Fork o repositório no GitHub
# 2. Clone seu fork
git clone https://github.com/SEU_USUARIO/duralux.git
cd duralux

# 3. Adicione o repositório original como upstream
git remote add upstream https://github.com/Ivon-Puc/duralux.git
```

### 2. **Configurar Ambiente**
```bash
# Instalar dependências (se houver)
# Configurar banco de dados
cd backend
php init.php

# Testar se tudo está funcionando
php -S localhost:8000
```

### 3. **Criar Branch**
```bash
# Criar branch para sua contribuição
git checkout -b feature/minha-nova-funcionalidade
# ou
git checkout -b fix/correcao-bug
# ou  
git checkout -b docs/melhorar-documentacao
```

### 4. **Fazer Alterações**
- Implemente sua funcionalidade ou correção
- Siga os [padrões de código](#padrões-de-código)
- Adicione testes se aplicável
- Atualize documentação se necessário

### 5. **Testar**
```bash
# Execute todos os testes
# Acesse: http://localhost:8000/duralux-admin/test-dashboard.html
# Verifique se tudo está funcionando
```

### 6. **Commit e Push**
```bash
# Adicionar arquivos
git add .

# Commit com mensagem descritiva
git commit -m "feat: adicionar funcionalidade X"

# Push para seu fork
git push origin feature/minha-nova-funcionalidade
```

### 7. **Abrir Pull Request**
- Vá para seu fork no GitHub
- Clique em "Compare & pull request"
- Preencha o template de PR
- Aguarde review

## 🐛 Reportar Bugs

### Antes de Reportar:
- Verifique se o bug já foi reportado nas [Issues](https://github.com/Ivon-Puc/duralux/issues)
- Certifique-se de que está usando a versão mais recente
- Teste em ambiente limpo

### Template de Bug Report:
```markdown
**Descrição do Bug**
Uma descrição clara e concisa do problema.

**Passos para Reproduzir**
1. Vá para '...'
2. Clique em '....'
3. Role até '....'
4. Veja o erro

**Comportamento Esperado**
O que você esperava que acontecesse.

**Comportamento Atual**
O que realmente aconteceu.

**Screenshots**
Se aplicável, adicione screenshots.

**Ambiente:**
- OS: [ex: Windows 10, Ubuntu 20.04]
- Browser: [ex: Chrome 95, Firefox 94]
- PHP Version: [ex: 8.1]
- Version: [ex: 1.0.0]

**Contexto Adicional**
Qualquer outro contexto sobre o problema.
```

## 💡 Sugerir Funcionalidades

### Template de Feature Request:
```markdown
**Funcionalidade Solicitada**
Uma descrição clara da funcionalidade.

**Problema que Resolve**
Qual problema esta funcionalidade resolveria?

**Solução Proposta**
Como você imagina que a funcionalidade deveria funcionar?

**Alternativas Consideradas**
Outras soluções que você considerou.

**Contexto Adicional**
Screenshots, mockups, ou qualquer contexto adicional.
```

## 🔄 Pull Requests

### Checklist do PR:
- [ ] Código segue os padrões do projeto
- [ ] Testes passando (se aplicável)
- [ ] Documentação atualizada
- [ ] Sem conflitos de merge
- [ ] Descrição clara do que foi alterado
- [ ] Screenshots (se mudanças visuais)

### Template de Pull Request:
```markdown
## Tipo de Mudança
- [ ] Bug fix (correção que resolve um issue)
- [ ] Nova funcionalidade (adição que não quebra funcionalidade existente)
- [ ] Breaking change (correção ou funcionalidade que quebra funcionalidade existente)
- [ ] Documentação

## Descrição
Descreva suas mudanças em detalhes.

## Issues Relacionadas
Fixes #[número da issue]

## Como Foi Testado?
Descreva os testes que você executou.

## Screenshots (se aplicável):
Adicione screenshots das mudanças.

## Checklist:
- [ ] Meu código segue os padrões do projeto
- [ ] Fiz uma auto-review do meu código
- [ ] Comentei meu código em partes complexas
- [ ] Fiz mudanças correspondentes na documentação
- [ ] Minhas mudanças não geram novos warnings
- [ ] Adicionei testes que provam que minha correção/funcionalidade funciona
- [ ] Testes novos e existentes passam localmente
```

## 📝 Padrões de Código

### PHP
```php
<?php
/**
 * Classe de exemplo seguindo padrões
 */
class ExampleController extends BaseController 
{
    /**
     * Método de exemplo
     * 
     * @param array $params Parâmetros do método
     * @return array Resultado do processamento
     */
    public function exampleMethod(array $params = []): array 
    {
        try {
            // Lógica do método
            $result = $this->processData($params);
            
            return $this->successResponse('Sucesso', $result);
        } catch (Exception $e) {
            error_log("Erro em exampleMethod: " . $e->getMessage());
            return $this->errorResponse('Erro interno');
        }
    }
}
```

### JavaScript
```javascript
/**
 * Classe de exemplo seguindo padrões
 */
class ExampleClass {
    constructor() {
        this.apiBase = '../backend/api/router.php';
        this.init();
    }

    /**
     * Método de exemplo
     * @param {Object} data - Dados para processar
     * @returns {Promise<Object>} Resultado da API
     */
    async exampleMethod(data) {
        try {
            const response = await fetch(this.apiBase, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            return await response.json();
        } catch (error) {
            console.error('Erro em exampleMethod:', error);
            throw error;
        }
    }
}
```

### Convenções:

#### **PHP**
- PSR-4 para autoloading
- CamelCase para classes e métodos
- snake_case para colunas de banco
- Documentação PHPDoc
- Type hints quando possível
- Try/catch para tratamento de erros

#### **JavaScript**
- ES6+ com classes
- camelCase para variáveis e funções
- Async/await para operações assíncronas
- JSDoc para documentação
- Const/let ao invés de var
- Arrow functions quando apropriado

#### **HTML/CSS**
- Indentação com 4 espaços
- Nomes de classes descritivos
- Semântica HTML5
- Bootstrap classes quando possível
- Comentários para seções complexas

### Mensagens de Commit:
```bash
# Formato: tipo(escopo): descrição

feat(dashboard): adicionar gráfico de vendas
fix(customers): corrigir validação de email
docs(readme): atualizar instruções de instalação
style(css): melhorar responsividade mobile
refactor(api): otimizar consultas de banco
test(customers): adicionar testes de CRUD
chore(deps): atualizar dependências
```

## 🔧 Configuração do Ambiente

### Pré-requisitos:
- PHP 8.0+
- SQLite3
- Servidor web ou PHP built-in server
- Git

### Configuração:
```bash
# 1. Clone e configure
git clone https://github.com/Ivon-Puc/duralux.git
cd duralux

# 2. Configurar permissões
chmod -R 755 .
chmod -R 777 backend/uploads/
chmod -R 777 backend/database/

# 3. Inicializar banco
cd backend
php init.php

# 4. Testar
php -S localhost:8000
```

### Estrutura de Desenvolvimento:
```
duralux/
├── backend/           # Desenvolvimento backend
├── duralux-admin/    # Desenvolvimento frontend  
├── docs/             # Documentação
└── tests/            # Testes (futuro)
```

## 🧪 Testes

### Como Executar:
```bash
# Acessar página de testes
http://localhost:8000/duralux-admin/test-dashboard.html

# Verificar se todos os testes passam
# Relatar falhas encontradas
```

### Adicionando Testes:
- Testes de API em `test-*.html`
- Testes unitários (planejado)
- Testes de integração (planejado)

## 📞 Dúvidas?

- **Issues**: [GitHub Issues](https://github.com/Ivon-Puc/duralux/issues)
- **Discussões**: [GitHub Discussions](https://github.com/Ivon-Puc/duralux/discussions)
- **Email**: ivon@sga.pucminas.br

## 🎉 Reconhecimento

Contribuidores serão listados no README e releases do projeto!

---

**Obrigado por contribuir para o Duralux CRM!** 🙏