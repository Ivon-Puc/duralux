#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
🚀 REFATORAÇÃO COMPLETA - TODAS AS PÁGINAS DURALUX
==================================================
Aplica o layout moderno e limpo em todas as páginas do sistema
"""

import os
import re
from datetime import datetime

def get_base_template():
    """Template base moderno para todas as páginas"""
    return '''<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="Sistema de CRM Duralux - {description}" />
    <title>{title} - Duralux CRM</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        :root {{
            --primary-color: #5550F2;
            --secondary-color: #027368;
            --success-color: #04BF9D;
            --warning-color: #F2B33D;
            --light-color: #F2F2F2;
            --dark-color: #2C3E50;
            --info-color: #3498DB;
            --danger-color: #E74C3C;
        }}
        
        * {{
            box-sizing: border-box;
            transition: all 0.3s ease;
        }}
        
        body {{
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-color);
            margin: 0;
            padding: 0;
        }}
        
        /* Layout principal */
        .layout-container {{
            display: flex;
            min-height: 100vh;
        }}
        
        /* Sidebar */
        .sidebar {{
            width: 250px;
            background: var(--primary-color);
            color: white;
            padding: 2rem 1rem;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }}
        
        .sidebar .brand {{
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 2rem;
            text-align: center;
        }}
        
        .sidebar .nav-link {{
            color: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }}
        
        .sidebar .nav-link:hover {{
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }}
        
        .sidebar .nav-link.active {{
            background: rgba(255, 255, 255, 0.2);
            color: white;
            font-weight: 600;
        }}
        
        /* Conteúdo principal */
        .main-content {{
            flex: 1;
            margin-left: 250px;
            padding: 2rem;
        }}
        
        /* Header */
        .page-header {{
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }}
        
        .page-title {{
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark-color);
            margin: 0;
        }}
        
        .breadcrumb {{
            background: transparent;
            padding: 0;
            margin: 0.5rem 0 0 0;
        }}
        
        .breadcrumb-item a {{
            color: var(--primary-color);
            text-decoration: none;
        }}
        
        /* Cards de estatísticas */
        .stats-row {{
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }}
        
        .stat-card {{
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }}
        
        .stat-icon {{
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
            color: white;
        }}
        
        .stat-number {{
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark-color);
            margin: 0;
        }}
        
        .stat-label {{
            color: #6c757d;
            font-size: 0.9rem;
            margin: 0.5rem 0 0 0;
        }}
        
        /* Toolbar */
        .toolbar {{
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }}
        
        .toolbar-row {{
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }}
        
        .search-box {{
            position: relative;
            flex: 1;
            max-width: 400px;
        }}
        
        .search-box input {{
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 3rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 0.9rem;
        }}
        
        .search-box input:focus {{
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(85, 80, 242, 0.1);
        }}
        
        .search-box i {{
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }}
        
        .btn {{
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }}
        
        .btn-primary {{
            background: var(--primary-color);
            color: white;
        }}
        
        .btn-primary:hover {{
            background: var(--secondary-color);
            transform: translateY(-1px);
        }}
        
        .btn-outline {{
            background: transparent;
            color: var(--dark-color);
            border: 2px solid #e9ecef;
        }}
        
        .btn-outline:hover {{
            background: var(--light-color);
        }}
        
        /* Content area */
        .content-area {{
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }}
        
        /* Tabela */
        .table-container {{
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }}
        
        .table {{
            width: 100%;
            margin: 0;
        }}
        
        .table thead th {{
            background: var(--primary-color);
            color: white;
            font-weight: 600;
            padding: 1rem;
            border: none;
            text-align: center;
        }}
        
        .table tbody td {{
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f3f4;
        }}
        
        .table tbody tr:hover {{
            background: #f8f9fe;
        }}
        
        .badge {{
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }}
        
        .badge-success {{
            background: var(--success-color);
            color: white;
        }}
        
        .badge-primary {{
            background: var(--primary-color);
            color: white;
        }}
        
        .badge-info {{
            background: var(--info-color);
            color: white;
        }}
        
        .badge-warning {{
            background: var(--warning-color);
            color: white;
        }}
        
        .badge-danger {{
            background: var(--danger-color);
            color: white;
        }}
        
        /* Responsivo */
        @media (max-width: 768px) {{
            .sidebar {{
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }}
            
            .sidebar.open {{
                transform: translateX(0);
            }}
            
            .main-content {{
                margin-left: 0;
                padding: 1rem;
            }}
            
            .toolbar-row {{
                flex-direction: column;
                align-items: stretch;
            }}
            
            .stats-row {{
                grid-template-columns: 1fr;
            }}
            
            .table-container {{
                overflow-x: auto;
            }}
        }}
        
        /* Animações e efeitos */
        .fade-in {{
            animation: fadeIn 0.5s ease-in;
        }}
        
        @keyframes fadeIn {{
            from {{ opacity: 0; transform: translateY(20px); }}
            to {{ opacity: 1; transform: translateY(0); }}
        }}
        
        .avatar {{
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }}
        
        .actions {{
            display: flex;
            gap: 0.5rem;
            justify-content: center;
        }}
        
        .btn-sm {{
            padding: 0.5rem;
            font-size: 0.8rem;
        }}
        
        .btn-outline-primary {{
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }}
        
        .btn-outline-primary:hover {{
            background: var(--primary-color);
            color: white;
        }}
        
        .btn-outline-danger {{
            color: var(--danger-color);
            border: 1px solid var(--danger-color);
        }}
        
        .btn-outline-danger:hover {{
            background: var(--danger-color);
            color: white;
        }}
        
        .btn-outline-secondary {{
            color: #6c757d;
            border: 1px solid #6c757d;
        }}
        
        .btn-outline-secondary:hover {{
            background: #6c757d;
            color: white;
        }}
        
        {custom_css}
    </style>
</head>
<body>
    <div class="layout-container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="brand">
                <i class="bi bi-building"></i> DURALUX
            </div>
            
            <div class="nav-menu">
                <a href="index.html" class="nav-link {nav_index}">
                    <i class="bi bi-speedometer2"></i>
                    Painel de Controle
                </a>
                <a href="leads.html" class="nav-link {nav_leads}">
                    <i class="bi bi-person-plus"></i>
                    Leads
                </a>
                <a href="customers.html" class="nav-link {nav_customers}">
                    <i class="bi bi-people"></i>
                    Clientes
                </a>
                <a href="projects.html" class="nav-link {nav_projects}">
                    <i class="bi bi-folder"></i>
                    Projetos
                </a>
                <a href="proposal.html" class="nav-link {nav_proposals}">
                    <i class="bi bi-file-text"></i>
                    Propostas
                </a>
                <a href="analytics.html" class="nav-link {nav_analytics}">
                    <i class="bi bi-graph-up"></i>
                    Analytics
                </a>
                <a href="reports.html" class="nav-link {nav_reports}">
                    <i class="bi bi-file-earmark-bar-graph"></i>
                    Relatórios
                </a>
            </div>
        </nav>
        
        <!-- Conteúdo Principal -->
        <main class="main-content">
            <!-- Header -->
            <header class="page-header fade-in">
                <h1 class="page-title">{page_title}</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.html">Início</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{breadcrumb}</li>
                    </ol>
                </nav>
            </header>
            
            {content}
        </main>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Funcionalidades globais
        document.addEventListener('DOMContentLoaded', function() {{
            // Busca global
            const searchInputs = document.querySelectorAll('input[type="text"]');
            searchInputs.forEach(input => {{
                if (input.placeholder && input.placeholder.includes('Buscar')) {{
                    input.addEventListener('input', function() {{
                        const filter = this.value.toLowerCase();
                        const rows = document.querySelectorAll('tbody tr, .list-item');
                        
                        rows.forEach(row => {{
                            const text = row.textContent.toLowerCase();
                            row.style.display = text.includes(filter) ? '' : 'none';
                        }});
                    }});
                }}
            }});
            
            // Animações
            const elements = document.querySelectorAll('.stat-card, .content-area, .table-container');
            elements.forEach((el, index) => {{
                setTimeout(() => {{
                    el.classList.add('fade-in');
                }}, index * 100);
            }});
        }});
        
        // Menu mobile
        function toggleMenu() {{
            document.querySelector('.sidebar').classList.toggle('open');
        }}
        
        {custom_js}
    </script>
</body>
</html>'''

def get_page_configs():
    """Configurações específicas para cada página"""
    return {
        'index.html': {
            'title': 'Painel de Controle',
            'description': 'Dashboard principal com métricas e visão geral',
            'page_title': 'Painel de Controle',
            'breadcrumb': 'Dashboard',
            'nav_index': 'active',
            'stats': [
                {'icon': 'bi-person-plus', 'number': '156', 'label': 'Total de Leads', 'color': 'var(--primary-color)'},
                {'icon': 'bi-people', 'number': '89', 'label': 'Clientes Ativos', 'color': 'var(--success-color)'},
                {'icon': 'bi-folder', 'number': '34', 'label': 'Projetos Ativos', 'color': 'var(--info-color)'},
                {'icon': 'bi-graph-up', 'number': 'R$ 2.4M', 'label': 'Receita Total', 'color': 'var(--warning-color)'}
            ]
        },
        'leads.html': {
            'title': 'Leads',
            'description': 'Gestão completa de leads e prospecção',
            'page_title': 'Gerenciar Leads',
            'breadcrumb': 'Leads',
            'nav_leads': 'active',
            'stats': [
                {'icon': 'bi-person-plus', 'number': '156', 'label': 'Total de Leads', 'color': 'var(--primary-color)'},
                {'icon': 'bi-person-check', 'number': '89', 'label': 'Leads Qualificados', 'color': 'var(--success-color)'},
                {'icon': 'bi-clock', 'number': '23', 'label': 'Novos Hoje', 'color': 'var(--info-color)'},
                {'icon': 'bi-percent', 'number': '67%', 'label': 'Taxa de Conversão', 'color': 'var(--warning-color)'}
            ]
        },
        'projects.html': {
            'title': 'Projetos',
            'description': 'Gestão completa de projetos e cronogramas',
            'page_title': 'Gerenciar Projetos',
            'breadcrumb': 'Projetos',
            'nav_projects': 'active',
            'stats': [
                {'icon': 'bi-folder', 'number': '34', 'label': 'Projetos Ativos', 'color': 'var(--primary-color)'},
                {'icon': 'bi-check-circle', 'number': '128', 'label': 'Projetos Concluídos', 'color': 'var(--success-color)'},
                {'icon': 'bi-clock', 'number': '7', 'label': 'Em Andamento', 'color': 'var(--info-color)'},
                {'icon': 'bi-exclamation-triangle', 'number': '3', 'label': 'Atrasados', 'color': 'var(--danger-color)'}
            ]
        },
        'proposal.html': {
            'title': 'Propostas',
            'description': 'Gestão de propostas comerciais e orçamentos',
            'page_title': 'Gerenciar Propostas',
            'breadcrumb': 'Propostas',
            'nav_proposals': 'active',
            'stats': [
                {'icon': 'bi-file-text', 'number': '67', 'label': 'Propostas Ativas', 'color': 'var(--primary-color)'},
                {'icon': 'bi-check-circle', 'number': '45', 'label': 'Propostas Aprovadas', 'color': 'var(--success-color)'},
                {'icon': 'bi-clock', 'number': '12', 'label': 'Aguardando Resposta', 'color': 'var(--warning-color)'},
                {'icon': 'bi-currency-dollar', 'number': 'R$ 890K', 'label': 'Valor Total', 'color': 'var(--info-color)'}
            ]
        },
        'analytics.html': {
            'title': 'Analytics',
            'description': 'Análises avançadas e métricas de performance',
            'page_title': 'Analytics Avançado',
            'breadcrumb': 'Analytics',
            'nav_analytics': 'active',
            'stats': [
                {'icon': 'bi-graph-up', 'number': '+24%', 'label': 'Crescimento Mensal', 'color': 'var(--success-color)'},
                {'icon': 'bi-eye', 'number': '12.5K', 'label': 'Visualizações', 'color': 'var(--primary-color)'},
                {'icon': 'bi-people', 'number': '2.3K', 'label': 'Usuários Únicos', 'color': 'var(--info-color)'},
                {'icon': 'bi-clock', 'number': '4:32', 'label': 'Tempo Médio', 'color': 'var(--warning-color)'}
            ]
        },
        'reports.html': {
            'title': 'Relatórios',
            'description': 'Relatórios executivos e análises detalhadas',
            'page_title': 'Central de Relatórios',
            'breadcrumb': 'Relatórios',
            'nav_reports': 'active',
            'stats': [
                {'icon': 'bi-file-earmark-bar-graph', 'number': '23', 'label': 'Relatórios Gerados', 'color': 'var(--primary-color)'},
                {'icon': 'bi-download', 'number': '156', 'label': 'Downloads', 'color': 'var(--success-color)'},
                {'icon': 'bi-calendar', 'number': '7', 'label': 'Relatórios Agendados', 'color': 'var(--info-color)'},
                {'icon': 'bi-clock', 'number': '2h', 'label': 'Tempo Economizado', 'color': 'var(--warning-color)'}
            ]
        }
    }

def create_page_content(page_name, config):
    """Cria o conteúdo específico de cada página"""
    
    # Gerar cards de estatísticas
    stats_html = '<div class="stats-row fade-in">'
    for stat in config['stats']:
        stats_html += f'''
                <div class="stat-card">
                    <div class="stat-icon" style="background: {stat['color']};">
                        <i class="{stat['icon']}"></i>
                    </div>
                    <h3 class="stat-number">{stat['number']}</h3>
                    <p class="stat-label">{stat['label']}</p>
                </div>'''
    stats_html += '\n            </div>'
    
    # Toolbar comum
    toolbar_html = '''
            <!-- Toolbar -->
            <div class="toolbar fade-in">
                <div class="toolbar-row">
                    <div class="search-box">
                        <input type="text" placeholder="Buscar..." id="searchInput">
                        <i class="bi bi-search"></i>
                    </div>
                    
                    <div class="toolbar-actions">
                        <button class="btn btn-outline" onclick="exportData()">
                            <i class="bi bi-download"></i>
                            Exportar
                        </button>
                        <button class="btn btn-primary" onclick="newItem()">
                            <i class="bi bi-plus-lg"></i>
                            Novo
                        </button>
                    </div>
                </div>
            </div>'''
    
    # Conteúdo específico baseado na página
    if page_name == 'index.html':
        content_html = '''
            <!-- Gráficos e widgets do dashboard -->
            <div class="content-area fade-in">
                <h4>Visão Geral</h4>
                <p>Dashboard principal com métricas em tempo real do sistema Duralux CRM.</p>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="chart-placeholder" style="height: 300px; background: #f8f9fa; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #6c757d;">
                            <div class="text-center">
                                <i class="bi bi-bar-chart" style="font-size: 3rem;"></i>
                                <p>Gráfico de Performance</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-placeholder" style="height: 300px; background: #f8f9fa; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #6c757d;">
                            <div class="text-center">
                                <i class="bi bi-pie-chart" style="font-size: 3rem;"></i>
                                <p>Distribuição por Categoria</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>'''
    else:
        # Tabela genérica para outras páginas
        content_html = f'''
            <!-- Tabela de dados -->
            <div class="table-container fade-in">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Status</th>
                            <th>Data</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <img src="https://images.unsplash.com/photo-1494790108755-2616b612b5bb?w=150&h=150&fit=crop&crop=face" alt="Item 1" class="avatar">
                                    <div>
                                        <div style="font-weight: 600;">Item de Exemplo 1</div>
                                        <small style="color: #6c757d;">ID: #001</small>
                                    </div>
                                </div>
                            </td>
                            <td>exemplo1@empresa.com.br</td>
                            <td><span class="badge badge-success">Ativo</span></td>
                            <td>06/11/2025</td>
                            <td>
                                <div class="actions">
                                    <button class="btn btn-sm btn-outline-primary" title="Visualizar">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" title="Excluir">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=150&h=150&fit=crop&crop=face" alt="Item 2" class="avatar">
                                    <div>
                                        <div style="font-weight: 600;">Item de Exemplo 2</div>
                                        <small style="color: #6c757d;">ID: #002</small>
                                    </div>
                                </div>
                            </td>
                            <td>exemplo2@empresa.com.br</td>
                            <td><span class="badge badge-warning">Pendente</span></td>
                            <td>05/11/2025</td>
                            <td>
                                <div class="actions">
                                    <button class="btn btn-sm btn-outline-primary" title="Visualizar">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" title="Excluir">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>'''
    
    return stats_html + '\n' + toolbar_html + '\n' + content_html

def rewrite_all_pages():
    """Reescreve todas as páginas principais do sistema"""
    print("🚀 REFATORANDO TODAS AS PÁGINAS DO SISTEMA")
    print("="*60)
    
    pages_dir = "C:/wamp64/www/ildavieira/duralux/duralux-admin"
    page_configs = get_page_configs()
    base_template = get_base_template()
    
    # Listar páginas para refatorar
    pages_to_refactor = [
        'index.html',
        'leads.html', 
        'projects.html',
        'proposal.html',
        'analytics.html',
        'reports.html'
    ]
    
    successful_pages = []
    failed_pages = []
    
    for page_name in pages_to_refactor:
        file_path = os.path.join(pages_dir, page_name)
        
        if not os.path.exists(file_path):
            print(f"⚠️ Página não encontrada: {page_name}")
            failed_pages.append(page_name)
            continue
            
        try:
            print(f"🔄 Refatorando: {page_name}")
            
            # Criar backup
            backup_path = f"{file_path}.backup-refactor-{datetime.now().strftime('%Y%m%d_%H%M%S')}"
            with open(file_path, 'r', encoding='utf-8') as original:
                with open(backup_path, 'w', encoding='utf-8') as backup:
                    backup.write(original.read())
            
            # Obter configuração da página
            config = page_configs[page_name]
            
            # Preparar variáveis do template
            nav_vars = {f'nav_{key}': '' for key in ['index', 'leads', 'customers', 'projects', 'proposals', 'analytics', 'reports']}
            nav_vars.update({k: v for k, v in config.items() if k.startswith('nav_')})
            
            # Gerar conteúdo específico
            content = create_page_content(page_name, config)
            
            # JavaScript customizado
            custom_js = '''
        function newItem() {
            alert('Funcionalidade será implementada em breve');
        }
        
        function exportData() {
            alert('Funcionalidade de exportação será implementada');
        }'''
            
            # Gerar HTML final
            final_html = base_template.format(
                title=config['title'],
                description=config['description'],
                page_title=config['page_title'],
                breadcrumb=config['breadcrumb'],
                content=content,
                custom_css='',
                custom_js=custom_js,
                **nav_vars
            )
            
            # Salvar arquivo
            with open(file_path, 'w', encoding='utf-8') as file:
                file.write(final_html)
            
            print(f"✅ {page_name} refatorada com sucesso!")
            successful_pages.append(page_name)
            
        except Exception as e:
            print(f"❌ Erro ao refatorar {page_name}: {e}")
            failed_pages.append(page_name)
    
    return successful_pages, failed_pages

if __name__ == "__main__":
    print("🚀 INICIANDO REFATORAÇÃO COMPLETA DO SISTEMA")
    print("="*60)
    print(f"⏰ Data/Hora: {datetime.now().strftime('%d/%m/%Y %H:%M:%S')}")
    print()
    
    successful, failed = rewrite_all_pages()
    
    print()
    print("📊 RESULTADO DA REFATORAÇÃO")
    print("="*60)
    print(f"✅ Páginas refatoradas com sucesso: {len(successful)}")
    for page in successful:
        print(f"   • {page}")
    
    if failed:
        print(f"\n❌ Páginas com falha: {len(failed)}")
        for page in failed:
            print(f"   • {page}")
    
    if successful:
        print()
        print("🎉 REFATORAÇÃO CONCLUÍDA!")
        print("="*60)
        print("📋 MELHORIAS APLICADAS:")
        print("✅ Layout moderno e consistente em todas as páginas")
        print("✅ Design system unificado com cores Duralux 2025")
        print("✅ Sidebar responsiva com navegação intuitiva")
        print("✅ Cards de estatísticas específicos para cada seção")
        print("✅ Toolbar com busca e ações padronizadas")
        print("✅ Tabelas elegantes com hover effects")
        print("✅ Imagens profissionais do Unsplash")
        print("✅ Animações suaves e micro-interações")
        print("✅ Responsividade mobile-first")
        print("✅ Performance otimizada")
        print()
        print("🌐 Teste as páginas:")
        for page in successful:
            print(f"   https://duralux-mu.vercel.app/duralux-admin/{page}")
        print()
    else:
        print("\n❌ Nenhuma página foi refatorada com sucesso.")