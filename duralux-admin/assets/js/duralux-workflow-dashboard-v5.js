/**
 * DURALUX CRM - Workflow Automation Painel de Controle v5.0
 * Sistema avançado de automação de processos
 * 
 * Features:
 * - Interface visual para criação de workflows
 * - Drag & Drop para actions e conditions
 * - Painel de Controle de monitoramento em tempo real
 * - Templates pré-configurados
 * - Histórico de execuções
 * 
 * @author Duralux Development Team
 * @version 5.0.0
 */

class DuraluxWorkflowPainel de Controle {
    constructor(options = {}) {
        this.options = {
            apiBase: '/duralux/backend/api/router.php',
            container: '#workflow-dashboard',
            updateInterval: 30000, // 30 segundos
            enableRealTime: true,
            ...options
        };
        
        this.workflows = [];
        this.templates = [];
        this.executions = [];
        this.currentWorkflow = null;
        this.charts = {};
        this.updateTimer = null;
        
        this.init();
    }
    
    /**
     * Inicializar workflow builder
     */
    initializeWorkflowBuilder() {
        console.log('🔧 Inicializando Workflow Builder...');
        
        const canvas = document.getElementById('workflow-canvas');
        if (canvas) {
            // Configurar drag and drop
            this.setupDragAndDrop(canvas);
            
            // Configurar eventos do canvas
            canvas.addEventListener('drop', this.handleCanvasDrop.bind(this));
            canvas.addEventListener('dragover', (e) => e.preventDefault());
        }
        
        // Configurar sidebar de componentes
        this.setupComponentSidebar();
    }
    
    /**
     * Carregar workflows
     */
    async loadWorkflows() {
        try {
            const response = await this.apiRequest('get_workflows', {}, 'GET');
            
            if (response.success) {
                this.workflows = response.workflows || [];
                this.renderWorkflowsList();
                return this.workflows;
            } else {
                throw new Error(response.message || 'Erro ao carregar workflows');
            }
        } catch (error) {
            console.error('Erro ao carregar workflows:', error);
            this.showNotification('Erro ao carregar workflows', 'error');
            return [];
        }
    }
    
    /**
     * Atualizar estatísticas
     */
    async updateStats() {
        try {
            const response = await this.apiRequest('get_workflow_stats', {}, 'GET');
            
            if (response.success && response.stats) {
                const stats = response.stats;
                
                // Atualizar contadores na interface
                this.updateStatDisplay('totalWorkflows', stats.total_workflows || 0);
                this.updateStatDisplay('activeWorkflows', stats.active_workflows || 0);
                this.updateStatDisplay('avgExecutions', stats.avg_executions || 0);
                this.updateStatDisplay('avgSuccessRate', (stats.success_rate || 0) + '%');
                
                return stats;
            } else {
                throw new Error(response.message || 'Erro ao carregar estatísticas');
            }
        } catch (error) {
            console.error('Erro ao atualizar estatísticas:', error);
            return null;
        }
    }
    
    /**
     * Atualizar display de estatística
     */
    updateStatDisplay(elementId, value) {
        const element = document.getElementById(elementId);
        if (element) {
            element.textContent = value;
            element.classList.add('stat-updated');
            setTimeout(() => element.classList.remove('stat-updated'), 500);
        }
    }
    
    /**
     * Inicializar dashboard
     */
    async init() {
        console.log('🚀 Inicializando Duralux Workflow Painel de Controle v5.0...');
        
        try {
            this.setupEventListeners();
            this.initializeWorkflowBuilder();
            await this.loadPainel de ControleData();
            this.initializeCharts();
            this.startAutoUpdate();
            
            console.log('✅ Workflow Painel de Controle inicializado com sucesso!');
        } catch (error) {
            console.error('❌ Erro ao inicializar dashboard:', error);
            this.showNotification('Erro ao carregar dashboard de workflows', 'error');
        }
    }
    
    /**
     * Configurar event listeners
     */
    setupEventListeners() {
        // Botões de ação
        document.getElementById('createWorkflowBtn')?.addEventListener('click', () => {
            this.showWorkflowModal();
        });
        
        document.getElementById('refreshDataBtn')?.addEventListener('click', () => {
            this.loadPainel de ControleData();
        });
        
        document.getElementById('saveWorkflowBtn')?.addEventListener('click', () => {
            this.saveWorkflow();
        });
        
        // Modal de workflow
        document.getElementById('workflowModal')?.addEventListener('hidden.bs.modal', () => {
            this.resetWorkflowModal();
        });
        
        // Navegação de abas
        document.querySelectorAll('[data-tab]').forEach(tab => {
            tab.addEventListener('click', (e) => {
                this.switchTab(e.target.dataset.tab);
            });
        });
        
        // Filtros
        document.getElementById('workflowFiltrar')?.addEventListener('change', (e) => {
            this.filterWorkflows(e.target.value);
        });
        
        document.getElementById('executionFiltrar')?.addEventListener('change', (e) => {
            this.filterExecutions(e.target.value);
        });
    }
    
    /**
     * Carregar dados do dashboard
     */
    async loadPainel de ControleData() {
        try {
            this.showLoading(true);
            
            // Carregar dados em paralelo
            const [
                dashboardData,
                workflows,
                templates,
                executions
            ] = await Promise.all([
                this.apiRequest('get_workflow_dashboard'),
                this.apiRequest('get_workflows'),
                this.apiRequest('get_templates'),
                this.apiRequest('get_executions', { limit: 50 })
            ]);
            
            // Processar dados
            this.workflows = workflows.data || [];
            this.templates = templates.data || [];
            this.executions = executions.data || [];
            
            // Renderizar componentes
            this.renderPainel de ControleStats(dashboardData.data);
            this.renderWorkflowsList();
            this.renderExecutionsList();
            this.renderTemplatesList();
            this.updateCharts(dashboardData.data);
            
        } catch (error) {
            console.error('Erro ao carregar dados:', error);
            this.showNotification('Erro ao carregar dados do dashboard', 'error');
        } finally {
            this.showLoading(false);
        }
    }
    
    /**
     * Renderizar estatísticas do dashboard
     */
    renderPainel de ControleStats(data) {
        if (!data || !data.stats) return;
        
        const stats = data.stats;
        
        // Atualizar cards de estatísticas
        this.updateStatCard('totalWorkflows', stats.total_workflows || 0);
        this.updateStatCard('activeWorkflows', stats.active_workflows || 0);
        this.updateStatCard('avgExecutions', Math.round(stats.avg_executions || 0));
        this.updateStatCard('avgSuccessRate', Math.round(stats.avg_success_rate || 0) + '%');
        
        // Atualizar workflows mais ativos
        this.renderTopWorkflows(data.top_workflows || []);
        
        // Atualizar execuções recentes
        this.renderRecentExecutions(data.recent_executions || []);
    }
    
    /**
     * Renderizar lista de workflows
     */
    renderWorkflowsList() {
        const container = document.getElementById('workflowsList');
        if (!container) return;
        
        if (this.workflows.length === 0) {
            container.innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-robot fa-3x text-muted mb-3"></i>
                    <h5>Nenhum workflow encontrado</h5>
                    <p class="text-muted">Crie seu primeiro workflow para automatizar processos</p>
                    <button class="btn btn-primary" onclick="dashboard.showWorkflowModal()">
                        <i class="fas fa-plus"></i> Criar Workflow
                    </button>
                </div>
            `;
            return;
        }
        
        const workflowsHtml = this.workflows.map(workflow => {
            const statusClass = workflow.is_active ? 'success' : 'secondary';
            const statusText = workflow.is_active ? 'Ativo' : 'Inativo';
            const lastExecution = workflow.last_execution ? 
                new Date(workflow.last_execution).toLocaleDateString() : 'Nunca';
            
            return `
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card workflow-card h-100" data-workflow-id="${workflow.id}">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">${workflow.name}</h6>
                            <span class="badge bg-${statusClass}">${statusText}</span>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small">${workflow.description || 'Sem descrição'}</p>
                            
                            <div class="workflow-stats mb-3">
                                <div class="row text-center">
                                    <div class="col-6">
                                        <div class="stat-item">
                                            <div class="stat-number">${workflow.execution_count || 0}</div>
                                            <div class="stat-label">Execuções</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="stat-item">
                                            <div class="stat-number">${Math.round(workflow.success_rate || 0)}%</div>
                                            <div class="stat-label">Sucesso</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="workflow-info">
                                <small class="text-muted d-block">
                                    <i class="fas fa-clock"></i> Última execução: ${lastExecution}
                                </small>
                                <small class="text-muted d-block">
                                    <i class="fas fa-cogs"></i> Trigger: ${workflow.trigger_type}
                                </small>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="btn-group w-100" role="group">
                                <button class="btn btn-outline-primary btn-sm" 
                                        onclick="dashboard.editWorkflow(${workflow.id})" 
                                        title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-outline-success btn-sm" 
                                        onclick="dashboard.executeWorkflow(${workflow.id})" 
                                        title="Executar">
                                    <i class="fas fa-play"></i>
                                </button>
                                <button class="btn btn-outline-warning btn-sm" 
                                        onclick="dashboard.toggleWorkflow(${workflow.id})" 
                                        title="Ativar/Desativar">
                                    <i class="fas fa-power-off"></i>
                                </button>
                                <button class="btn btn-outline-danger btn-sm" 
                                        onclick="dashboard.deleteWorkflow(${workflow.id})" 
                                        title="Deletar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
        
        container.innerHTML = workflowsHtml;
    }
    
    /**
     * Renderizar lista de execuções
     */
    renderExecutionsList() {
        const container = document.getElementById('executionsList');
        if (!container) return;
        
        if (this.executions.length === 0) {
            container.innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-history fa-3x text-muted mb-3"></i>
                    <h5>Nenhuma execução encontrada</h5>
                    <p class="text-muted">As execuções de workflows aparecerão aqui</p>
                </div>
            `;
            return;
        }
        
        const executionsHtml = this.executions.map(execution => {
            const statusClass = this.getExecutionStatusClass(execution.status);
            const statusIcon = this.getExecutionStatusIcon(execution.status);
            const duration = this.calculateExecutionDuration(execution);
            
            return `
                <tr class="execution-row" data-execution-id="${execution.id}">
                    <td>
                        <div class="d-flex align-items-center">
                            <i class="${statusIcon} text-${statusClass} me-2"></i>
                            <div>
                                <strong>${execution.workflow_name}</strong>
                                <br><small class="text-muted">#${execution.id}</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-${statusClass}">${execution.status}</span>
                    </td>
                    <td>${new Date(execution.started_at).toLocaleString()}</td>
                    <td>${duration}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" 
                                onclick="dashboard.viewExecutionDetails(${execution.id})">
                            <i class="fas fa-eye"></i> Detalhes
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
        
        container.innerHTML = executionsHtml;
    }
    
    /**
     * Renderizar lista de templates
     */
    renderTemplatesList() {
        const container = document.getElementById('templatesList');
        if (!container) return;
        
        if (this.templates.length === 0) {
            container.innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-copy fa-3x text-muted mb-3"></i>
                    <h5>Nenhum template disponível</h5>
                    <p class="text-muted">Templates facilitam a criação de workflows</p>
                </div>
            `;
            return;
        }
        
        const templatesHtml = this.templates.map(template => {
            return `
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card template-card h-100">
                        <div class="card-body">
                            <h6 class="card-title">${template.name}</h6>
                            <p class="card-text text-muted">${template.description || 'Sem descrição'}</p>
                            <div class="template-meta">
                                <small class="text-muted">
                                    <i class="fas fa-tag"></i> ${template.category}
                                    <br>
                                    <i class="fas fa-chart-line"></i> Usado ${template.usage_count} vezes
                                </small>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-primary btn-sm w-100" 
                                    onclick="dashboard.useTemplate(${template.id})">
                                <i class="fas fa-magic"></i> Usar Template
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
        
        container.innerHTML = templatesHtml;
    }
    
    /**
     * Mostrar modal de workflow
     */
    showWorkflowModal(workflowId = null) {
        this.currentWorkflow = workflowId;
        
        if (workflowId) {
            // Editar workflow existente
            this.loadWorkflowForEdit(workflowId);
        } else {
            // Novo workflow
            this.resetWorkflowModal();
        }
        
        const modal = new bootstrap.Modal(document.getElementById('workflowModal'));
        modal.show();
    }
    
    /**
     * Salvar workflow
     */
    async saveWorkflow() {
        try {
            const formData = this.getWorkflowFormData();
            
            // Validar dados
            if (!this.validateWorkflowData(formData)) {
                return;
            }
            
            const action = this.currentWorkflow ? 'update_workflow' : 'create_workflow';
            const url = this.currentWorkflow ? 
                `${this.options.apiBase}?action=${action}&workflow_id=${this.currentWorkflow}` :
                `${this.options.apiBase}?action=${action}`;
            
            const result = await this.apiRequest(action, formData);
            
            if (result.success) {
                this.showNotification('Workflow salvo com sucesso!', 'success');
                bootstrap.Modal.getInstance(document.getElementById('workflowModal')).hide();
                await this.loadPainel de ControleData();
            } else {
                throw new Error(result.error || 'Erro ao salvar workflow');
            }
            
        } catch (error) {
            console.error('Erro ao salvar workflow:', error);
            this.showNotification('Erro ao salvar workflow: ' + error.message, 'error');
        }
    }
    
    /**
     * Executar workflow manualmente
     */
    async executeWorkflow(workflowId) {
        try {
            if (!confirm('Deseja executar este workflow?')) return;
            
            const result = await this.apiRequest('execute_workflow', {
                workflow_id: workflowId
            });
            
            if (result.success) {
                this.showNotification('Workflow executado com sucesso!', 'success');
                await this.loadPainel de ControleData();
            } else {
                throw new Error(result.error || 'Erro ao executar workflow');
            }
            
        } catch (error) {
            console.error('Erro ao executar workflow:', error);
            this.showNotification('Erro ao executar workflow: ' + error.message, 'error');
        }
    }
    
    /**
     * Ativar/desativar workflow
     */
    async toggleWorkflow(workflowId) {
        try {
            const workflow = this.workflows.find(w => w.id == workflowId);
            const newStatus = !workflow.is_active;
            
            const result = await this.apiRequest('toggle_workflow', {
                workflow_id: workflowId,
                active: newStatus
            });
            
            if (result.success) {
                const statusText = newStatus ? 'ativado' : 'desativado';
                this.showNotification(`Workflow ${statusText} com sucesso!`, 'success');
                await this.loadPainel de ControleData();
            } else {
                throw new Error(result.error || 'Erro ao alterar status');
            }
            
        } catch (error) {
            console.error('Erro ao alterar status:', error);
            this.showNotification('Erro ao alterar status: ' + error.message, 'error');
        }
    }
    
    /**
     * Deletar workflow
     */
    async deleteWorkflow(workflowId) {
        try {
            if (!confirm('Tem certeza que deseja deletar este workflow?')) return;
            
            const result = await this.apiRequest('delete_workflow', {
                workflow_id: workflowId
            });
            
            if (result.success) {
                this.showNotification('Workflow deletado com sucesso!', 'success');
                await this.loadPainel de ControleData();
            } else {
                throw new Error(result.error || 'Erro ao deletar workflow');
            }
            
        } catch (error) {
            console.error('Erro ao deletar workflow:', error);
            this.showNotification('Erro ao deletar workflow: ' + error.message, 'error');
        }
    }
    
    /**
     * Usar template
     */
    async useTemplate(templateId) {
        try {
            const template = this.templates.find(t => t.id == templateId);
            
            // Preencher modal com dados do template
            const templateData = template.template_data;
            
            document.getElementById('workflowName').value = `${template.name} - Cópia`;
            document.getElementById('workflowDescription').value = template.description || '';
            
            // Preencher outros campos conforme o template
            // ... implementar preenchimento baseado no template_data
            
            this.showWorkflowModal();
            
        } catch (error) {
            console.error('Erro ao usar template:', error);
            this.showNotification('Erro ao carregar template', 'error');
        }
    }
    
    /**
     * Ver detalhes da execução
     */
    async viewExecutionDetails(executionId) {
        try {
            const result = await this.apiRequest('get_execution_details', {
                execution_id: executionId
            });
            
            if (result.success) {
                this.showExecutionDetailsModal(result.data);
            } else {
                throw new Error(result.error || 'Erro ao carregar detalhes');
            }
            
        } catch (error) {
            console.error('Erro ao carregar detalhes:', error);
            this.showNotification('Erro ao carregar detalhes da execução', 'error');
        }
    }
    
    // ==========================================
    // MÉTODOS AUXILIARES
    // ==========================================
    
    async apiRequest(action, data = {}) {
        const url = `${this.options.apiBase}?action=${action}`;
        
        const options = {
            method: data && Object.keys(data).length > 0 ? 'POST' : 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        if (options.method === 'POST') {
            options.body = JSON.stringify(data);
        }
        
        const response = await fetch(url, options);
        return await response.json();
    }
    
    updateStatCard(id, value) {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = value;
            element.classList.add('stat-updated');
            setTimeout(() => element.classList.remove('stat-updated'), 500);
        }
    }
    
    getExecutionStatusClass(status) {
        const classes = {
            'completed': 'success',
            'running': 'primary',
            'failed': 'danger',
            'cancelled': 'warning',
            'pending': 'secondary'
        };
        return classes[status] || 'secondary';
    }
    
    getExecutionStatusIcon(status) {
        const icons = {
            'completed': 'fas fa-check-circle',
            'running': 'fas fa-spinner fa-spin',
            'failed': 'fas fa-exclamation-circle',
            'cancelled': 'fas fa-ban',
            'pending': 'fas fa-clock'
        };
        return icons[status] || 'fas fa-question-circle';
    }
    
    calculateExecutionDuration(execution) {
        if (!execution.completed_at) {
            return execution.status === 'running' ? 'Em execução...' : '--';
        }
        
        const start = new Date(execution.started_at);
        const end = new Date(execution.completed_at);
        const duration = Math.round((end - start) / 1000);
        
        return duration >= 60 ? `${Math.floor(duration / 60)}m ${duration % 60}s` : `${duration}s`;
    }
    
    showNotification(message, type = 'info') {
        // Implementar sistema de notificações
        console.log(`[${type.toUpperCase()}] ${message}`);
        
        // Toast notification
        const toastHtml = `
            <div class="toast" role="alert">
                <div class="toast-header bg-${type} text-white">
                    <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle me-2"></i>
                    <strong class="me-auto">Workflow System</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">${message}</div>
            </div>
        `;
        
        const toastContainer = document.getElementById('toastContainer') || this.createToastContainer();
        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        
        const toastElement = toastContainer.lastElementChild;
        const toast = new bootstrap.Toast(toastElement);
        toast.show();
        
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
    }
    
    createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = '1055';
        document.body.appendChild(container);
        return container;
    }
    
    showLoading(show) {
        const loader = document.getElementById('loadingOverlay');
        if (loader) {
            loader.style.display = show ? 'flex' : 'none';
        }
    }
    
    startAutoUpdate() {
        if (this.options.enableRealTime) {
            this.updateTimer = setInterval(() => {
                this.loadPainel de ControleData();
            }, this.options.updateInterval);
        }
    }
    
    /**
     * Configurar drag and drop
     */
    setupDragAndDrop(canvas) {
        canvas.addEventListener('dragover', (e) => {
            e.preventDefault();
            canvas.classList.add('drag-over');
        });
        
        canvas.addEventListener('dragleave', () => {
            canvas.classList.remove('drag-over');
        });
    }
    
    /**
     * Configurar sidebar de componentes
     */
    setupComponentSidebar() {
        const components = document.querySelectorAll('.workflow-component');
        components.forEach(component => {
            component.draggable = true;
            component.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('text/plain', component.dataset.type);
            });
        });
    }
    
    /**
     * Manipular drop no canvas
     */
    handleCanvasDrop(e) {
        e.preventDefault();
        const componentType = e.dataTransfer.getData('text/plain');
        const rect = e.currentTarget.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        this.addComponentToWorkflow(componentType, x, y);
        e.currentTarget.classList.remove('drag-over');
    }
    
    /**
     * Adicionar componente ao workflow
     */
    addComponentToWorkflow(type, x, y) {
        console.log(`Adicionando componente ${type} na posição (${x}, ${y})`);
        // Implementar lógica de adição de componente
    }
    
    /**
     * Renderizar lista de workflows
     */
    renderWorkflowsList() {
        const container = document.getElementById('workflowsList');
        if (!container) return;
        
        container.innerHTML = '';
        
        this.workflows.forEach(workflow => {
            const item = document.createElement('div');
            item.className = 'workflow-item';
            item.innerHTML = `
                <div class="workflow-info">
                    <h5>${workflow.name}</h5>
                    <p>${workflow.description || 'Sem descrição'}</p>
                    <span class="badge ${workflow.is_active ? 'bg-success' : 'bg-secondary'}">
                        ${workflow.is_active ? 'Ativo' : 'Inativo'}
                    </span>
                </div>
                <div class="workflow-actions">
                    <button class="btn btn-sm btn-outline-primary" onclick="dashboard.editWorkflow(${workflow.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-success" onclick="dashboard.executeWorkflow(${workflow.id})">
                        <i class="fas fa-play"></i>
                    </button>
                </div>
            `;
            container.appendChild(item);
        });
    }
    
    // Placeholders para métodos não implementados
    initializeCharts() { /* Implementar gráficos com Chart.js */ }
    updateCharts(data) { /* Atualizar gráficos */ }
    renderTopWorkflows(workflows) { /* Renderizar top workflows */ }
    renderRecentExecutions(executions) { /* Renderizar execuções recentes */ }
    switchTab(tabName) { /* Alternar abas */ }
    filterWorkflows(filter) { /* Filtrar workflows */ }
    filterExecutions(filter) { /* Filtrar execuções */ }
    resetWorkflowModal() { /* Reset do modal */ }
    loadWorkflowForEdit(id) { /* Carregar workflow para edição */ }
    getWorkflowFormData() { /* Obter dados do formulário */ }
    validateWorkflowData(data) { /* Validar dados */ return true; }
    showExecutionDetailsModal(data) { /* Mostrar modal de detalhes */ }
}

// Inicialização automática
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('workflow-dashboard')) {
        window.dashboard = new DuraluxWorkflowPainel de Controle();
    }
});

// Exportararar para uso global
window.DuraluxWorkflowPainel de Controle = DuraluxWorkflowPainel de Controle;