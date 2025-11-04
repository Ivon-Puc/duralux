import os
import shutil

def create_modern_template():
    """Cria template moderno padrão para todas as páginas"""
    
    template = '''<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Duralux - {{PAGE_TITLE}}</title>
    
    <!-- CSS -->
    <link href="/duralux/duralux-admin/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="/duralux/duralux-admin/assets/css/theme.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #f8f9fa;
        }
        .main-header { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            padding: 25px; 
            border-radius: 15px; 
            margin-bottom: 30px; 
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        .sidebar { 
            background: white; 
            min-height: 100vh; 
            padding: 25px 20px; 
            box-shadow: 2px 0 15px rgba(0,0,0,0.1);
            border-radius: 0 15px 15px 0;
        }
        .sidebar .nav-link { 
            color: #495057; 
            padding: 15px 20px; 
            margin: 5px 0; 
            border-radius: 12px; 
            transition: all 0.3s ease;
            font-weight: 500;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        .sidebar .nav-link i { 
            width: 20px; 
            text-align: center; 
        }
        .content-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 25px;
            border: 1px solid rgba(102, 126, 234, 0.1);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 25px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        .table {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        .table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px;
            font-weight: 600;
        }
        .table td {
            padding: 15px;
            border-color: #f8f9fa;
            vertical-align: middle;
        }
        .badge {
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: 500;
        }
        .search-box {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 50px;
            padding: 12px 20px;
            transition: all 0.3s ease;
        }
        .search-box:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            border-left: 5px solid #667eea;
            transition: transform 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 10px;
        }
        .stats-label {
            color: #6c757d;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
        }
    </style>
    {{CUSTOM_CSS}}
</head>

<body>
    <div class="container-fluid p-0">
        <div class="row g-0">
            <!-- Sidebar -->
            <div class="col-md-2">
                <div class="sidebar">
                    <div class="logo mb-4 text-center">
                        <h3 class="text-primary mb-0">
                            <i class="fas fa-gem me-2"></i>Duralux
                        </h3>
                        <small class="text-muted">CRM Professional</small>
                    </div>
                    
                    <nav class="nav flex-column">
                        <a href="/duralux/duralux-admin/index.html" class="nav-link {{DASHBOARD_ACTIVE}}">
                            <i class="fas fa-home me-3"></i> Dashboard
                        </a>
                        <a href="/duralux/duralux-admin/apps-tasks.html" class="nav-link {{TASKS_ACTIVE}}">
                            <i class="fas fa-tasks me-3"></i> Tarefas
                        </a>
                        <a href="/duralux/duralux-admin/customers.html" class="nav-link {{CUSTOMERS_ACTIVE}}">
                            <i class="fas fa-users me-3"></i> Clientes
                        </a>
                        <a href="/duralux/duralux-admin/leads.html" class="nav-link {{LEADS_ACTIVE}}">
                            <i class="fas fa-bullseye me-3"></i> Leads
                        </a>
                        <a href="/duralux/duralux-admin/projects.html" class="nav-link {{PROJECTS_ACTIVE}}">
                            <i class="fas fa-project-diagram me-3"></i> Projetos
                        </a>
                        <a href="/duralux/duralux-admin/reports-sales.html" class="nav-link {{REPORTS_ACTIVE}}">
                            <i class="fas fa-chart-line me-3"></i> Relatórios
                        </a>
                        <a href="/duralux/duralux-admin/analytics-advanced.html" class="nav-link {{ANALYTICS_ACTIVE}}">
                            <i class="fas fa-chart-bar me-3"></i> Analytics
                        </a>
                        <a href="/duralux/duralux-admin/settings-general.html" class="nav-link {{SETTINGS_ACTIVE}}">
                            <i class="fas fa-cog me-3"></i> Configurações
                        </a>
                    </nav>
                    
                    <div class="mt-5 pt-4 border-top">
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center me-3">
                                <i class="fas fa-user text-white"></i>
                            </div>
                            <div>
                                <div class="fw-semibold">Admin User</div>
                                <small class="text-muted">Administrador</small>
                            </div>
                        </div>
                        <a href="/duralux/duralux-admin/auth-login-minimal.html" class="btn btn-outline-danger btn-sm mt-3 w-100">
                            <i class="fas fa-sign-out-alt me-2"></i>Sair
                        </a>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10">
                <div class="p-4">
                    <!-- Header -->
                    <div class="main-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h1 class="mb-1">{{HEADER_ICON}} {{PAGE_TITLE}}</h1>
                                <p class="mb-0 opacity-90">{{PAGE_DESCRIPTION}}</p>
                            </div>
                            <div>
                                {{HEADER_BUTTONS}}
                            </div>
                        </div>
                    </div>

                    {{CONTENT}}
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    {{CUSTOM_SCRIPTS}}
    
    <script>
        // Notificação global
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 350px; border-radius: 10px;';
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 5000);
        }
        
        // Mostrar notificação de boas-vindas
        document.addEventListener('DOMContentLoaded', function() {
            console.log('✅ {{PAGE_TITLE}} carregado com sucesso!');
        });
    </script>
</body>
</html>'''
    
    return template

def create_dashboard_page():
    """Cria página do dashboard moderna"""
    
    template = create_modern_template()
    
    stats_content = '''
    <!-- Estatísticas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-number">R$ 125.750</div>
                <div class="stats-label">Receita Total</div>
                <div class="text-success mt-2">
                    <i class="fas fa-arrow-up me-1"></i>+15.3%
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-number">247</div>
                <div class="stats-label">Clientes Ativos</div>
                <div class="text-success mt-2">
                    <i class="fas fa-arrow-up me-1"></i>+8.2%
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-number">89</div>
                <div class="stats-label">Leads em Aberto</div>
                <div class="text-warning mt-2">
                    <i class="fas fa-minus me-1"></i>-2.1%
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-number">156</div>
                <div class="stats-label">Vendas do Mês</div>
                <div class="text-success mt-2">
                    <i class="fas fa-arrow-up me-1"></i>+22.8%
                </div>
            </div>
        </div>
    </div>
    
    <!-- Gráficos e Tabelas -->
    <div class="row">
        <div class="col-md-8">
            <div class="content-card">
                <h5 class="mb-4">📈 Vendas dos Últimos 30 Dias</h5>
                <div style="height: 300px; background: #f8f9fa; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                    <div class="text-center">
                        <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Gráfico de vendas será carregado aqui</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="content-card">
                <h5 class="mb-4">🎯 Metas do Mês</h5>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Vendas</span>
                        <span class="text-success">78%</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-success" style="width: 78%"></div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Leads</span>
                        <span class="text-warning">65%</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-warning" style="width: 65%"></div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Clientes</span>
                        <span class="text-info">92%</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-info" style="width: 92%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Atividades Recentes -->
    <div class="content-card">
        <h5 class="mb-4">📋 Atividades Recentes</h5>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Atividade</th>
                        <th>Usuário</th>
                        <th>Data</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <i class="fas fa-user-plus text-success me-2"></i>
                            Novo cliente cadastrado
                        </td>
                        <td>João Silva</td>
                        <td>Há 2 minutos</td>
                        <td><span class="badge bg-success">Concluído</span></td>
                    </tr>
                    <tr>
                        <td>
                            <i class="fas fa-handshake text-primary me-2"></i>
                            Venda finalizada - R$ 15.500
                        </td>
                        <td>Maria Santos</td>
                        <td>Há 15 minutos</td>
                        <td><span class="badge bg-success">Concluído</span></td>
                    </tr>
                    <tr>
                        <td>
                            <i class="fas fa-tasks text-warning me-2"></i>
                            Tarefa atribuída
                        </td>
                        <td>Pedro Costa</td>
                        <td>Há 1 hora</td>
                        <td><span class="badge bg-warning">Pendente</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    '''
    
    page_content = template.replace('{{PAGE_TITLE}}', 'Dashboard')
    page_content = page_content.replace('{{PAGE_DESCRIPTION}}', 'Visão geral do seu negócio')
    page_content = page_content.replace('{{HEADER_ICON}}', '🏠')
    page_content = page_content.replace('{{DASHBOARD_ACTIVE}}', 'active')
    page_content = page_content.replace('{{TASKS_ACTIVE}}', '')
    page_content = page_content.replace('{{CUSTOMERS_ACTIVE}}', '')
    page_content = page_content.replace('{{LEADS_ACTIVE}}', '')
    page_content = page_content.replace('{{PROJECTS_ACTIVE}}', '')
    page_content = page_content.replace('{{REPORTS_ACTIVE}}', '')
    page_content = page_content.replace('{{ANALYTICS_ACTIVE}}', '')
    page_content = page_content.replace('{{SETTINGS_ACTIVE}}', '')
    page_content = page_content.replace('{{HEADER_BUTTONS}}', '''
        <div class="d-flex gap-2">
            <button class="btn btn-light" onclick="refreshDashboard()">
                <i class="fas fa-sync me-2"></i>Atualizar
            </button>
            <button class="btn btn-light" onclick="exportDashboard()">
                <i class="fas fa-download me-2"></i>Exportar
            </button>
        </div>
    ''')
    page_content = page_content.replace('{{CONTENT}}', stats_content)
    page_content = page_content.replace('{{CUSTOM_CSS}}', '')
    page_content = page_content.replace('{{CUSTOM_SCRIPTS}}', '''
        <script>
            function refreshDashboard() {
                showNotification('Dashboard atualizado com sucesso!', 'success');
            }
            
            function exportDashboard() {
                showNotification('Relatório exportado com sucesso!', 'info');
            }
        </script>
    ''')
    
    return page_content

def standardize_all_pages():
    """Padroniza todas as páginas principais"""
    
    wamp_path = r"C:\wamp64\www\duralux\duralux-admin"
    
    print("🎨 Padronizando todas as páginas com o novo layout...")
    
    # Criar dashboard moderno
    dashboard_content = create_dashboard_page()
    
    with open(os.path.join(wamp_path, "index.html"), 'w', encoding='utf-8') as f:
        f.write(dashboard_content)
    
    print("✅ Dashboard modernizado")
    
    # Lista de páginas para padronizar
    pages = {
        'customers.html': {
            'title': 'Clientes',
            'description': 'Gerencie sua base de clientes',
            'icon': '👥',
            'active': 'CUSTOMERS_ACTIVE'
        },
        'leads.html': {
            'title': 'Leads',
            'description': 'Gerencie suas oportunidades de venda',
            'icon': '🎯',
            'active': 'LEADS_ACTIVE'
        },
        'projects.html': {
            'title': 'Projetos',
            'description': 'Acompanhe seus projetos',
            'icon': '📋',
            'active': 'PROJECTS_ACTIVE'
        },
        'reports-sales.html': {
            'title': 'Relatórios de Vendas',
            'description': 'Análise detalhada de vendas',
            'icon': '📊',
            'active': 'REPORTS_ACTIVE'
        }
    }
    
    template = create_modern_template()
    
    for filename, config in pages.items():
        # Resetar todas as ativações
        page_content = template
        for key in ['DASHBOARD_ACTIVE', 'TASKS_ACTIVE', 'CUSTOMERS_ACTIVE', 'LEADS_ACTIVE', 'PROJECTS_ACTIVE', 'REPORTS_ACTIVE', 'ANALYTICS_ACTIVE', 'SETTINGS_ACTIVE']:
            page_content = page_content.replace('{{' + key + '}}', 'active' if key == config['active'] else '')
        
        # Configurar página específica
        page_content = page_content.replace('{{PAGE_TITLE}}', config['title'])
        page_content = page_content.replace('{{PAGE_DESCRIPTION}}', config['description'])
        page_content = page_content.replace('{{HEADER_ICON}}', config['icon'])
        page_content = page_content.replace('{{HEADER_BUTTONS}}', '''
            <button class="btn btn-light btn-lg" onclick="addNew()">
                <i class="fas fa-plus me-2"></i>Adicionar
            </button>
        ''')
        
        # Conteúdo genérico
        generic_content = f'''
        <!-- Filtros -->
        <div class="row mb-4">
            <div class="col-md-6">
                <input type="text" class="form-control search-box" placeholder="🔍 Buscar {config['title'].lower()}...">
            </div>
            <div class="col-md-3">
                <select class="form-select">
                    <option>Todos os Status</option>
                    <option>Ativo</option>
                    <option>Inativo</option>
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-outline-primary w-100">
                    <i class="fas fa-filter me-2"></i>Filtros Avançados
                </button>
            </div>
        </div>
        
        <!-- Conteúdo Principal -->
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0">Lista de {config['title']}</h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-download me-1"></i>Exportar
                    </button>
                    <button class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-sync me-1"></i>Atualizar
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Status</th>
                            <th>Data</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>#001</td>
                            <td>Exemplo de registro</td>
                            <td><span class="badge bg-success">Ativo</span></td>
                            <td>04/11/2025</td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary me-1">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-warning me-1">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        '''
        
        page_content = page_content.replace('{{CONTENT}}', generic_content)
        page_content = page_content.replace('{{CUSTOM_CSS}}', '')
        page_content = page_content.replace('{{CUSTOM_SCRIPTS}}', '''
            <script>
                function addNew() {
                    showNotification('Função de adicionar em desenvolvimento', 'info');
                }
            </script>
        ''')
        
        # Salvar arquivo
        file_path = os.path.join(wamp_path, filename)
        with open(file_path, 'w', encoding='utf-8') as f:
            f.write(page_content)
        
        print(f"✅ {filename} padronizada")
    
    print(f"\n🎉 {len(pages) + 1} páginas padronizadas com sucesso!")
    return len(pages) + 1

if __name__ == "__main__":
    print("🚀 DURALUX - Padronização de Layout")
    print("=" * 50)
    
    total_pages = standardize_all_pages()
    
    print(f"\n✅ Padronização concluída!")
    print(f"📊 Total de páginas: {total_pages}")
    print("\n🌐 Páginas atualizadas:")
    print("   • Dashboard: http://localhost/duralux/duralux-admin/index.html")
    print("   • Clientes: http://localhost/duralux/duralux-admin/customers.html")
    print("   • Leads: http://localhost/duralux/duralux-admin/leads.html")
    print("   • Projetos: http://localhost/duralux/duralux-admin/projects.html")
    print("   • Relatórios: http://localhost/duralux/duralux-admin/reports-sales.html")
    print("   • Tarefas: http://localhost/duralux/duralux-admin/apps-tasks.html")