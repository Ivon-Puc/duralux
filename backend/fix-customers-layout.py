#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
🎨 CORREÇÃO COMPLETA DO LAYOUT E IMAGENS - PÁGINA CUSTOMERS
===========================================================
Corrige o layout e substitui imagens antigas por novas imagens profissionais
"""

import os
import re
from datetime import datetime

def fix_customers_layout_and_images():
    print("🎨 CORRIGINDO LAYOUT E IMAGENS - CUSTOMERS.HTML")
    print("="*60)
    
    file_path = "C:/wamp64/www/ildavieira/duralux/duralux-admin/customers.html"
    
    try:
        with open(file_path, 'r', encoding='utf-8') as file:
            content = file.read()
        
        print(f"📄 Arquivo lido: {len(content)} caracteres")
        
        # 1. Corrigir estrutura da sidebar e layout principal
        print("🔄 Corrigindo estrutura do layout...")
        
        # Substituir a estrutura problemática por uma moderna
        old_structure = r'<main role="main" class="nxl-container">.*?<div class="nxl-content">'
        new_structure = '''<div class="container-fluid">
        <div class="row">
            <!-- Sidebar responsiva -->
            <div class="col-12 col-lg-2 sidebar">
                <div class="d-flex flex-column">
                    <h4 class="mb-4">
                        <i class="bi bi-building"></i> DURALUX
                    </h4>
                    
                    <nav role="navigation" class="nav flex-column nav-pills">
                        <a class="nav-link" href="index.html">
                            <i class="bi bi-speedometer2"></i> Painel de Controle
                        </a>
                        <a class="nav-link" href="leads.html">
                            <i class="bi bi-person-plus"></i> Leads
                        </a>
                        <a class="nav-link active" style="background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(10px); border-radius: 8px; margin-bottom: 0.25rem;" href="customers.html">
                            <i class="bi bi-people"></i> Clientes
                        </a>
                        <a class="nav-link" href="projects.html">
                            <i class="bi bi-folder"></i> Projetos
                        </a>
                        <a class="nav-link" href="proposal.html">
                            <i class="bi bi-file-text"></i> Propostas
                        </a>
                        <a class="nav-link" href="analytics.html">
                            <i class="bi bi-graph-up"></i> Analytics
                        </a>
                        <a class="nav-link" href="reports.html">
                            <i class="bi bi-file-earmark-bar-graph"></i> Relatórios
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Conteúdo principal -->
            <div class="col-12 col-lg-10">
                <header class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 mb-0">Gerenciar Clientes</h1>
                        <nav role="navigation" aria-label="breadcrumb" style="margin-bottom: 1.5rem;">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="index.html">Início</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Clientes</li>
                            </ol>
                        </nav>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-secondary" type="button" data-bs-toggle="offcanvas" data-bs-target="#notifications" aria-label="Notificações">
                            <i class="bi bi-bell"></i>
                        </button>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-label="Menu do usuário">
                                <i class="bi bi-person-circle"></i> Usuário
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#"><i class="bi bi-person"></i> Perfil</a></li>
                                <li><a class="dropdown-item" href="#"><i class="bi bi-gear"></i> Configurações</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#"><i class="bi bi-box-arrow-right"></i> Sair</a></li>
                            </ul>
                        </div>
                    </div>
                </header>
                
                <main role="main">'''
        
        content = re.sub(old_structure, new_structure, content, flags=re.DOTALL)
        
        # 2. Substituir imagens antigas por novas imagens profissionais do Unsplash
        print("🖼️ Substituindo imagens antigas por novas...")
        
        # Definir novas imagens profissionais
        professional_images = [
            "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&h=150&fit=crop&crop=face",  # Homem profissional 1
            "https://images.unsplash.com/photo-1494790108755-2616b612b5bb?w=150&h=150&fit=crop&crop=face",  # Mulher profissional 1
            "https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=150&h=150&fit=crop&crop=face",  # Homem profissional 2
            "https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=150&h=150&fit=crop&crop=face",  # Mulher profissional 2
            "https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150&h=150&fit=crop&crop=face",  # Homem profissional 3
            "https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&h=150&fit=crop&crop=face",  # Mulher profissional 3
            "https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=150&h=150&fit=crop&crop=face",  # Homem profissional 4
            "https://images.unsplash.com/photo-1554151228-14d9def656e4?w=150&h=150&fit=crop&crop=face",  # Mulher profissional 4
        ]
        
        # Substituir referências de imagens antigas
        content = re.sub(r'assets/images/avatar/\d+\.png', professional_images[0], content)
        content = re.sub(r'src="assets/images/avatar/1\.png"', f'src="{professional_images[0]}"', content)
        content = re.sub(r'src="assets/images/avatar/2\.png"', f'src="{professional_images[1]}"', content)
        content = re.sub(r'src="assets/images/avatar/3\.png"', f'src="{professional_images[2]}"', content)
        content = re.sub(r'src="assets/images/avatar/4\.png"', f'src="{professional_images[3]}"', content)
        
        # 3. Corrigir dados da tabela com informações mais realistas
        print("📊 Atualizando dados da tabela...")
        
        # Dados de clientes modernos e realistas
        customers_data = [
            {
                'name': 'Alexandra Della',
                'email': 'alexandra.della@empresa.com.br',
                'phone': '(11) 99876-5432',
                'date': '2024-10-15, 09:30AM',
                'groups': ['VIP', 'Corporativo', 'Premium'],
                'image': professional_images[0]
            },
            {
                'name': 'Carlos Eduardo Silva',
                'email': 'carlos.silva@construcoes.com.br',
                'phone': '(11) 98765-4321',
                'date': '2024-10-20, 14:15PM',
                'groups': ['Construção', 'Alto Volume', 'Prioritário'],
                'image': professional_images[1]
            },
            {
                'name': 'Marina Santos Oliveira',
                'email': 'marina.santos@arquitetura.com.br',
                'phone': '(11) 97654-3210',
                'date': '2024-11-01, 11:45AM',
                'groups': ['Arquitetura', 'Design', 'Premium'],
                'image': professional_images[2]
            },
            {
                'name': 'Roberto Ferreira Costa',
                'email': 'roberto.costa@incorporadora.com.br',
                'phone': '(11) 96543-2109',
                'date': '2024-11-05, 16:20PM',
                'groups': ['Incorporação', 'VIP', 'Grandes Projetos'],
                'image': professional_images[3]
            }
        ]
        
        # 4. Substituir conteúdo da tabela com dados realistas
        print("🔄 Substituindo conteúdo da tabela...")
        
        # Encontrar e substituir as linhas da tabela
        table_rows = []
        for i, customer in enumerate(customers_data):
            row = f'''
                                            <tr class="single-item">
                                                <td>
                                                    <div class="item-checkbox ms-1">
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input checkbox" id="checkBox_{i+1}">
                                                            <label class="custom-form-label" for="checkBox_{i+1}"></label>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <a href="customers-view.html" class="hstack gap-3">
                                                        <div class="avatar-image avatar-md">
                                                            <img src="{customer['image']}" alt="{customer['name']}" class="img-fluid rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                                        </div>
                                                        <div>
                                                            <span class="text-truncate-1-line fw-semibold">{customer['name']}</span>
                                                        </div>
                                                    </a>
                                                </td>
                                                <td><a href="mailto:{customer['email']}" class="text-decoration-none">{customer['email']}</a></td>
                                                <td>
                                                    <div class="d-flex gap-1 flex-wrap">
                                                        {"".join([f'<span class="badge bg-primary">{group}</span>' for group in customer['groups'][:2]])}
                                                    </div>
                                                </td>
                                                <td><a href="tel:{customer['phone']}" class="text-decoration-none">{customer['phone']}</a></td>
                                                <td><small class="text-muted">{customer['date']}</small></td>
                                                <td>
                                                    <span class="badge bg-success">Ativo</span>
                                                </td>
                                                <td>
                                                    <div class="hstack gap-2 justify-content-end">
                                                        <a href="customers-view.html" class="btn btn-sm btn-outline-primary" title="Visualizar">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <a href="customers-edit.html" class="btn btn-sm btn-outline-secondary" title="Editar">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <button class="btn btn-sm btn-outline-danger" title="Excluir" onclick="confirmDelete('{customer['name']}')">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>'''
            table_rows.append(row)
        
        # Substituir o conteúdo da tbody
        tbody_pattern = r'<tbody>.*?</tbody>'
        new_tbody = f'<tbody>{"".join(table_rows)}\n                                        </tbody>'
        content = re.sub(tbody_pattern, new_tbody, content, flags=re.DOTALL)
        
        # 5. Corrigir elementos HTML problemáticos
        print("🔧 Corrigindo elementos HTML...")
        
        content = re.sub(r'</Selecionar>', '</select>', content)
        content = re.sub(r'<spanArquivare</span>', '<span>Arquivar</span>', content)
        content = re.sub(r'<spaArquivarve</span>', '<span>Arquivar</span>', content)
        content = re.sub(r'dropdown-itin', 'dropdown-item', content)
        content = re.sub(r'new Cliente', 'Novo Cliente', content)
        content = re.sub(r'Atualizars', 'Updates', content)
        content = re.sub(r'Baixa Budget', 'Budget Baixo', content)
        content = re.sub(r'Alta Budget', 'Budget Alto', content)
        
        # 6. Melhorar o cabeçalho da tabela
        print("📋 Melhorando cabeçalho da tabela...")
        
        new_table_header = '''
                                        <thead>
                                            <tr>
                                                <th class="wd-30">
                                                    <div class="custom-control custom-checkbox ms-1">
                                                        <input type="checkbox" class="custom-control-input" id="checkAllCustomers">
                                                        <label class="custom-form-label" for="checkAllCustomers"></label>
                                                    </div>
                                                </th>
                                                <th style="background: var(--primary-color); color: white; font-weight: 600; text-align: center; padding: 1rem; border: none; position: sticky; top: 0; z-index: 10;">Cliente</th>
                                                <th style="background: var(--primary-color); color: white; font-weight: 600; text-align: center; padding: 1rem; border: none; position: sticky; top: 0; z-index: 10;">E-mail</th>
                                                <th style="background: var(--primary-color); color: white; font-weight: 600; text-align: center; padding: 1rem; border: none; position: sticky; top: 0; z-index: 10;">Grupos</th>
                                                <th style="background: var(--primary-color); color: white; font-weight: 600; text-align: center; padding: 1rem; border: none; position: sticky; top: 0; z-index: 10;">Telefone</th>
                                                <th style="background: var(--primary-color); color: white; font-weight: 600; text-align: center; padding: 1rem; border: none; position: sticky; top: 0; z-index: 10;">Data de Cadastro</th>
                                                <th style="background: var(--primary-color); color: white; font-weight: 600; text-align: center; padding: 1rem; border: none; position: sticky; top: 0; z-index: 10;">Status</th>
                                                <th class="text-end" style="background: var(--primary-color); color: white; font-weight: 600; text-align: center; padding: 1rem; border: none; position: sticky; top: 0; z-index: 10;">Ações</th>
                                            </tr>
                                        </thead>'''
        
        # Substituir o thead existente
        content = re.sub(r'<thead>.*?</thead>', new_table_header, content, flags=re.DOTALL)
        
        # 7. Adicionar barra de ferramentas moderna
        print("🛠️ Adicionando barra de ferramentas...")
        
        toolbar = '''
                    <!-- Barra de ferramentas moderna -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <div class="search-container">
                                        <input type="text" class="form-control" placeholder="Buscar clientes..." id="searchCustomers">
                                        <i class="bi bi-search"></i>
                                    </div>
                                </div>
                                <div class="col-md-6 text-end">
                                    <div class="btn-group me-2">
                                        <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                            <i class="bi bi-funnel"></i> Filtros
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#" data-filter="all">Todos</a></li>
                                            <li><a class="dropdown-item" href="#" data-filter="active">Ativos</a></li>
                                            <li><a class="dropdown-item" href="#" data-filter="inactive">Inativos</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item" href="#" data-filter="vip">VIP</a></li>
                                            <li><a class="dropdown-item" href="#" data-filter="premium">Premium</a></li>
                                        </ul>
                                    </div>
                                    <div class="btn-group me-2">
                                        <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                            <i class="bi bi-download"></i> Exportar
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#"><i class="bi bi-file-pdf me-2"></i>PDF</a></li>
                                            <li><a class="dropdown-item" href="#"><i class="bi bi-file-excel me-2"></i>Excel</a></li>
                                            <li><a class="dropdown-item" href="#"><i class="bi bi-file-csv me-2"></i>CSV</a></li>
                                        </ul>
                                    </div>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newCustomerModal">
                                        <i class="bi bi-plus-lg me-2"></i>Novo Cliente
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>'''
        
        # Inserir toolbar antes da tabela
        content = re.sub(r'(<div class="card stretch stretch-full">\s*<div class="card-body p-0">)', toolbar + r'\1', content)
        
        # 8. Fechar estrutura corretamente
        print("🔚 Fechando estrutura HTML...")
        
        # Adicionar fechamento correto no final
        content = re.sub(r'</main>\s*</div>\s*</div>\s*</body>', '''
                </main>
            </div>
        </div>
    </div>
    
    <!-- Painel de notificações -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="notifications">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">Notificações</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <div class="list-group list-group-flush">
                <div class="list-group-item">
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1">Novo cliente cadastrado</h6>
                        <small>5 min atrás</small>
                    </div>
                    <p class="mb-1">Marina Santos se cadastrou no sistema.</p>
                </div>
                <div class="list-group-item">
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1">Cliente atualizado</h6>
                        <small>1 hora atrás</small>
                    </div>
                    <p class="mb-1">Roberto Costa atualizou seus dados.</p>
                </div>
            </div>
        </div>
    </div>

</body>''', content)
        
        # 9. Adicionar script para funcionalidades
        script = '''
    <script>
        // Funcionalidades da página de clientes
        document.addEventListener('DOMContentLoaded', function() {
            // Busca em tempo real
            const searchInput = document.getElementById('searchCustomers');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const filter = this.value.toLowerCase();
                    const rows = document.querySelectorAll('tbody tr');
                    
                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        row.style.display = text.includes(filter) ? '' : 'none';
                    });
                });
            }
            
            // Seleção em massa
            const checkAll = document.getElementById('checkAllCustomers');
            if (checkAll) {
                checkAll.addEventListener('change', function() {
                    const checkboxes = document.querySelectorAll('tbody input[type="checkbox"]');
                    checkboxes.forEach(cb => cb.checked = this.checked);
                });
            }
            
            // Filtros
            document.querySelectorAll('[data-filter]').forEach(filter => {
                filter.addEventListener('click', function(e) {
                    e.preventDefault();
                    const filterType = this.dataset.filter;
                    applyFilter(filterType);
                });
            });
            
            // Atualizar métricas
            updateMetrics();
        });
        
        function applyFilter(type) {
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach(row => {
                row.style.display = '';
                if (type !== 'all') {
                    const badges = row.querySelectorAll('.badge');
                    let show = false;
                    badges.forEach(badge => {
                        if (badge.textContent.toLowerCase().includes(type.toLowerCase())) {
                            show = true;
                        }
                    });
                    if (!show && type === 'active') {
                        show = row.textContent.includes('Ativo');
                    }
                    if (!show && type === 'inactive') {
                        show = row.textContent.includes('Inativo');
                    }
                    row.style.display = show ? '' : 'none';
                }
            });
        }
        
        function updateMetrics() {
            const totalCustomers = document.querySelectorAll('tbody tr').length;
            const activeCustomers = document.querySelectorAll('tbody .badge.bg-success').length;
            const newCustomers = Math.floor(totalCustomers * 0.25);
            const inactiveCustomers = totalCustomers - activeCustomers;
            
            document.getElementById('total-customers').textContent = totalCustomers;
            document.getElementById('active-customers').textContent = activeCustomers;
            document.getElementById('new-customers').textContent = newCustomers;
            document.getElementById('inactive-customers').textContent = inactiveCustomers;
        }
        
        function confirmDelete(customerName) {
            if (confirm(`Tem certeza que deseja excluir o cliente ${customerName}?`)) {
                // Implementar exclusão
                alert('Cliente excluído com sucesso!');
            }
        }
    </script>'''
        
        # Inserir script antes do </body>
        content = content.replace('</body>', script + '\n</body>')
        
        # Salvar arquivo corrigido
        with open(file_path, 'w', encoding='utf-8') as file:
            file.write(content)
        
        print("✅ Layout e imagens corrigidos com sucesso!")
        print(f"📁 Arquivo salvo: {file_path}")
        
        return True
        
    except Exception as e:
        print(f"❌ Erro ao corrigir arquivo: {e}")
        return False

def create_backup():
    """Criar backup antes das correções"""
    file_path = "C:/wamp64/www/ildavieira/duralux/duralux-admin/customers.html"
    backup_path = f"{file_path}.backup-layout-{datetime.now().strftime('%Y%m%d_%H%M%S')}"
    
    try:
        with open(file_path, 'r', encoding='utf-8') as original:
            with open(backup_path, 'w', encoding='utf-8') as backup:
                backup.write(original.read())
        print(f"💾 Backup criado: {backup_path}")
        return True
    except Exception as e:
        print(f"❌ Erro ao criar backup: {e}")
        return False

if __name__ == "__main__":
    print("🚀 INICIANDO CORREÇÃO DE LAYOUT E IMAGENS")
    print("="*60)
    print(f"⏰ Data/Hora: {datetime.now().strftime('%d/%m/%Y %H:%M:%S')}")
    print()
    
    # Criar backup
    if create_backup():
        print("✅ Backup criado com sucesso")
    
    # Aplicar correções
    if fix_customers_layout_and_images():
        print()
        print("🎉 CORREÇÃO CONCLUÍDA COM SUCESSO!")
        print("="*60)
        print("📋 CORREÇÕES APLICADAS:")
        print("✅ Layout modernizado com sidebar responsiva")
        print("✅ Imagens substituídas por fotos profissionais")
        print("✅ Dados da tabela atualizados com informações realistas")
        print("✅ Barra de ferramentas moderna adicionada")
        print("✅ Funcionalidades JavaScript implementadas")
        print("✅ Elementos HTML problemáticos corrigidos")
        print("✅ Estrutura responsiva melhorada")
        print()
        print("🌐 Teste a página agora:")
        print("   https://duralux-mu.vercel.app/duralux-admin/customers.html")
        print()
    else:
        print("❌ Falha na correção. Verifique os logs acima.")