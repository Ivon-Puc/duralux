#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
🎯 CORREÇÃO: FUNCIONALIDADES ESPECÍFICAS POR PÁGINA
===================================================
Cada página terá seu conteúdo e funcionalidade específica
"""

import os
import re
from datetime import datetime

def get_specific_content():
    """Conteúdo específico para cada página"""
    return {
        'index.html': {
            'title': 'Painel de Controle',
            'page_title': 'Dashboard Executivo',
            'breadcrumb': 'Dashboard',
            'nav_active': 'nav_index',
            'content': '''
            <!-- Estatísticas Principais -->
            <div class="stats-row fade-in">
                <div class="stat-card">
                    <div class="stat-icon" style="background: var(--primary-color);">
                        <i class="bi bi-person-plus"></i>
                    </div>
                    <h3 class="stat-number">156</h3>
                    <p class="stat-label">Total de Leads</p>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: var(--success-color);">
                        <i class="bi bi-people"></i>
                    </div>
                    <h3 class="stat-number">89</h3>
                    <p class="stat-label">Clientes Ativos</p>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: var(--info-color);">
                        <i class="bi bi-folder"></i>
                    </div>
                    <h3 class="stat-number">34</h3>
                    <p class="stat-label">Projetos Ativos</p>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: var(--warning-color);">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <h3 class="stat-number">R$ 2.4M</h3>
                    <p class="stat-label">Receita Total</p>
                </div>
            </div>

            <!-- Gráficos e Widgets -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="content-area fade-in">
                        <h4><i class="bi bi-graph-up me-2"></i>Performance Mensal</h4>
                        <div style="height: 300px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center; margin: 1rem 0;">
                            <div class="text-center">
                                <i class="bi bi-bar-chart" style="font-size: 3rem; color: var(--primary-color);"></i>
                                <p class="mt-2">Gráfico de Performance dos Últimos 6 Meses</p>
                                <small class="text-muted">Crescimento de 24% no período</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="content-area fade-in">
                        <h5><i class="bi bi-clock me-2"></i>Atividades Recentes</h5>
                        <div class="activity-list">
                            <div class="activity-item d-flex align-items-center mb-3">
                                <div class="activity-icon bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px;">
                                    <i class="bi bi-person-plus" style="font-size: 0.8rem;"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold">Novo cliente cadastrado</div>
                                    <small class="text-muted">Marina Santos - há 5 min</small>
                                </div>
                            </div>
                            
                            <div class="activity-item d-flex align-items-center mb-3">
                                <div class="activity-icon bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px;">
                                    <i class="bi bi-folder" style="font-size: 0.8rem;"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold">Projeto finalizado</div>
                                    <small class="text-muted">Reforma Comercial ABC - há 1h</small>
                                </div>
                            </div>
                            
                            <div class="activity-item d-flex align-items-center mb-3">
                                <div class="activity-icon bg-warning text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px;">
                                    <i class="bi bi-file-text" style="font-size: 0.8rem;"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold">Proposta aprovada</div>
                                    <small class="text-muted">Orçamento #1247 - há 2h</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resumo Executivo -->
            <div class="content-area fade-in">
                <h4><i class="bi bi-pie-chart me-2"></i>Resumo Executivo</h4>
                <div class="row">
                    <div class="col-md-3 text-center">
                        <div class="metric-summary">
                            <div class="display-6 text-success fw-bold">87%</div>
                            <p class="text-muted">Taxa de Satisfação</p>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="metric-summary">
                            <div class="display-6 text-primary fw-bold">24%</div>
                            <p class="text-muted">Crescimento Mensal</p>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="metric-summary">
                            <div class="display-6 text-info fw-bold">156</div>
                            <p class="text-muted">Leads Convertidos</p>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="metric-summary">
                            <div class="display-6 text-warning fw-bold">4.8</div>
                            <p class="text-muted">Avaliação Média</p>
                        </div>
                    </div>
                </div>
            </div>
            ''',
            'custom_js': '''
                // Dashboard específico
                function loadDashboardData() {
                    // Simular carregamento de dados do dashboard
                    setTimeout(() => {
                        updateRecentActivities();
                        updateMetrics();
                    }, 1000);
                }
                
                function updateRecentActivities() {
                    // Atualizar atividades recentes
                    console.log('Atividades atualizadas');
                }
                
                function updateMetrics() {
                    // Atualizar métricas em tempo real
                    console.log('Métricas atualizadas');
                }
                
                // Carregar dados quando a página carrega
                document.addEventListener('DOMContentLoaded', loadDashboardData);
            '''
        },
        
        'leads.html': {
            'title': 'Gestão de Leads',
            'page_title': 'Gerenciar Leads',
            'breadcrumb': 'Leads',
            'nav_active': 'nav_leads',
            'content': '''
            <!-- Estatísticas de Leads -->
            <div class="stats-row fade-in">
                <div class="stat-card">
                    <div class="stat-icon" style="background: var(--primary-color);">
                        <i class="bi bi-person-plus"></i>
                    </div>
                    <h3 class="stat-number">156</h3>
                    <p class="stat-label">Total de Leads</p>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: var(--success-color);">
                        <i class="bi bi-person-check"></i>
                    </div>
                    <h3 class="stat-number">89</h3>
                    <p class="stat-label">Leads Qualificados</p>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: var(--info-color);">
                        <i class="bi bi-clock"></i>
                    </div>
                    <h3 class="stat-number">23</h3>
                    <p class="stat-label">Novos Hoje</p>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: var(--warning-color);">
                        <i class="bi bi-percent"></i>
                    </div>
                    <h3 class="stat-number">67%</h3>
                    <p class="stat-label">Taxa de Conversão</p>
                </div>
            </div>

            <!-- Funil de Vendas -->
            <div class="content-area fade-in">
                <h4><i class="bi bi-funnel me-2"></i>Funil de Vendas</h4>
                <div class="sales-funnel">
                    <div class="funnel-stage">
                        <div class="funnel-bar" style="width: 100%; background: var(--primary-color);">
                            <span>Prospects: 156</span>
                        </div>
                    </div>
                    <div class="funnel-stage">
                        <div class="funnel-bar" style="width: 75%; background: var(--info-color);">
                            <span>Qualificados: 117</span>
                        </div>
                    </div>
                    <div class="funnel-stage">
                        <div class="funnel-bar" style="width: 50%; background: var(--warning-color);">
                            <span>Propostas: 78</span>
                        </div>
                    </div>
                    <div class="funnel-stage">
                        <div class="funnel-bar" style="width: 30%; background: var(--success-color);">
                            <span>Fechados: 47</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Toolbar de Leads -->
            <div class="toolbar fade-in">
                <div class="toolbar-row">
                    <div class="search-box">
                        <input type="text" placeholder="Buscar leads..." id="searchLeads">
                        <i class="bi bi-search"></i>
                    </div>
                    
                    <div class="toolbar-actions">
                        <div class="btn-group me-2">
                            <button class="btn btn-outline dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="bi bi-funnel"></i> Filtrar por Status
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="filterLeads('all')">Todos</a></li>
                                <li><a class="dropdown-item" href="#" onclick="filterLeads('new')">Novos</a></li>
                                <li><a class="dropdown-item" href="#" onclick="filterLeads('qualified')">Qualificados</a></li>
                                <li><a class="dropdown-item" href="#" onclick="filterLeads('proposal')">Em Proposta</a></li>
                                <li><a class="dropdown-item" href="#" onclick="filterLeads('closed')">Fechados</a></li>
                            </ul>
                        </div>
                        <button class="btn btn-outline" onclick="exportLeads()">
                            <i class="bi bi-download"></i> Exportar
                        </button>
                        <button class="btn btn-primary" onclick="newLead()">
                            <i class="bi bi-plus-lg"></i> Novo Lead
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabela de Leads -->
            <div class="table-container fade-in" id="leadsTable">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Lead</th>
                            <th>Empresa</th>
                            <th>Origem</th>
                            <th>Status</th>
                            <th>Último Contato</th>
                            <th>Valor Potencial</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr data-status="qualified">
                            <td>
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <img src="https://images.unsplash.com/photo-1494790108755-2616b612b5bb?w=150&h=150&fit=crop&crop=face" alt="Maria Silva" class="avatar">
                                    <div>
                                        <div style="font-weight: 600;">Maria Silva</div>
                                        <small style="color: #6c757d;">maria.silva@empresa.com.br</small>
                                    </div>
                                </div>
                            </td>
                            <td>Construtora ABC</td>
                            <td><span class="badge badge-info">Site</span></td>
                            <td><span class="badge badge-success">Qualificado</span></td>
                            <td>Hoje, 14:30</td>
                            <td>R$ 45.000</td>
                            <td>
                                <div class="actions">
                                    <button class="btn btn-sm btn-outline-primary" onclick="viewLead(1)" title="Visualizar">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="contactLead(1)" title="Contatar">
                                        <i class="bi bi-telephone"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-success" onclick="convertLead(1)" title="Converter">
                                        <i class="bi bi-arrow-right"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        
                        <tr data-status="new">
                            <td>
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=150&h=150&fit=crop&crop=face" alt="João Santos" class="avatar">
                                    <div>
                                        <div style="font-weight: 600;">João Santos</div>
                                        <small style="color: #6c757d;">joao.santos@reformas.com.br</small>
                                    </div>
                                </div>
                            </td>
                            <td>Reformas Premium</td>
                            <td><span class="badge badge-warning">Indicação</span></td>
                            <td><span class="badge badge-primary">Novo</span></td>
                            <td>Ontem, 16:45</td>
                            <td>R$ 28.000</td>
                            <td>
                                <div class="actions">
                                    <button class="btn btn-sm btn-outline-primary" onclick="viewLead(2)" title="Visualizar">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="contactLead(2)" title="Contatar">
                                        <i class="bi bi-telephone"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-success" onclick="convertLead(2)" title="Converter">
                                        <i class="bi bi-arrow-right"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        
                        <tr data-status="proposal">
                            <td>
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <img src="https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=150&h=150&fit=crop&crop=face" alt="Ana Costa" class="avatar">
                                    <div>
                                        <div style="font-weight: 600;">Ana Costa</div>
                                        <small style="color: #6c757d;">ana.costa@design.com.br</small>
                                    </div>
                                </div>
                            </td>
                            <td>Design & Arquitetura</td>
                            <td><span class="badge badge-success">LinkedIn</span></td>
                            <td><span class="badge badge-warning">Em Proposta</span></td>
                            <td>2 dias atrás</td>
                            <td>R$ 67.000</td>
                            <td>
                                <div class="actions">
                                    <button class="btn btn-sm btn-outline-primary" onclick="viewLead(3)" title="Visualizar">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="contactLead(3)" title="Contatar">
                                        <i class="bi bi-telephone"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-success" onclick="convertLead(3)" title="Converter">
                                        <i class="bi bi-arrow-right"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            ''',
            'custom_css': '''
                /* Funil de vendas */
                .sales-funnel {
                    margin: 2rem 0;
                }
                
                .funnel-stage {
                    margin-bottom: 1rem;
                }
                
                .funnel-bar {
                    height: 50px;
                    display: flex;
                    align-items: center;
                    padding: 0 1rem;
                    border-radius: 8px;
                    color: white;
                    font-weight: 600;
                    position: relative;
                    margin-bottom: 0.5rem;
                }
                
                .funnel-bar::after {
                    content: '';
                    position: absolute;
                    right: -15px;
                    top: 50%;
                    transform: translateY(-50%);
                    width: 0;
                    height: 0;
                    border-left: 15px solid;
                    border-top: 25px solid transparent;
                    border-bottom: 25px solid transparent;
                    border-left-color: inherit;
                }
            ''',
            'custom_js': '''
                function filterLeads(status) {
                    const rows = document.querySelectorAll('#leadsTable tbody tr');
                    rows.forEach(row => {
                        if (status === 'all' || row.dataset.status === status) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                }
                
                function newLead() {
                    alert('Abrindo formulário de novo lead...');
                }
                
                function viewLead(id) {
                    alert(`Visualizando lead ID: ${id}`);
                }
                
                function contactLead(id) {
                    alert(`Contatando lead ID: ${id}`);
                }
                
                function convertLead(id) {
                    if (confirm(`Converter lead ID ${id} em cliente?`)) {
                        alert('Lead convertido com sucesso!');
                    }
                }
                
                function exportLeads() {
                    alert('Exportando leads...');
                }
            '''
        },
        
        'projects.html': {
            'title': 'Gestão de Projetos',
            'page_title': 'Gerenciar Projetos',
            'breadcrumb': 'Projetos',
            'nav_active': 'nav_projects',
            'content': '''
            <!-- Estatísticas de Projetos -->
            <div class="stats-row fade-in">
                <div class="stat-card">
                    <div class="stat-icon" style="background: var(--primary-color);">
                        <i class="bi bi-folder"></i>
                    </div>
                    <h3 class="stat-number">34</h3>
                    <p class="stat-label">Projetos Ativos</p>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: var(--success-color);">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <h3 class="stat-number">128</h3>
                    <p class="stat-label">Projetos Concluídos</p>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: var(--info-color);">
                        <i class="bi bi-clock"></i>
                    </div>
                    <h3 class="stat-number">7</h3>
                    <p class="stat-label">Em Andamento</p>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: var(--danger-color);">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <h3 class="stat-number">3</h3>
                    <p class="stat-label">Atrasados</p>
                </div>
            </div>

            <!-- Timeline de Projetos -->
            <div class="content-area fade-in">
                <h4><i class="bi bi-calendar-event me-2"></i>Timeline de Projetos</h4>
                <div class="project-timeline">
                    <div class="timeline-item">
                        <div class="timeline-marker bg-success"></div>
                        <div class="timeline-content">
                            <h6>Projeto Finalizado</h6>
                            <p>Reforma Comercial ABC - Entregue no prazo</p>
                            <small class="text-muted">Hoje, 09:30</small>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-marker bg-warning"></div>
                        <div class="timeline-content">
                            <h6>Marco Importante</h6>
                            <p>Projeto Residencial XYZ - 75% concluído</p>
                            <small class="text-muted">Ontem, 14:15</small>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-marker bg-primary"></div>
                        <div class="timeline-content">
                            <h6>Novo Projeto Iniciado</h6>
                            <p>Escritório Moderno - Fase de planejamento</p>
                            <small class="text-muted">2 dias atrás</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Toolbar de Projetos -->
            <div class="toolbar fade-in">
                <div class="toolbar-row">
                    <div class="search-box">
                        <input type="text" placeholder="Buscar projetos..." id="searchProjects">
                        <i class="bi bi-search"></i>
                    </div>
                    
                    <div class="toolbar-actions">
                        <div class="btn-group me-2">
                            <button class="btn btn-outline dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="bi bi-funnel"></i> Status
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="filterProjects('all')">Todos</a></li>
                                <li><a class="dropdown-item" href="#" onclick="filterProjects('planning')">Planejamento</a></li>
                                <li><a class="dropdown-item" href="#" onclick="filterProjects('progress')">Em Andamento</a></li>
                                <li><a class="dropdown-item" href="#" onclick="filterProjects('completed')">Concluídos</a></li>
                                <li><a class="dropdown-item" href="#" onclick="filterProjects('delayed')">Atrasados</a></li>
                            </ul>
                        </div>
                        <button class="btn btn-outline" onclick="exportProjects()">
                            <i class="bi bi-download"></i> Relatório
                        </button>
                        <button class="btn btn-primary" onclick="newProject()">
                            <i class="bi bi-plus-lg"></i> Novo Projeto
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabela de Projetos -->
            <div class="table-container fade-in" id="projectsTable">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Projeto</th>
                            <th>Cliente</th>
                            <th>Progresso</th>
                            <th>Status</th>
                            <th>Prazo</th>
                            <th>Orçamento</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr data-status="progress">
                            <td>
                                <div>
                                    <div style="font-weight: 600;">Reforma Comercial</div>
                                    <small style="color: #6c757d;">Loja Centro - Fase de acabamento</small>
                                </div>
                            </td>
                            <td>Comércio ABC Ltda</td>
                            <td>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar" style="width: 85%; background: var(--success-color);">85%</div>
                                </div>
                            </td>
                            <td><span class="badge badge-success">Em Andamento</span></td>
                            <td>15/11/2025</td>
                            <td>R$ 125.000</td>
                            <td>
                                <div class="actions">
                                    <button class="btn btn-sm btn-outline-primary" onclick="viewProject(1)" title="Visualizar">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="editProject(1)" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-info" onclick="projectTasks(1)" title="Tarefas">
                                        <i class="bi bi-list-check"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        
                        <tr data-status="planning">
                            <td>
                                <div>
                                    <div style="font-weight: 600;">Escritório Moderno</div>
                                    <small style="color: #6c757d;">Coworking Premium - Projeto arquitetônico</small>
                                </div>
                            </td>
                            <td>Inovação Corp</td>
                            <td>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar" style="width: 25%; background: var(--warning-color);">25%</div>
                                </div>
                            </td>
                            <td><span class="badge badge-warning">Planejamento</span></td>
                            <td>30/12/2025</td>
                            <td>R$ 280.000</td>
                            <td>
                                <div class="actions">
                                    <button class="btn btn-sm btn-outline-primary" onclick="viewProject(2)" title="Visualizar">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="editProject(2)" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-info" onclick="projectTasks(2)" title="Tarefas">
                                        <i class="bi bi-list-check"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        
                        <tr data-status="delayed">
                            <td>
                                <div>
                                    <div style="font-weight: 600;">Residencial Luxo</div>
                                    <small style="color: #6c757d;">Casa Alto Padrão - Revisão estrutural</small>
                                </div>
                            </td>
                            <td>Família Santos</td>
                            <td>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar" style="width: 60%; background: var(--danger-color);">60%</div>
                                </div>
                            </td>
                            <td><span class="badge badge-danger">Atrasado</span></td>
                            <td>01/11/2025</td>
                            <td>R$ 450.000</td>
                            <td>
                                <div class="actions">
                                    <button class="btn btn-sm btn-outline-primary" onclick="viewProject(3)" title="Visualizar">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="editProject(3)" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-info" onclick="projectTasks(3)" title="Tarefas">
                                        <i class="bi bi-list-check"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            ''',
            'custom_css': '''
                /* Timeline de projetos */
                .project-timeline {
                    position: relative;
                    padding-left: 2rem;
                }
                
                .project-timeline::before {
                    content: '';
                    position: absolute;
                    left: 15px;
                    top: 0;
                    bottom: 0;
                    width: 2px;
                    background: #e9ecef;
                }
                
                .timeline-item {
                    position: relative;
                    margin-bottom: 2rem;
                }
                
                .timeline-marker {
                    position: absolute;
                    left: -23px;
                    top: 5px;
                    width: 16px;
                    height: 16px;
                    border-radius: 50%;
                    border: 3px solid white;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                
                .timeline-content h6 {
                    margin-bottom: 0.5rem;
                    color: var(--dark-color);
                }
                
                .timeline-content p {
                    margin-bottom: 0.25rem;
                    color: #6c757d;
                }
                
                /* Progress bars */
                .progress {
                    border-radius: 10px;
                    overflow: hidden;
                }
                
                .progress-bar {
                    border-radius: 10px;
                    transition: width 0.6s ease;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: white;
                    font-weight: 600;
                    font-size: 0.8rem;
                }
            ''',
            'custom_js': '''
                function filterProjects(status) {
                    const rows = document.querySelectorAll('#projectsTable tbody tr');
                    rows.forEach(row => {
                        if (status === 'all' || row.dataset.status === status) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                }
                
                function newProject() {
                    alert('Abrindo formulário de novo projeto...');
                }
                
                function viewProject(id) {
                    alert(`Visualizando detalhes do projeto ID: ${id}`);
                }
                
                function editProject(id) {
                    alert(`Editando projeto ID: ${id}`);
                }
                
                function projectTasks(id) {
                    alert(`Visualizando tarefas do projeto ID: ${id}`);
                }
                
                function exportProjects() {
                    alert('Gerando relatório de projetos...');
                }
            '''
        }
    }

def create_specific_pages():
    """Cria páginas com funcionalidades específicas"""
    print("🎯 CRIANDO PÁGINAS COM FUNCIONALIDADES ESPECÍFICAS")
    print("="*60)
    
    base_template = '''<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="Sistema de CRM Duralux - {title}" />
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
        
        .layout-container {{
            display: flex;
            min-height: 100vh;
        }}
        
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
        
        .main-content {{
            flex: 1;
            margin-left: 250px;
            padding: 2rem;
        }}
        
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
        
        .content-area {{
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }}
        
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
        
        .badge-success {{ background: var(--success-color); color: white; }}
        .badge-primary {{ background: var(--primary-color); color: white; }}
        .badge-info {{ background: var(--info-color); color: white; }}
        .badge-warning {{ background: var(--warning-color); color: white; }}
        .badge-danger {{ background: var(--danger-color); color: white; }}
        
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
        
        .btn-outline-primary {{ color: var(--primary-color); border: 1px solid var(--primary-color); }}
        .btn-outline-primary:hover {{ background: var(--primary-color); color: white; }}
        .btn-outline-secondary {{ color: #6c757d; border: 1px solid #6c757d; }}
        .btn-outline-secondary:hover {{ background: #6c757d; color: white; }}
        .btn-outline-success {{ color: var(--success-color); border: 1px solid var(--success-color); }}
        .btn-outline-success:hover {{ background: var(--success-color); color: white; }}
        .btn-outline-info {{ color: var(--info-color); border: 1px solid var(--info-color); }}
        .btn-outline-info:hover {{ background: var(--info-color); color: white; }}
        .btn-outline-danger {{ color: var(--danger-color); border: 1px solid var(--danger-color); }}
        .btn-outline-danger:hover {{ background: var(--danger-color); color: white; }}
        
        .fade-in {{
            animation: fadeIn 0.5s ease-in;
        }}
        
        @keyframes fadeIn {{
            from {{ opacity: 0; transform: translateY(20px); }}
            to {{ opacity: 1; transform: translateY(0); }}
        }}
        
        @media (max-width: 768px) {{
            .sidebar {{ transform: translateX(-100%); }}
            .sidebar.open {{ transform: translateX(0); }}
            .main-content {{ margin-left: 0; padding: 1rem; }}
            .toolbar-row {{ flex-direction: column; align-items: stretch; }}
            .stats-row {{ grid-template-columns: 1fr; }}
        }}
        
        {custom_css}
    </style>
</head>
<body>
    <div class="layout-container">
        <nav class="sidebar">
            <div class="brand">
                <i class="bi bi-building"></i> DURALUX
            </div>
            
            <div class="nav-menu">
                <a href="index.html" class="nav-link {nav_index}">
                    <i class="bi bi-speedometer2"></i> Painel de Controle
                </a>
                <a href="leads.html" class="nav-link {nav_leads}">
                    <i class="bi bi-person-plus"></i> Leads
                </a>
                <a href="customers.html" class="nav-link {nav_customers}">
                    <i class="bi bi-people"></i> Clientes
                </a>
                <a href="projects.html" class="nav-link {nav_projects}">
                    <i class="bi bi-folder"></i> Projetos
                </a>
                <a href="proposal.html" class="nav-link {nav_proposals}">
                    <i class="bi bi-file-text"></i> Propostas
                </a>
                <a href="analytics.html" class="nav-link {nav_analytics}">
                    <i class="bi bi-graph-up"></i> Analytics
                </a>
                <a href="reports.html" class="nav-link {nav_reports}">
                    <i class="bi bi-file-earmark-bar-graph"></i> Relatórios
                </a>
            </div>
        </nav>
        
        <main class="main-content">
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {{
            // Animações
            const elements = document.querySelectorAll('.stat-card, .content-area, .table-container');
            elements.forEach((el, index) => {{
                setTimeout(() => {{
                    el.classList.add('fade-in');
                }}, index * 100);
            }});
            
            // Busca global
            const searchInputs = document.querySelectorAll('input[type="text"]');
            searchInputs.forEach(input => {{
                if (input.placeholder && input.placeholder.includes('Buscar')) {{
                    input.addEventListener('input', function() {{
                        const filter = this.value.toLowerCase();
                        const rows = document.querySelectorAll('tbody tr');
                        
                        rows.forEach(row => {{
                            const text = row.textContent.toLowerCase();
                            row.style.display = text.includes(filter) ? '' : 'none';
                        }});
                    }});
                }}
            }});
        }});
        
        {custom_js}
    </script>
</body>
</html>'''
    
    pages_dir = "C:/wamp64/www/ildavieira/duralux/duralux-admin"
    specific_content = get_specific_content()
    
    successful_pages = []
    failed_pages = []
    
    for page_name, config in specific_content.items():
        file_path = os.path.join(pages_dir, page_name)
        
        try:
            print(f"🔄 Criando página específica: {page_name}")
            
            # Criar backup
            if os.path.exists(file_path):
                backup_path = f"{file_path}.backup-specific-{datetime.now().strftime('%Y%m%d_%H%M%S')}"
                with open(file_path, 'r', encoding='utf-8') as original:
                    with open(backup_path, 'w', encoding='utf-8') as backup:
                        backup.write(original.read())
            
            # Preparar variáveis de navegação
            nav_vars = {
                'nav_index': 'active' if config['nav_active'] == 'nav_index' else '',
                'nav_leads': 'active' if config['nav_active'] == 'nav_leads' else '',
                'nav_customers': 'active' if config['nav_active'] == 'nav_customers' else '',
                'nav_projects': 'active' if config['nav_active'] == 'nav_projects' else '',
                'nav_proposals': 'active' if config['nav_active'] == 'nav_proposals' else '',
                'nav_analytics': 'active' if config['nav_active'] == 'nav_analytics' else '',
                'nav_reports': 'active' if config['nav_active'] == 'nav_reports' else ''
            }
            
            # Gerar HTML final
            final_html = base_template.format(
                title=config['title'],
                page_title=config['page_title'],
                breadcrumb=config['breadcrumb'],
                content=config['content'],
                custom_css=config.get('custom_css', ''),
                custom_js=config.get('custom_js', ''),
                **nav_vars
            )
            
            # Salvar arquivo
            with open(file_path, 'w', encoding='utf-8') as file:
                file.write(final_html)
            
            print(f"✅ {page_name} criada com funcionalidades específicas!")
            successful_pages.append(page_name)
            
        except Exception as e:
            print(f"❌ Erro ao criar {page_name}: {e}")
            failed_pages.append(page_name)
    
    return successful_pages, failed_pages

if __name__ == "__main__":
    print("🎯 INICIANDO CRIAÇÃO DE PÁGINAS ESPECÍFICAS")
    print("="*60)
    print(f"⏰ Data/Hora: {datetime.now().strftime('%d/%m/%Y %H:%M:%S')}")
    print()
    
    successful, failed = create_specific_pages()
    
    print()
    print("📊 RESULTADO DA CRIAÇÃO")
    print("="*60)
    print(f"✅ Páginas criadas com sucesso: {len(successful)}")
    for page in successful:
        print(f"   • {page}")
    
    if failed:
        print(f"\n❌ Páginas com falha: {len(failed)}")
        for page in failed:
            print(f"   • {page}")
    
    if successful:
        print()
        print("🎉 PÁGINAS ESPECÍFICAS CRIADAS!")
        print("="*60)
        print("📋 FUNCIONALIDADES ESPECÍFICAS:")
        print("✅ Dashboard: Métricas executivas + atividades recentes + gráficos")
        print("✅ Leads: Funil de vendas + gestão de leads + conversão")
        print("✅ Projetos: Timeline + progresso + gestão de tarefas")
        print("✅ Cada página tem dados e ações específicas")
        print("✅ Funcionalidades JavaScript únicas por página")
        print("✅ Interface específica para cada contexto")
        print()
        print("🌐 Teste as páginas específicas:")
        for page in successful:
            print(f"   https://duralux-mu.vercel.app/duralux-admin/{page}")
        print()
    else:
        print("\n❌ Nenhuma página foi criada com sucesso.")