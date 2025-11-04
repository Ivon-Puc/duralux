# 🚀 Duralux CRM - Sistema de Gestão Empresarial

[![Status](https://img.shields.io/badge/Status-Em%20Desenvolvimento-yellow)](https://github.com/Ivon-Puc/duralux)
[![PHP](https://img.shields.io/badge/PHP-8.0+-blue)](https://php.net)
[![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-yellow)](https://javascript.info)
[![SQLite](https://img.shields.io/badge/Database-SQLite-green)](https://sqlite.org)
[![Bootstrap](https://img.shields.io/badge/Frontend-Bootstrap%205-purple)](https://getbootstrap.com)

## 📋 Índice

- [Sobre o Projeto](#-sobre-o-projeto)
- [Funcionalidades](#-funcionalidades)
- [Tecnologias](#-tecnologias)
- [Instalação](#-instalação)
- [Estrutura do Projeto](#-estrutura-do-projeto)
- [API Documentation](#-api-documentation)
- [Componentes Frontend](#-componentes-frontend)
- [Sistema de Autenticação](#-sistema-de-autenticação)
- [Dashboard](#-dashboard)
- [Gestão de Clientes](#-gestão-de-clientes)
- [Gestão de Produtos](#-gestão-de-produtos)
- [Testes](#-testes)
- [Contribuição](#-contribuição)
- [Licença](#-licença)

## 🎯 Sobre o Projeto

O **Duralux CRM** é um sistema completo de gestão empresarial desenvolvido com PHP e JavaScript, focado em proporcionar uma experiência moderna e intuitiva para gerenciamento de clientes, produtos, vendas e projetos.

### 🌟 Características Principais

- **Interface Moderna**: Design responsivo com Bootstrap 5
- **API RESTful**: Backend PHP com arquitetura MVC
- **Dashboard Dinâmico**: Estatísticas em tempo real
- **Sistema Seguro**: Autenticação robusta e proteção CSRF
- **Código Limpo**: PSR-4, documentação completa
- **Testes Integrados**: Validação automática de funcionalidades

## ✨ Funcionalidades

### ✅ **Implementadas**

#### 🎛️ **Dashboard Inteligente**
- Estatísticas em tempo real
- Cards dinâmicos com dados da API
- Auto-refresh automático (30s)
- Gráficos e métricas de performance
- Indicadores visuais de progresso

#### 👥 **Gestão de Clientes**
- CRUD completo (Criar, Ler, Atualizar, Deletar)
- Sistema de busca em tempo real
- Paginação inteligente
- Modalais para criação/edição
- Upload de avatares
- Validação de dados robusta

#### 📦 **Gestão de Produtos**
- Catálogo completo de produtos
- Controle de estoque
- Categorização
- Imagens de produtos
- Preços e descontos
- Status de disponibilidade

#### 🔐 **Sistema de Autenticação**
- Login/Logout seguro
- Hash de senhas (bcrypt)
- Proteção CSRF
- Sessões seguras
- Rate limiting
- Logs de atividade

### 🔄 **Em Desenvolvimento**
- Sistema de Leads
- Gestão de Projetos
- Sistema de Pedidos
- Relatórios Avançados
- Notificações em Tempo Real
- Configurações Avançadas

## 🛠️ Tecnologias

### **Backend**
- **PHP 8.0+**: Linguagem principal
- **SQLite**: Banco de dados
- **PDO**: Camada de abstração de dados
- **Arquitetura MVC**: Organização do código

### **Frontend**
- **HTML5 & CSS3**: Estrutura e estilo
- **JavaScript ES6+**: Interatividade
- **Bootstrap 5**: Framework CSS
- **Feather Icons**: Ícones modernos
- **ApexCharts**: Gráficos interativos

### **Ferramentas**
- **Git**: Controle de versão
- **Composer**: Gerenciador de dependências PHP
- **VS Code**: IDE recomendada

## 📥 Instalação

### **Pré-requisitos**
- PHP 8.0 ou superior
- Servidor web (Apache/Nginx) ou PHP built-in server
- SQLite3 habilitado
- Extensões PHP: PDO, SQLite, JSON, Session

### **Passo a Passo**

```bash
# 1. Clone o repositório
git clone https://github.com/Ivon-Puc/duralux.git
cd duralux

# 2. Configurar permissões (Linux/Mac)
chmod -R 755 .
chmod -R 777 backend/uploads/
chmod -R 777 backend/database/

# 3. Inicializar banco de dados
cd backend
php init.php

# 4. Iniciar servidor de desenvolvimento
php -S localhost:8000

# 5. Acessar a aplicação
# Frontend: http://localhost:8000/duralux-admin/
# Testes: http://localhost:8000/duralux-admin/test-dashboard.html
```

### **Configuração de Produção**

```bash
# 1. Configurar Apache/Nginx
# 2. Ajustar config.php para produção
# 3. Configurar HTTPS
# 4. Otimizar banco de dados
# 5. Habilitar logs de erro
```

## 📁 Estrutura do Projeto

```
duralux/
├── 📁 backend/                     # Backend PHP
│   ├── 📁 api/                     # APIs RESTful
│   │   ├── router.php             # Roteador principal
│   │   ├── test.html              # Testes de API
│   │   └── test-*.html            # Testes específicos
│   ├── 📁 classes/                # Controllers MVC
│   │   ├── BaseController.php     # Controller base
│   │   ├── AuthController.php     # Autenticação
│   │   ├── CustomerController.php # Clientes
│   │   ├── ProductController.php  # Produtos
│   │   ├── DashboardController.php# Dashboard
│   │   └── AuthMiddleware.php     # Middleware
│   ├── 📁 config/                 # Configurações
│   │   ├── config.php            # Config principal
│   │   └── database.php          # Config BD
│   ├── 📁 database/              # Banco de dados
│   │   └── duralux.sqlite        # BD SQLite
│   ├── 📁 uploads/               # Arquivos enviados
│   ├── init.php                  # Inicializador
│   └── test.php                  # Testes backend
├── 📁 duralux-admin/             # Frontend
│   ├── 📁 assets/               # Recursos estáticos
│   │   ├── 📁 css/             # Estilos
│   │   ├── 📁 js/              # JavaScripts
│   │   │   ├── duralux-dashboard.js
│   │   │   └── duralux-customers.js
│   │   ├── 📁 images/          # Imagens
│   │   └── 📁 vendors/         # Bibliotecas
│   ├── index.html              # Dashboard principal
│   ├── customers.html          # Gestão de clientes
│   ├── products.html           # Gestão de produtos
│   ├── test-dashboard.html     # Testes frontend
│   ├── system-integration.html # Status sistema
│   └── auth-login-minimal.html # Login
├── 📁 docs/                    # Documentação
│   └── documentations.html    # Docs principais
├── DASHBOARD-COMPLETO.md       # Doc dashboard
├── INSTALACAO.md              # Guia instalação
├── PROGRESSO.md               # Progresso desenvolvimento
└── README.md                  # Este arquivo
```

## 🔌 API Documentation

### **Base URL**
```
/backend/api/router.php
```

### **Autenticação**
Todas as rotas (exceto login) requerem sessão ativa.

#### **Headers Obrigatórios**
```http
Content-Type: application/json
```

### **Endpoints Principais**

#### 🔐 **Autenticação**
```javascript
// Login
POST /backend/api/router.php
{
    "action": "login",
    "email": "admin@duralux.com",
    "password": "admin123"
}

// Verificar sessão
POST /backend/api/router.php
{
    "action": "check_auth"
}

// Logout
POST /backend/api/router.php
{
    "action": "logout"
}
```

#### 📊 **Dashboard**
```javascript
// Estatísticas principais
POST /backend/api/router.php
{
    "action": "get_dashboard_stats"
}

// Dados de receita
POST /backend/api/router.php
{
    "action": "get_revenue_data",
    "period": "month"
}

// Análises de leads
POST /backend/api/router.php
{
    "action": "get_leads_analytics"
}

// Atividades recentes
POST /backend/api/router.php
{
    "action": "get_recent_activities",
    "limit": 10
}
```

#### 👥 **Clientes**
```javascript
// Listar clientes
POST /backend/api/router.php
{
    "action": "get_customers",
    "page": 1,
    "limit": 10,
    "search": "termo"
}

// Obter cliente
POST /backend/api/router.php
{
    "action": "get_customer",
    "id": 1
}

// Criar cliente
POST /backend/api/router.php
{
    "action": "create_customer",
    "name": "João Silva",
    "email": "joao@email.com",
    "phone": "(11) 99999-9999"
}

// Atualizar cliente
POST /backend/api/router.php
{
    "action": "update_customer",
    "id": 1,
    "name": "João Santos",
    "email": "joao.santos@email.com"
}

// Deletar cliente
POST /backend/api/router.php
{
    "action": "delete_customer",
    "id": 1
}
```

#### 📦 **Produtos**
```javascript
// Listar produtos
POST /backend/api/router.php
{
    "action": "get_products",
    "page": 1,
    "limit": 10,
    "category": "categoria"
}

// Criar produto
POST /backend/api/router.php
{
    "action": "create_product",
    "name": "Produto ABC",
    "description": "Descrição do produto",
    "price": 99.90,
    "stock": 100
}
```

### **Respostas da API**

#### **Sucesso**
```json
{
    "success": true,
    "message": "Operação realizada com sucesso",
    "data": {
        // dados retornados
    }
}
```

#### **Erro**
```json
{
    "success": false,
    "message": "Mensagem de erro",
    "errors": {
        "field": "Detalhes do erro"
    }
}
```

## 💻 Componentes Frontend

### **Classes JavaScript Principais**

#### 🎛️ **DuraluxDashboard** (`assets/js/duralux-dashboard.js`)
```javascript
class DuraluxDashboard {
    // Gerencia dashboard dinâmico
    constructor()               // Inicialização
    checkAuthentication()       // Verifica login
    loadDashboardData()         // Carrega dados
    updateMainStats(data)       // Atualiza cards
    setupAutoRefresh()          // Auto-refresh
    formatCurrency(value)       // Formata R$
}
```

#### 👥 **DuraluxCustomers** (`assets/js/duralux-customers.js`)
```javascript
class DuraluxCustomers {
    // Gerencia sistema de clientes
    constructor()               // Inicialização
    loadCustomers()            // Lista clientes
    searchCustomers(term)      // Busca clientes
    createCustomer(data)       // Novo cliente
    updateCustomer(id, data)   // Atualiza cliente
    deleteCustomer(id)         // Remove cliente
    setupPagination()          // Paginação
}
```

### **Recursos Frontend**

#### 🎨 **Interface Responsiva**
- Layout adaptativo Bootstrap 5
- Cards dinâmicos e interativos
- Modais para formulários
- Toasts para notificações
- Loading states profissionais

#### ⚡ **Funcionalidades JavaScript**
- Requisições AJAX assíncronas
- Validação em tempo real
- Auto-complete e busca instantânea
- Paginação dinâmica
- Upload de arquivos com preview

## 🔐 Sistema de Autenticação

### **Arquitetura de Segurança**

#### 🛡️ **AuthController** (`backend/classes/AuthController.php`)
```php
class AuthController extends BaseController {
    public function login()           // Autenticação
    public function logout()          // Encerrar sessão
    public function checkSession()    // Validar sessão
    public function register()        // Novo usuário
    public function forgotPassword()  // Recuperar senha
}
```

#### 🔒 **AuthMiddleware** (`backend/classes/AuthMiddleware.php`)
```php
class AuthMiddleware {
    public static function handle()   // Verificar autenticação
    public static function checkCSRF() // Validar CSRF token
    public static function rateLimit() // Controle de requisições
}
```

### **Recursos de Segurança**

- ✅ **Hash de Senhas**: bcrypt com salt
- ✅ **Proteção CSRF**: Tokens únicos por sessão
- ✅ **Rate Limiting**: Controle de requisições
- ✅ **Sessões Seguras**: Configuração robusta
- ✅ **Validação de Input**: Sanitização completa
- ✅ **Logs de Atividade**: Auditoria de ações

## 📊 Dashboard

### **Estatísticas Implementadas**

#### 💰 **Faturas Aguardando Pagamento**
- Contador baseado em dados reais
- Valores calculados dinamicamente
- Barra de progresso atualizada
- Percentual de pending vs total

#### 🎯 **Leads Convertidos**
- Taxa de conversão inteligente
- Base de cálculo proporcional
- Métricas de performance
- Indicadores visuais de sucesso

#### 📁 **Projetos em Andamento**
- Status de projetos ativos
- Relacionamento com clientes
- Percentual de conclusão
- Timeline de progresso

#### 📈 **Taxa de Conversão Geral**
- Cálculo automático de ROI
- Valor médio de conversão
- Tendências de crescimento
- Comparativo mensal

### **Auto-Refresh e Tempo Real**
```javascript
// Atualização automática a cada 30 segundos
setInterval(() => {
    this.loadDashboardData();
}, 30000);
```

## 👥 Gestão de Clientes

### **Funcionalidades Completas**

#### ✨ **Interface de Clientes**
- **Lista Dinâmica**: Tabela com dados em tempo real
- **Busca Instantânea**: Filtro por nome, email, telefone
- **Paginação Inteligente**: Navegação otimizada
- **Ordenação**: Por qualquer coluna
- **Ações em Lote**: Operações múltiplas

#### 🛠️ **CRUD Completo**
```javascript
// Exemplo de uso da API de clientes
const customers = new DuraluxCustomers();

// Criar cliente
await customers.createCustomer({
    name: 'João Silva',
    email: 'joao@email.com',
    phone: '(11) 99999-9999',
    address: 'Rua A, 123'
});

// Buscar clientes
const results = await customers.searchCustomers('João');

// Atualizar cliente
await customers.updateCustomer(1, {
    name: 'João Santos',
    phone: '(11) 88888-8888'
});
```

#### 🔍 **Validações Implementadas**
- **Email**: Formato válido e unicidade
- **Telefone**: Máscara automática brasileira
- **CPF/CNPJ**: Validação de dígitos
- **CEP**: Auto-complete de endereço
- **Campos Obrigatórios**: Validação em tempo real

### **CustomerController** (`backend/classes/CustomerController.php`)
```php
class CustomerController extends BaseController {
    public function handleRequest()     // Roteamento
    public function index($params)      // Listar clientes
    public function show($params)       // Obter cliente
    public function store()             // Criar cliente
    public function update($params)     // Atualizar cliente
    public function delete($params)     // Deletar cliente
    public function search()            // Buscar clientes
}
```

## 📦 Gestão de Produtos

### **Sistema de Produtos**

#### 🏷️ **Recursos Implementados**
- Catálogo completo de produtos
- Controle de estoque em tempo real
- Categorização hierárquica
- Upload de imagens múltiplas
- Variações de produtos (tamanho, cor)
- Preços promocionais e descontos

#### 💼 **ProductController** (`backend/classes/ProductController.php`)
```php
class ProductController extends BaseController {
    public function handleRequest()     // Gerencia requisições
    public function getProducts()       // Lista produtos
    public function createProduct()     // Novo produto
    public function updateProduct()     // Atualiza produto
    public function deleteProduct()     // Remove produto
    public function updateStock()       // Controle estoque
}
```

## 🧪 Testes

### **Testes Automáticos**

#### 🔍 **test-dashboard.html**
- Verificação de todas as APIs
- Medição de performance (ms)
- Status de conectividade
- Dashboard ao vivo
- Relatório visual de resultados

#### 🧪 **Como Executar Testes**
```bash
# 1. Acessar página de testes
http://localhost:8000/duralux-admin/test-dashboard.html

# 2. Os testes executam automaticamente
# 3. Verificar relatório de resultados
# 4. Dashboard ao vivo se todos passarem
```

#### ✅ **Cobertura de Testes**
- Autenticação de usuário
- Estatísticas do dashboard
- Dados de receita
- Análises de leads
- Análises de projetos
- Atividades recentes
- Performance de APIs
- Conectividade de rede

## 🛠️ Desenvolvimento

### **Padrões de Código**

#### 🏗️ **Arquitetura MVC**
```
Model (Database) ← → Controller ← → View (Frontend)
     ↑                   ↑              ↑
  SQLite PDO         PHP Classes    HTML/JS
```

#### 📝 **Convenções PHP**
- PSR-4 para autoloading
- CamelCase para classes e métodos
- snake_case para variáveis de BD
- Documentação PHPDoc completa
- Tratamento de exceções robusto

#### 🎨 **Convenções JavaScript**
- ES6+ com classes modernas
- camelCase para variáveis e funções
- Async/await para requisições
- Modularização em classes
- Comentários JSDoc

### **Git Workflow**
```bash
# Feature branch
git checkout -b feature/nova-funcionalidade
git add .
git commit -m "feat: implementar nova funcionalidade"
git push origin feature/nova-funcionalidade

# Merge via pull request
```

### **Logs e Debug**
```php
// Backend logging
error_log("Erro: " . $e->getMessage());
$this->logActivity('action_name', $details);

// JavaScript debugging
console.log('Debug info:', data);
console.error('Erro:', error);
```

## 📈 Performance

### **Otimizações Implementadas**

#### ⚡ **Backend**
- Queries SQL otimizadas
- Conexões PDO reutilizáveis
- Cache de sessão inteligente
- Paginação eficiente
- Índices de banco otimizados

#### 🚀 **Frontend**
- Carregamento assíncrono
- Debounce para busca
- Lazy loading de imagens
- Minificação de assets
- Compressão gzip

### **Métricas de Performance**
- Tempo de resposta API: < 100ms
- Carregamento de página: < 2s
- Primeira interação: < 1s
- Bundle JavaScript: < 200KB
- Imagens otimizadas: WebP/JPG

## 🔮 Roadmap

### **Próximas Funcionalidades**

#### 🎯 **Sistema de Leads** (Em Desenvolvimento)
- CRUD de leads/oportunidades
- Pipeline de vendas visual
- Conversão automática para clientes
- Histórico de interações
- Scoring de leads

#### 📊 **Gestão de Projetos**
- Criação de projetos
- Timeline e milestones
- Atribuição de tarefas
- Controle de horas
- Relacionamento com clientes

#### 🛒 **Sistema de Pedidos**
- Criação de pedidos
- Gestão de faturas
- Controle de pagamentos
- Status de entrega
- Relatórios financeiros

#### 📋 **Relatórios Avançados**
- Relatórios personalizáveis
- Exportação PDF/Excel
- Gráficos interativos
- Filtros avançados
- Agendamento de relatórios

#### 🔔 **Notificações**
- Notificações em tempo real
- Sistema de alertas
- Email automático
- Push notifications
- Webhooks

## 🤝 Contribuição

### **Como Contribuir**

1. **Fork** o repositório
2. **Clone** seu fork
3. **Crie** uma branch para sua feature
4. **Implemente** a funcionalidade
5. **Teste** completamente
6. **Documente** as alterações
7. **Envie** um Pull Request

### **Diretrizes**

#### 📋 **Pull Requests**
- Título claro e descritivo
- Descrição detalhada das mudanças
- Screenshots se aplicável
- Testes passando
- Documentação atualizada

#### 🐛 **Reportar Bugs**
- Título descritivo
- Passos para reproduzir
- Resultado esperado vs atual
- Screenshots/logs de erro
- Versão do navegador/PHP

#### 💡 **Sugerir Funcionalidades**
- Descrição clara da necessidade
- Casos de uso detalhados
- Mockups se possível
- Impacto estimado
- Prioridade sugerida

## 📄 Licença

Este projeto está sob a licença **MIT**. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.

---

## 📞 Contato

### **Desenvolvimento**
- **Desenvolvedor**: Ivon Martins
- **Email**: ivon@sga.pucminas.br
- **GitHub**: [Ivon-Puc](https://github.com/Ivon-Puc)

### **Suporte**
- **Issues**: [GitHub Issues](https://github.com/Ivon-Puc/duralux/issues)
- **Documentação**: [Wiki do Projeto](https://github.com/Ivon-Puc/duralux/wiki)
- **Discussões**: [GitHub Discussions](https://github.com/Ivon-Puc/duralux/discussions)

---

<div align="center">

### 🌟 **Se este projeto foi útil, considere dar uma estrela!** ⭐

**Desenvolvido com ❤️ por [Ivon Martins](https://github.com/Ivon-Puc)**

**© 2025 Duralux CRM - Todos os direitos reservados**

</div>