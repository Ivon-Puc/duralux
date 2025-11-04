/**
 * DURALUX CRM - Sistema de Projetos v1.2
 * Gerenciamento completo de projetos e tarefas
 * 
 * @author Duralux Development Team
 * @version 1.2.0
 * @since 2024
 */

'use strict';

class DuraluxProjects {
    constructor() {
        // Settings da API
        this.apiBase = '../backend/api/router.php';
        this.endpoints = {
            projects: {
                list: `${this.apiBase}?action=projects&method=GET`,
                create: `${this.apiBase}?action=projects&method=POST`,
                update: (id) => `${this.apiBase}?action=projects&method=PUT&id=${id}`,
                delete: (id) => `${this.apiBase}?action=projects&method=DELETE&id=${id}`,
                view: (id) => `${this.apiBase}?action=projects&method=GET&id=${id}`
            },
            tasks: {
                list: (projectId) => `${this.apiBase}?action=project_tasks&method=GET&project_id=${projectId}`,
                create: `${this.apiBase}?action=project_tasks&method=POST`,
                update: (id) => `${this.apiBase}?action=project_tasks&method=PUT&id=${id}`,
                delete: (id) => `${this.apiBase}?action=project_tasks&method=DELETE&id=${id}`,
                updateStatus: (id) => `${this.apiBase}?action=project_tasks_status&method=PUT&id=${id}`
            },
            customers: `${this.apiBase}?action=customers&method=GET`,
            stats: `${this.apiBase}?action=projects_stats&method=GET`
        };

        // Estado da aplicação
        this.currentPage = 1;
        this.itemsPerPage = 10;
        this.currentFiltrars = {};
        this.currentSort = { field: 'name', direction: 'asc' };
        this.projects = [];
        this.customers = [];
        this.selectedProject = null;

        // Configuração de auto-refresh
        this.autoRefreshInterval = 30000; // 30 segundos
        this.autoRefreshTimer = null;

        // Status e prioridades dos projetos
        this.projectStatus = {
            'planning': { label: 'Planejamento', class: 'info', icon: 'fas fa-clipboard-list' },
            'in_progress': { label: 'Em Andamento', class: 'primary', icon: 'fas fa-play-circle' },
            'review': { label: 'Em Revisão', class: 'warning', icon: 'fas fa-search' },
            'completed': { label: 'Concluído', class: 'success', icon: 'fas fa-check-circle' },
            'on_hold': { label: 'Pausado', class: 'secondary', icon: 'fas fa-pause-circle' },
            'cancelled': { label: 'Cancelado', class: 'danger', icon: 'fas fa-times-circle' }
        };

        this.taskStatus = {
            'pending': { label: 'Pendente', class: 'secondary', icon: 'fas fa-clock' },
            'in_progress': { label: 'Em Andamento', class: 'primary', icon: 'fas fa-play' },
            'completed': { label: 'Concluída', class: 'success', icon: 'fas fa-check' },
            'cancelled': { label: 'Cancelada', class: 'danger', icon: 'fas fa-times' }
        };

        this.priorities = {
            'low': { label: 'Baixa', class: 'success', icon: 'fas fa-arrow-down' },
            'medium': { label: 'Média', class: 'warning', icon: 'fas fa-minus' },
            'high': { label: 'Alta', class: 'danger', icon: 'fas fa-arrow-up' },
            'urgent': { label: 'Urgente', class: 'danger', icon: 'fas fa-exclamation-triangle' }
        };

        this.init();
    }

    /**
     * Inicializa o sistema de projetos
     */
    async init() {
        try {
            console.log('🚀 Iniciando Sistema de Projetos v1.2...');
            
            // Carrega dados iniciais
            await this.loadCustomers();
            await this.loadProjects();
            await this.loadStats();
            
            // Configura eventos
            this.setupEventListeners();
            this.setupAutoRefresh();
            
            // Inicializa componentes da interface
            this.setupFiltrars();
            this.setupPagination();
            
            console.log('✅ Sistema de Projetos inicializado com sucesso!');
            this.showNotification('Sistema de Projetos carregado com sucesso!', 'success');
            
        } catch (error) {
            console.error('❌ Erro ao inicializar sistema de projetos:', error);
            this.showNotification('Erro ao carregar sistema de projetos', 'error');
        }
    }

    /**
     * Configura todos os event listeners
     */
    setupEventListeners() {
        // Botões principais
        document.getElementById('btnNovoProject')?.addEventListener('click', () => this.showProjectModal());
        document.getElementById('btnRefreshProjects')?.addEventListener('click', () => this.refreshProjects());
        
        // Formulário de projeto
        document.getElementById('projectForm')?.addEventListener('submit', (e) => this.handleProjectSubmit(e));
        document.getElementById('taskForm')?.addEventListener('submit', (e) => this.handleTaskSubmit(e));
        
        // Filtros e pesquisa
        document.getElementById('searchProjects')?.addEventListener('input', (e) => this.handleSearch(e));
        document.getElementById('filterStatus')?.addEventListener('change', () => this.applyFiltrars());
        document.getElementById('filterPriority')?.addEventListener('change', () => this.applyFiltrars());
        document.getElementById('filterCustomer')?.addEventListener('change', () => this.applyFiltrars());
        
        // Ordenação
        document.querySelectorAll('.sort-header').forEach(header => {
            header.addEventListener('click', (e) => this.handleSort(e));
        });

        // Modal de tarefas
        document.getElementById('btnNovoTask')?.addEventListener('click', () => this.showTaskModal());
        
        // Botões de ação em massa
        document.getElementById('btnBulkDelete')?.addEventListener('click', () => this.handleBulkDelete());
        document.getElementById('selectAllProjects')?.addEventListener('change', (e) => this.handleSelectAll(e));
    }

    /**
     * Configura auto-refresh
     */
    setupAutoRefresh() {
        if (this.autoRefreshTimer) {
            clearInterval(this.autoRefreshTimer);
        }
        
        this.autoRefreshTimer = setInterval(() => {
            this.refreshProjects(false); // Refresh silencioso
        }, this.autoRefreshInterval);
    }

    /**
     * Carrega lista de clientes
     */
    async loadCustomers() {
        try {
            const response = await fetch(this.endpoints.customers);
            const data = await response.json();
            
            if (data.success) {
                this.customers = data.data || [];
                this.populateCustomerSelects();
            }
        } catch (error) {
            console.error('Erro ao carregar clientes:', error);
        }
    }

    /**
     * Popula selects de cliente
     */
    populateCustomerSelects() {
        const selects = ['projectCustomerId', 'filterCustomer'];
        
        selects.forEach(selectId => {
            const select = document.getElementById(selectId);
            if (select) {
                // Preserva opção padrão se existir
                const defaultOption = select.querySelector('option[value=""]');
                select.innerHTML = '';
                
                if (defaultOption) {
                    select.appendChild(defaultOption);
                }
                
                this.customers.forEach(customer => {
                    const option = document.createElement('option');
                    option.value = customer.id;
                    option.textContent = customer.name;
                    select.appendChild(option);
                });
            }
        });
    }

    /**
     * Carrega projetos com filtros e paginação
     */
    async loadProjects() {
        try {
            // Monta URL com parâmetros
            const params = new URLSearchParams({
                page: this.currentPage,
                limit: this.itemsPerPage,
                sort_field: this.currentSort.field,
                sort_direction: this.currentSort.direction,
                ...this.currentFiltrars
            });

            const url = `${this.endpoints.projects.list}&${params.toString()}`;
            const response = await fetch(url);
            const data = await response.json();

            if (data.success) {
                this.projects = data.data || [];
                this.renderProjectsTable();
                this.updatePagination(data.pagination || {});
            } else {
                throw new Error(data.message || 'Erro ao carregar projetos');
            }
        } catch (error) {
            console.error('Erro ao carregar projetos:', error);
            this.showNotification('Erro ao carregar projetos', 'error');
        }
    }

    /**
     * Carrega estatísticas dos projetos
     */
    async loadStats() {
        try {
            const response = await fetch(this.endpoints.stats);
            const data = await response.json();
            
            if (data.success && data.data) {
                this.renderStats(data.data);
            }
        } catch (error) {
            console.error('Erro ao carregar estatísticas:', error);
        }
    }

    /**
     * Renderiza estatísticas
     */
    renderStats(stats) {
        // Total de projetos
        const totalElement = document.getElementById('totalProjects');
        if (totalElement) {
            totalElement.textContent = stats.total || 0;
        }

        // Projetos ativos
        const activeElement = document.getElementById('activeProjects');
        if (activeElement) {
            activeElement.textContent = stats.active || 0;
        }

        // Projetos concluídos
        const completedElement = document.getElementById('completedProjects');
        if (completedElement) {
            completedElement.textContent = stats.completed || 0;
        }

        // Projetos em atraso
        const overdueElement = document.getElementById('overdueProjects');
        if (overdueElement) {
            overdueElement.textContent = stats.overdue || 0;
        }

        // Gráfico de status (se existir container)
        const chartContainer = document.getElementById('projectStatusChart');
        if (chartContainer && stats.by_status) {
            this.renderStatusChart(stats.by_status);
        }
    }

    /**
     * Renderiza tabela de projetos
     */
    renderProjectsTable() {
        const tbody = document.getElementById('projectsTableBody');
        if (!tbody) return;

        if (this.projects.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-4">
                        <div class="text-muted">
                            <i class="fas fa-inbox fa-2x mb-2"></i>
                            <p>Nenhum projeto encontrado</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = this.projects.map(project => this.renderProjectRow(project)).join('');
    }

    /**
     * Renderiza linha da tabela de projeto
     */
    renderProjectRow(project) {
        const status = this.projectStatus[project.status] || this.projectStatus['planning'];
        const priority = this.priorities[project.priority] || this.priorities['medium'];
        const customer = this.customers.find(c => c.id == project.customer_id);
        
        // Calcula progresso
        const progress = project.progress || 0;
        const progressClass = progress >= 75 ? 'success' : progress >= 50 ? 'info' : progress >= 25 ? 'warning' : 'secondary';
        
        // Calcula dias restantes
        const dueDate = project.due_date ? new Date(project.due_date) : null;
        const today = new Date();
        const daysRemaining = dueDate ? Math.ceil((dueDate - today) / (1000 * 60 * 60 * 24)) : null;
        
        let dueDateDisplay = 'Sem prazo';
        let dueDateClass = 'text-muted';
        
        if (dueDate) {
            const dateStr = new Intl.DateTimeFormat('pt-BR').format(dueDate);
            if (daysRemaining < 0) {
                dueDateDisplay = `${dateStr} <small class="text-danger">(${Math.abs(daysRemaining)} dias em atraso)</small>`;
                dueDateClass = 'text-danger';
            } else if (daysRemaining <= 3) {
                dueDateDisplay = `${dateStr} <small class="text-warning">(${daysRemaining} dias)</small>`;
                dueDateClass = 'text-warning';
            } else {
                dueDateDisplay = `${dateStr} <small class="text-muted">(${daysRemaining} dias)</small>`;
            }
        }

        return `
            <tr data-project-id="${project.id}">
                <td>
                    <div class="form-check">
                        <input class="form-check-input project-checkbox" type="checkbox" value="${project.id}" id="project_${project.id}">
                    </div>
                </td>
                <td>
                    <div>
                        <strong>${this.escapeHtml(project.name)}</strong>
                        ${project.description ? `<br><small class="text-muted">${this.escapeHtml(project.description).substring(0, 100)}${project.description.length > 100 ? '...' : ''}</small>` : ''}
                    </div>
                </td>
                <td>
                    <span class="badge bg-${status.class}">
                        <i class="${status.icon} me-1"></i>
                        ${status.label}
                    </span>
                </td>
                <td>
                    <span class="badge bg-${priority.class}">
                        <i class="${priority.icon} me-1"></i>
                        ${priority.label}
                    </span>
                </td>
                <td>
                    ${customer ? `<span class="text-primary">${this.escapeHtml(customer.name)}</span>` : '<span class="text-muted">-</span>'}
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="progress me-2" style="width: 60px; height: 8px;">
                            <div class="progress-bar bg-${progressClass}" style="width: ${progress}%"></div>
                        </div>
                        <small class="text-muted">${progress}%</small>
                    </div>
                </td>
                <td class="${dueDateClass}">
                    ${dueDateDisplay}
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="duraluxProjects.viewProject(${project.id})" title="Ver Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="duraluxProjects.editProject(${project.id})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="duraluxProjects.manageProjectTasks(${project.id})" title="Gerenciar Tarefas">
                            <i class="fas fa-tasks"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="duraluxProjects.deleteProject(${project.id})" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }

    /**
     * Configura filtros
     */
    setupFiltrars() {
        // Popula filtro de status
        const statusFiltrar = document.getElementById('filterStatus');
        if (statusFiltrar) {
            Object.keys(this.projectStatus).forEach(key => {
                const option = document.createElement('option');
                option.value = key;
                option.textContent = this.projectStatus[key].label;
                statusFiltrar.appendChild(option);
            });
        }

        // Popula filtro de prioridade
        const priorityFiltrar = document.getElementById('filterPriority');
        if (priorityFiltrar) {
            Object.keys(this.priorities).forEach(key => {
                const option = document.createElement('option');
                option.value = key;
                option.textContent = this.priorities[key].label;
                priorityFiltrar.appendChild(option);
            });
        }
    }

    /**
     * Aplica filtros
     */
    applyFiltrars() {
        this.currentFiltrars = {};

        // Filtro de busca
        const search = document.getElementById('searchProjects')?.value?.trim();
        if (search) {
            this.currentFiltrars.search = search;
        }

        // Filtro de status
        const status = document.getElementById('filterStatus')?.value;
        if (status) {
            this.currentFiltrars.status = status;
        }

        // Filtro de prioridade  
        const priority = document.getElementById('filterPriority')?.value;
        if (priority) {
            this.currentFiltrars.priority = priority;
        }

        // Filtro de cliente
        const customer = document.getElementById('filterCustomer')?.value;
        if (customer) {
            this.currentFiltrars.customer_id = customer;
        }

        this.currentPage = 1;
        this.loadProjects();
    }

    /**
     * Handle search input
     */
    handleSearch(event) {
        // Debounce search
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => {
            this.applyFiltrars();
        }, 500);
    }

    /**
     * Handle sorting
     */
    handleSort(event) {
        const field = event.target.dataset.sort;
        if (!field) return;

        if (this.currentSort.field === field) {
            this.currentSort.direction = this.currentSort.direction === 'asc' ? 'desc' : 'asc';
        } else {
            this.currentSort.field = field;
            this.currentSort.direction = 'asc';
        }

        this.updateSortHeaders();
        this.loadProjects();
    }

    /**
     * Atualiza headers de ordenação
     */
    updateSortHeaders() {
        document.querySelectorAll('.sort-header').forEach(header => {
            const field = header.dataset.sort;
            const icon = header.querySelector('.sort-icon');
            
            if (field === this.currentSort.field) {
                icon.className = `sort-icon fas fa-sort-${this.currentSort.direction === 'asc' ? 'up' : 'down'}`;
                header.classList.add('sorted');
            } else {
                icon.className = 'sort-icon fas fa-sort';
                header.classList.remove('sorted');
            }
        });
    }

    /**
     * Configura paginação
     */
    setupPagination() {
        // Event listeners para controles de paginação
        document.getElementById('btnPrevPage')?.addEventListener('click', () => {
            if (this.currentPage > 1) {
                this.currentPage--;
                this.loadProjects();
            }
        });

        document.getElementById('btnNextPage')?.addEventListener('click', () => {
            this.currentPage++;
            this.loadProjects();
        });

        // Items per page
        document.getElementById('itemsPerPage')?.addEventListener('change', (e) => {
            this.itemsPerPage = parseInt(e.target.value);
            this.currentPage = 1;
            this.loadProjects();
        });
    }

    /**
     * Atualiza controles de paginação
     */
    updatePagination(pagination) {
        const { current_page, total_pages, total_items } = pagination;
        
        // Atualiza texto de informação
        const info = document.getElementById('paginationInfo');
        if (info) {
            const start = ((current_page - 1) * this.itemsPerPage) + 1;
            const end = Math.min(start + this.itemsPerPage - 1, total_items);
            info.textContent = `Exibindo ${start}-${end} de ${total_items} projetos`;
        }

        // Atualiza botões
        const btnPrev = document.getElementById('btnPrevPage');
        const btnNext = document.getElementById('btnNextPage');
        
        if (btnPrev) {
            btnPrev.disabled = current_page <= 1;
        }
        
        if (btnNext) {
            btnNext.disabled = current_page >= total_pages;
        }

        // Atualiza números das páginas
        this.renderPaginationNumbers(current_page, total_pages);
    }

    /**
     * Renderiza números da paginação
     */
    renderPaginationNumbers(currentPage, totalPages) {
        const container = document.getElementById('paginationNumbers');
        if (!container) return;

        let html = '';
        
        // Lógica para mostrar páginas relevantes
        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(totalPages, currentPage + 2);
        
        if (endPage - startPage < 4 && totalPages > 5) {
            if (startPage === 1) {
                endPage = Math.min(5, totalPages);
            } else {
                startPage = Math.max(1, endPage - 4);
            }
        }

        // Primeira página
        if (startPage > 1) {
            html += `<button class="btn btn-outline-primary btn-sm page-btn" data-page="1">1</button>`;
            if (startPage > 2) {
                html += `<span class="px-2">...</span>`;
            }
        }

        // Páginas do meio
        for (let i = startPage; i <= endPage; i++) {
            const active = i === currentPage ? 'btn-primary' : 'btn-outline-primary';
            html += `<button class="btn ${active} btn-sm page-btn" data-page="${i}">${i}</button>`;
        }

        // Última página
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                html += `<span class="px-2">...</span>`;
            }
            html += `<button class="btn btn-outline-primary btn-sm page-btn" data-page="${totalPages}">${totalPages}</button>`;
        }

        container.innerHTML = html;

        // Event listeners para os botões de página
        container.querySelectorAll('.page-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.currentPage = parseInt(e.target.dataset.page);
                this.loadProjects();
            });
        });
    }

    /**
     * Exibe modal de projeto
     */
    showProjectModal(project = null) {
        this.selectedProject = project;
        
        const modal = new bootstrap.Modal(document.getElementById('projectModal'));
        const form = document.getElementById('projectForm');
        const title = document.getElementById('projectModalLabel');
        
        if (project) {
            title.textContent = 'Editar Projeto';
            this.populateProjectForm(project);
        } else {
            title.textContent = 'Novo Projeto';
            form.reset();
            // Define valores padrão
            document.getElementById('projectStatus').value = 'planning';
            document.getElementById('projectPriority').value = 'medium';
        }
        
        modal.show();
    }

    /**
     * Popula formulário do projeto
     */
    populateProjectForm(project) {
        document.getElementById('projectName').value = project.name || '';
        document.getElementById('projectDescription').value = project.description || '';
        document.getElementById('projectCustomerId').value = project.customer_id || '';
        document.getElementById('projectStatus').value = project.status || 'planning';
        document.getElementById('projectPriority').value = project.priority || 'medium';
        document.getElementById('projectBudget').value = project.budget || '';
        document.getElementById('projectStartDate').value = project.start_date || '';
        document.getElementById('projectDueDate').value = project.due_date || '';
    }

    /**
     * Handle project form submit
     */
    async handleProjectSubmit(event) {
        event.preventDefault();
        
        const formData = new FormData(event.target);
        const projectData = Object.fromEntries(formData.entries());
        
        try {
            let url, method;
            
            if (this.selectedProject) {
                url = this.endpoints.projects.update(this.selectedProject.id);
                method = 'PUT';
            } else {
                url = this.endpoints.projects.create;
                method = 'POST';
            }

            const response = await fetch(url, {
                method: 'POST', // Sempre POST, o método real vai no parâmetro
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(projectData)
            });

            const result = await response.json();
            
            if (result.success) {
                this.showNotification(
                    this.selectedProject ? 'Projeto atualizado com sucesso!' : 'Projeto criado com sucesso!',
                    'success'
                );
                
                // Fecha modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('projectModal'));
                modal.hide();
                
                // Recarrega lista
                await this.loadProjects();
                await this.loadStats();
                
            } else {
                throw new Error(result.message || 'Erro ao salvar projeto');
            }
            
        } catch (error) {
            console.error('Erro ao salvar projeto:', error);
            this.showNotification('Erro ao salvar projeto: ' + error.message, 'error');
        }
    }

    /**
     * Ver detalhes do projeto
     */
    async viewProject(projectId) {
        try {
            const response = await fetch(this.endpoints.projects.view(projectId));
            const result = await response.json();
            
            if (result.success) {
                this.showProjectDetails(result.data);
            } else {
                throw new Error(result.message);
            }
            
        } catch (error) {
            console.error('Erro ao carregar projeto:', error);
            this.showNotification('Erro ao carregar detalhes do projeto', 'error');
        }
    }

    /**
     * Exibe detalhes do projeto
     */
    showProjectDetails(project) {
        const modal = new bootstrap.Modal(document.getElementById('projectDetailsModal'));
        
        // Popula dados do projeto
        document.getElementById('detailProjectName').textContent = project.name;
        document.getElementById('detailProjectDescription').textContent = project.description || 'Sem descrição';
        
        const status = this.projectStatus[project.status];
        const priority = this.priorities[project.priority];
        
        document.getElementById('detailProjectStatus').innerHTML = `
            <span class="badge bg-${status.class}">
                <i class="${status.icon} me-1"></i>
                ${status.label}
            </span>
        `;
        
        document.getElementById('detailProjectPriority').innerHTML = `
            <span class="badge bg-${priority.class}">
                <i class="${priority.icon} me-1"></i>
                ${priority.label}
            </span>
        `;
        
        // Cliente
        const customer = this.customers.find(c => c.id == project.customer_id);
        document.getElementById('detailProjectCustomer').textContent = customer ? customer.name : 'Não definido';
        
        // Datas
        document.getElementById('detailProjectStartDate').textContent = 
            project.start_date ? new Intl.DateTimeFormat('pt-BR').format(new Date(project.start_date)) : 'Não definida';
        document.getElementById('detailProjectDueDate').textContent = 
            project.due_date ? new Intl.DateTimeFormat('pt-BR').format(new Date(project.due_date)) : 'Não definida';
        
        // Orçamento
        document.getElementById('detailProjectBudget').textContent = 
            project.budget ? `R$ ${parseFloat(project.budget).toLocaleString('pt-BR', {minimumFractionDigits: 2})}` : 'Não definido';
        
        // Progresso
        const progress = project.progress || 0;
        document.getElementById('detailProjectProgress').innerHTML = `
            <div class="progress mb-1">
                <div class="progress-bar bg-${progress >= 75 ? 'success' : progress >= 50 ? 'info' : 'warning'}" 
                     style="width: ${progress}%"></div>
            </div>
            <small class="text-muted">${progress}% concluído</small>
        `;
        
        modal.show();
    }

    /**
     * Edita projeto
     */
    editProject(projectId) {
        const project = this.projects.find(p => p.id == projectId);
        if (project) {
            this.showProjectModal(project);
        }
    }

    /**
     * Gerencia tarefas do projeto
     */
    async manageProjectTasks(projectId) {
        this.selectedProject = this.projects.find(p => p.id == projectId);
        
        if (!this.selectedProject) return;
        
        // Carrega tarefas do projeto
        await this.loadProjectTasks(projectId);
        
        // Exibe modal de tarefas
        const modal = new bootstrap.Modal(document.getElementById('projectTasksModal'));
        document.getElementById('taskModalProjectName').textContent = this.selectedProject.name;
        
        modal.show();
    }

    /**
     * Carrega tarefas do projeto
     */
    async loadProjectTasks(projectId) {
        try {
            const response = await fetch(this.endpoints.tasks.list(projectId));
            const result = await response.json();
            
            if (result.success) {
                this.renderProjectTasks(result.data || []);
            } else {
                throw new Error(result.message);
            }
            
        } catch (error) {
            console.error('Erro ao carregar tarefas:', error);
            this.showNotification('Erro ao carregar tarefas do projeto', 'error');
        }
    }

    /**
     * Renderiza tarefas do projeto
     */
    renderProjectTasks(tasks) {
        const container = document.getElementById('projectTasksList');
        
        if (tasks.length === 0) {
            container.innerHTML = `
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-tasks fa-2x mb-2"></i>
                    <p>Nenhuma tarefa encontrada</p>
                </div>
            `;
            return;
        }

        container.innerHTML = tasks.map(task => this.renderTaskItem(task)).join('');
    }

    /**
     * Renderiza item de tarefa
     */
    renderTaskItem(task) {
        const status = this.taskStatus[task.status];
        const priority = this.priorities[task.priority];
        const dueDate = task.due_date ? new Intl.DateTimeFormat('pt-BR').format(new Date(task.due_date)) : '';
        
        return `
            <div class="card mb-2 task-card" data-task-id="${task.id}">
                <div class="card-body py-2">
                    <div class="row align-items-center">
                        <div class="col">
                            <h6 class="mb-1">${this.escapeHtml(task.title)}</h6>
                            ${task.description ? `<small class="text-muted">${this.escapeHtml(task.description)}</small>` : ''}
                        </div>
                        <div class="col-auto">
                            <span class="badge bg-${status.class} me-1">
                                <i class="${status.icon} me-1"></i>
                                ${status.label}
                            </span>
                            <span class="badge bg-${priority.class}">
                                <i class="${priority.icon} me-1"></i>
                                ${priority.label}
                            </span>
                        </div>
                        <div class="col-auto">
                            ${dueDate ? `<small class="text-muted"><i class="fas fa-calendar me-1"></i>${dueDate}</small>` : ''}
                        </div>
                        <div class="col-auto">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="duraluxProjects.editTask(${task.id})" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-outline-success" onclick="duraluxProjects.toggleTaskStatus(${task.id})" title="Alterar Status">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="btn btn-outline-danger" onclick="duraluxProjects.deleteTask(${task.id})" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Exibe modal de nova tarefa
     */
    showTaskModal(task = null) {
        const modal = new bootstrap.Modal(document.getElementById('taskModal'));
        const form = document.getElementById('taskForm');
        const title = document.getElementById('taskModalLabel');
        
        if (task) {
            title.textContent = 'Editar Tarefa';
            this.populateTaskForm(task);
        } else {
            title.textContent = 'Nova Tarefa';
            form.reset();
            document.getElementById('taskStatus').value = 'pending';
            document.getElementById('taskPriority').value = 'medium';
        }
        
        modal.show();
    }

    /**
     * Popula formulário de tarefa
     */
    populateTaskForm(task) {
        document.getElementById('taskTitle').value = task.title || '';
        document.getElementById('taskDescription').value = task.description || '';
        document.getElementById('taskStatus').value = task.status || 'pending';
        document.getElementById('taskPriority').value = task.priority || 'medium';
        document.getElementById('taskDueDate').value = task.due_date || '';
        document.getElementById('taskEstimatedHours').value = task.estimated_hours || '';
    }

    /**
     * Handle task form submit
     */
    async handleTaskSubmit(event) {
        event.preventDefault();
        
        const formData = new FormData(event.target);
        const taskData = Object.fromEntries(formData.entries());
        taskData.project_id = this.selectedProject.id;
        
        try {
            const response = await fetch(this.endpoints.tasks.create, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(taskData)
            });

            const result = await response.json();
            
            if (result.success) {
                this.showNotification('Tarefa criada com sucesso!', 'success');
                
                // Fecha modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('taskModal'));
                modal.hide();
                
                // Recarrega tarefas
                await this.loadProjectTasks(this.selectedProject.id);
                await this.loadProjects(); // Para atualizar progresso
                
            } else {
                throw new Error(result.message || 'Erro ao salvar tarefa');
            }
            
        } catch (error) {
            console.error('Erro ao salvar tarefa:', error);
            this.showNotification('Erro ao salvar tarefa: ' + error.message, 'error');
        }
    }

    /**
     * Exclui projeto
     */
    async deleteProject(projectId) {
        if (!confirm('Tem certeza que deseja excluir este projeto? Esta ação não pode ser desfeita.')) {
            return;
        }
        
        try {
            const response = await fetch(this.endpoints.projects.delete(projectId), {
                method: 'POST' // O método real DELETE vai no parâmetro da URL
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification('Projeto excluído com sucesso!', 'success');
                await this.loadProjects();
                await this.loadStats();
            } else {
                throw new Error(result.message || 'Erro ao excluir projeto');
            }
            
        } catch (error) {
            console.error('Erro ao excluir projeto:', error);
            this.showNotification('Erro ao excluir projeto', 'error');
        }
    }

    /**
     * Refresh projetos
     */
    async refreshProjects(showNotification = true) {
        try {
            await this.loadProjects();
            await this.loadStats();
            
            if (showNotification) {
                this.showNotification('Lista de projetos atualizada!', 'success');
            }
        } catch (error) {
            console.error('Erro ao atualizar projetos:', error);
            if (showNotification) {
                this.showNotification('Erro ao atualizar projetos', 'error');
            }
        }
    }

    /**
     * Seleção em massa
     */
    handleSelectAll(event) {
        const checkboxes = document.querySelectorAll('.project-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = event.target.checked;
        });
        this.updateBulkActions();
    }

    /**
     * Atualiza ações em massa
     */
    updateBulkActions() {
        const selectedCheckboxes = document.querySelectorAll('.project-checkbox:checked');
        const bulkActions = document.getElementById('bulkActions');
        
        if (bulkActions) {
            bulkActions.style.display = selectedCheckboxes.length > 0 ? 'block' : 'none';
        }
    }

    /**
     * Exclusão em massa
     */
    async handleBulkDelete() {
        const selectedCheckboxes = document.querySelectorAll('.project-checkbox:checked');
        const selectedIds = Array.from(selectedCheckboxes).map(cb => cb.value);
        
        if (selectedIds.length === 0) {
            this.showNotification('Selecione pelo menos um projeto para excluir', 'warning');
            return;
        }
        
        if (!confirm(`Tem certeza que deseja excluir ${selectedIds.length} projeto(s)? Esta ação não pode ser desfeita.`)) {
            return;
        }
        
        try {
            const promises = selectedIds.map(id => 
                fetch(this.endpoints.projects.delete(id), { method: 'POST' })
            );
            
            await Promise.all(promises);
            
            this.showNotification(`${selectedIds.length} projeto(s) excluído(s) com sucesso!`, 'success');
            await this.loadProjects();
            await this.loadStats();
            
        } catch (error) {
            console.error('Erro na exclusão em massa:', error);
            this.showNotification('Erro ao excluir projetos', 'error');
        }
    }

    /**
     * Utility: Escape HTML
     */
    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text?.replace(/[&<>"']/g, m => map[m]) || '';
    }

    /**
     * Exibe notificação
     */
    showNotification(message, type = 'info') {
        // Remove notificações antigas
        document.querySelectorAll('.duralux-notification').forEach(n => n.remove());
        
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show duralux-notification`;
        notification.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Remove automaticamente após 5 segundos
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }

    /**
     * Cleanup - remove timers ao sair da página
     */
    destroy() {
        if (this.autoRefreshTimer) {
            clearInterval(this.autoRefreshTimer);
        }
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }
    }
}

// Inicializa o sistema quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    window.duraluxProjects = new DuraluxProjects();
});

// Cleanup ao sair da página
window.addEventListener('beforeunload', function() {
    if (window.duraluxProjects) {
        window.duraluxProjects.destroy();
    }
});