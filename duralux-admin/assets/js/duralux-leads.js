/**
 * Duralux Leads Management System - JavaScript Integration
 * Sistema de Gestão de Leads v1.1
 * 
 * Funcionalidades:
 * - CRUD completo de leads
 * - Pipeline de vendas dinâmico
 * - Conversão de leads para clientes
 * - Filtros avançados e busca em tempo real
 * - Estatísticas do pipeline
 * - Interface responsiva e moderna
 */

class DuraluxLeads {
    constructor() {
        this.apiUrl = '../backend/api/router.php';
        this.leads = [];
        this.currentPage = 1;
        this.itemsPerPage = 10;
        this.filters = {
            status: '',
            pipeline: '',
            source: '',
            search: ''
        };
        this.options = {};
        this.isLoading = false;

        // Elementos do DOM
        this.elements = {
            leadsTable: document.getElementById('leadsTable'),
            leadsTableBody: document.getElementById('leadsTableBody'),
            loadingOverlay: document.getElementById('loadingOverlay'),
            searchInput: document.getElementById('searchInput'),
            statusFilter: document.getElementById('statusFilter'),
            pipelineFilter: document.getElementById('pipelineFilter'),
            sourceFilter: document.getElementById('sourceFilter'),
            createLeadBtn: document.getElementById('createLeadBtn'),
            pagination: document.getElementById('pagination'),
            leadModal: document.getElementById('leadModal'),
            leadForm: document.getElementById('leadForm'),
            statsCards: document.querySelectorAll('.stats-card')
        };

        this.init();
    }

    /**
     * Inicializar o sistema
     */
    async init() {
        try {
            console.log('🚀 Inicializando Sistema de Leads Duralux v1.1');
            
            await this.loadOptions();
            await this.loadLeads();
            await this.loadStats();
            
            this.setupEventListeners();
            this.setupAutoRefresh();
            
            console.log('✅ Sistema de Leads inicializado com sucesso!');
            this.showToast('Sistema de Leads carregado com sucesso!', 'success');
            
        } catch (error) {
            console.error('❌ Erro ao inicializar sistema:', error);
            this.showToast('Erro ao carregar sistema de leads', 'error');
        }
    }

    /**
     * Configurar listeners de eventos
     */
    setupEventListeners() {
        // Busca em tempo real
        if (this.elements.searchInput) {
            this.elements.searchInput.addEventListener('input', 
                this.debounce((e) => {
                    this.filters.search = e.target.value;
                    this.loadLeads();
                }, 300)
            );
        }

        // Filtros
        if (this.elements.statusFilter) {
            this.elements.statusFilter.addEventListener('change', (e) => {
                this.filters.status = e.target.value;
                this.loadLeads();
            });
        }

        if (this.elements.pipelineFilter) {
            this.elements.pipelineFilter.addEventListener('change', (e) => {
                this.filters.pipeline = e.target.value;
                this.loadLeads();
            });
        }

        if (this.elements.sourceFilter) {
            this.elements.sourceFilter.addEventListener('change', (e) => {
                this.filters.source = e.target.value;
                this.loadLeads();
            });
        }

        // Botão de criar lead
        if (this.elements.createLeadBtn) {
            this.elements.createLeadBtn.addEventListener('click', () => this.showCreateModal());
        }

        // Formulário de lead
        if (this.elements.leadForm) {
            this.elements.leadForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.saveLead();
            });
        }

        // Eventos de teclado globais
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                this.showCreateModal();
            }
        });
    }

    /**
     * Configurar atualização automática
     */
    setupAutoRefresh() {
        // Atualizar estatísticas a cada 30 segundos
        setInterval(() => {
            this.loadStats();
        }, 30000);

        // Atualizar leads a cada 60 segundos
        setInterval(() => {
            this.loadLeads(false); // false = não mostrar loading
        }, 60000);
    }

    /**
     * Carregar opções de configuração
     */
    async loadOptions() {
        try {
            const response = await this.makeRequest('get_leads_options');
            if (response && !response.error) {
                this.options = response;
                this.populateFilterOptions();
            }
        } catch (error) {
            console.error('Erro ao carregar opções:', error);
        }
    }

    /**
     * Popular opções dos filtros
     */
    populateFilterOptions() {
        // Status
        if (this.elements.statusFilter && this.options.status) {
            this.elements.statusFilter.innerHTML = '<option value="">Todos os Status</option>';
            Object.entries(this.options.status).forEach(([key, value]) => {
                this.elements.statusFilter.innerHTML += `<option value="${key}">${value}</option>`;
            });
        }

        // Pipeline
        if (this.elements.pipelineFilter && this.options.pipeline_stages) {
            this.elements.pipelineFilter.innerHTML = '<option value="">Todas as Etapas</option>';
            Object.entries(this.options.pipeline_stages).forEach(([key, value]) => {
                this.elements.pipelineFilter.innerHTML += `<option value="${key}">${value}</option>`;
            });
        }

        // Source
        if (this.elements.sourceFilter && this.options.sources) {
            this.elements.sourceFilter.innerHTML = '<option value="">Todas as Fontes</option>';
            Object.entries(this.options.sources).forEach(([key, value]) => {
                this.elements.sourceFilter.innerHTML += `<option value="${key}">${value}</option>`;
            });
        }
    }

    /**
     * Carregar leads
     */
    async loadLeads(showLoading = true) {
        if (this.isLoading) return;
        
        try {
            if (showLoading) this.showLoading();
            this.isLoading = true;

            const params = {
                ...this.filters,
                page: this.currentPage,
                limit: this.itemsPerPage
            };

            const response = await this.makeRequest('get_leads', params);
            
            if (response && !response.error) {
                this.leads = response.leads || [];
                this.renderLeadsTable();
                this.renderPagination(response.pagination);
                
                // Atualizar estatísticas se disponível
                if (response.stats) {
                    this.updateStatsCards(response.stats);
                }
            } else {
                throw new Error(response?.error || 'Erro ao carregar leads');
            }

        } catch (error) {
            console.error('Erro ao carregar leads:', error);
            this.showToast('Erro ao carregar leads: ' + error.message, 'error');
        } finally {
            this.isLoading = false;
            if (showLoading) this.hideLoading();
        }
    }

    /**
     * Renderizar tabela de leads
     */
    renderLeadsTable() {
        if (!this.elements.leadsTableBody) return;

        if (this.leads.length === 0) {
            this.elements.leadsTableBody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-4">
                        <div class="empty-state">
                            <i class="feather-inbox fs-1 text-muted mb-3"></i>
                            <h5>Nenhum lead encontrado</h5>
                            <p class="text-muted">Não há leads para os filtros selecionados.</p>
                            <button class="btn btn-primary btn-sm" onclick="duraluxLeads.showCreateModal()">
                                <i class="feather-plus me-2"></i>Criar Primeiro Lead
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        this.elements.leadsTableBody.innerHTML = this.leads.map(lead => `
            <tr class="lead-row" data-lead-id="${lead.id}">
                <td>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input lead-checkbox" 
                               id="lead_${lead.id}" value="${lead.id}">
                        <label class="custom-control-label" for="lead_${lead.id}"></label>
                    </div>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="avatar-text avatar-md bg-primary text-white rounded me-3">
                            ${(lead.name || '').charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <h6 class="mb-0">${this.escapeHtml(lead.name || '')}</h6>
                            ${lead.company ? `<small class="text-muted">${this.escapeHtml(lead.company)}</small>` : ''}
                        </div>
                    </div>
                </td>
                <td>
                    ${lead.email ? `<a href="mailto:${lead.email}">${this.escapeHtml(lead.email)}</a>` : 
                      '<span class="text-muted">-</span>'}
                </td>
                <td>
                    <span class="badge source-badge bg-info">${this.getSourceLabel(lead.source)}</span>
                </td>
                <td>
                    ${lead.phone ? `<a href="tel:${lead.phone}">${this.escapeHtml(lead.phone)}</a>` : 
                      '<span class="text-muted">-</span>'}
                </td>
                <td>
                    <span class="pipeline-stage">${this.getPipelineLabel(lead.pipeline_stage)}</span>
                </td>
                <td>
                    <span class="lead-status badge ${this.getStatusClass(lead.status)}">
                        ${this.getStatusLabel(lead.status)}
                    </span>
                </td>
                <td>
                    <div class="lead-value">
                        ${lead.value > 0 ? this.formatCurrency(lead.value) : '-'}
                    </div>
                    ${lead.probability ? `<small class="text-muted">${lead.probability}% chance</small>` : ''}
                </td>
                <td>
                    <small class="text-muted">${this.formatDate(lead.created_at)}</small>
                </td>
                <td>
                    <div class="dropdown">
                        <button class="btn btn-light btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="feather-more-horizontal"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="duraluxLeads.viewLead(${lead.id})">
                                <i class="feather-eye me-2"></i>Ver Detalhes</a></li>
                            <li><a class="dropdown-item" href="#" onclick="duraluxLeads.editLead(${lead.id})">
                                <i class="feather-edit me-2"></i>Editar</a></li>
                            ${!lead.converted ? `
                                <li><a class="dropdown-item" href="#" onclick="duraluxLeads.convertLead(${lead.id})">
                                    <i class="feather-users me-2"></i>Converter Cliente</a></li>
                            ` : ''}
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="#" onclick="duraluxLeads.deleteLead(${lead.id})">
                                <i class="feather-trash-2 me-2"></i>Excluir</a></li>
                        </ul>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    /**
     * Renderizar paginação
     */
    renderPagination(pagination) {
        if (!this.elements.pagination || !pagination) return;

        const { page, pages, total } = pagination;
        
        if (pages <= 1) {
            this.elements.pagination.innerHTML = '';
            return;
        }

        let paginationHtml = `
            <nav aria-label="Paginação de leads">
                <ul class="pagination justify-content-center mb-0">
        `;

        // Botão anterior
        paginationHtml += `
            <li class="page-item ${page <= 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="duraluxLeads.changePage(${page - 1})" ${page <= 1 ? 'tabindex="-1"' : ''}>
                    <i class="feather-chevron-left"></i>
                </a>
            </li>
        `;

        // Páginas
        const startPage = Math.max(1, page - 2);
        const endPage = Math.min(pages, page + 2);

        if (startPage > 1) {
            paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="duraluxLeads.changePage(1)">1</a></li>`;
            if (startPage > 2) {
                paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            paginationHtml += `
                <li class="page-item ${i === page ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="duraluxLeads.changePage(${i})">${i}</a>
                </li>
            `;
        }

        if (endPage < pages) {
            if (endPage < pages - 1) {
                paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
            paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="duraluxLeads.changePage(${pages})">${pages}</a></li>`;
        }

        // Botão próximo
        paginationHtml += `
            <li class="page-item ${page >= pages ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="duraluxLeads.changePage(${page + 1})" ${page >= pages ? 'tabindex="-1"' : ''}>
                    <i class="feather-chevron-right"></i>
                </a>
            </li>
        `;

        paginationHtml += `
                </ul>
            </nav>
            <div class="mt-2 text-center">
                <small class="text-muted">
                    Mostrando ${((page - 1) * this.itemsPerPage) + 1} - ${Math.min(page * this.itemsPerPage, total)} de ${total} leads
                </small>
            </div>
        `;

        this.elements.pagination.innerHTML = paginationHtml;
    }

    /**
     * Carregar estatísticas
     */
    async loadStats() {
        try {
            const response = await this.makeRequest('get_pipeline_stats');
            if (response && !response.error) {
                this.updateStatsCards(response.stats);
            }
        } catch (error) {
            console.error('Erro ao carregar estatísticas:', error);
        }
    }

    /**
     * Atualizar cards de estatísticas
     */
    updateStatsCards(stats) {
        if (!stats) return;

        // Total de leads
        const totalElement = document.getElementById('totalLeads');
        if (totalElement) {
            totalElement.textContent = stats.total_leads || 0;
        }

        // Leads convertidos
        const convertedElement = document.getElementById('convertedLeads');
        if (convertedElement) {
            convertedElement.textContent = stats.converted_leads || 0;
        }

        // Taxa de conversão
        const rateElement = document.getElementById('conversionRate');
        if (rateElement) {
            rateElement.textContent = (stats.conversion_rate || 0) + '%';
        }

        // Valor total do pipeline
        const valueElement = document.getElementById('pipelineValue');
        if (valueElement) {
            valueElement.textContent = this.formatCurrency(stats.total_value || 0);
        }
    }

    /**
     * Mudar página
     */
    changePage(page) {
        if (page < 1 || this.isLoading) return;
        
        this.currentPage = page;
        this.loadLeads();
        
        // Scroll para o topo da tabela
        if (this.elements.leadsTable) {
            this.elements.leadsTable.scrollIntoView({ behavior: 'smooth' });
        }
    }

    /**
     * Mostrar modal de criação
     */
    showCreateModal() {
        this.currentLeadId = null;
        this.resetForm();
        
        // Configurar modal para criação
        const modalTitle = document.getElementById('leadModalTitle');
        if (modalTitle) {
            modalTitle.textContent = 'Novo Lead';
        }

        // Mostrar modal
        if (this.elements.leadModal) {
            const modal = new bootstrap.Modal(this.elements.leadModal);
            modal.show();
        }
    }

    /**
     * Editar lead
     */
    async editLead(leadId) {
        try {
            this.showLoading();
            
            const response = await this.makeRequest('get_lead', { id: leadId });
            
            if (response && !response.error) {
                this.currentLeadId = leadId;
                this.populateForm(response);
                
                const modalTitle = document.getElementById('leadModalTitle');
                if (modalTitle) {
                    modalTitle.textContent = 'Editar Lead';
                }

                if (this.elements.leadModal) {
                    const modal = new bootstrap.Modal(this.elements.leadModal);
                    modal.show();
                }
            } else {
                throw new Error(response?.error || 'Lead não encontrado');
            }

        } catch (error) {
            console.error('Erro ao carregar lead:', error);
            this.showToast('Erro ao carregar lead: ' + error.message, 'error');
        } finally {
            this.hideLoading();
        }
    }

    /**
     * Converter lead em cliente
     */
    async convertLead(leadId) {
        if (!confirm('Tem certeza que deseja converter este lead em cliente?')) return;

        try {
            this.showLoading();
            
            const response = await this.makeRequest('convert_lead', { id: leadId });
            
            if (response && !response.error) {
                this.showToast('Lead convertido em cliente com sucesso!', 'success');
                this.loadLeads();
                this.loadStats();
            } else {
                throw new Error(response?.error || 'Erro ao converter lead');
            }

        } catch (error) {
            console.error('Erro ao converter lead:', error);
            this.showToast('Erro ao converter lead: ' + error.message, 'error');
        } finally {
            this.hideLoading();
        }
    }

    /**
     * Excluir lead
     */
    async deleteLead(leadId) {
        if (!confirm('Tem certeza que deseja excluir este lead? Esta ação não pode ser desfeita.')) return;

        try {
            this.showLoading();
            
            const response = await this.makeRequest('delete_lead', { id: leadId });
            
            if (response && !response.error) {
                this.showToast('Lead excluído com sucesso!', 'success');
                this.loadLeads();
                this.loadStats();
            } else {
                throw new Error(response?.error || 'Erro ao excluir lead');
            }

        } catch (error) {
            console.error('Erro ao excluir lead:', error);
            this.showToast('Erro ao excluir lead: ' + error.message, 'error');
        } finally {
            this.hideLoading();
        }
    }

    /**
     * Salvar lead (criar ou editar)
     */
    async saveLead() {
        try {
            const formData = this.getFormData();
            
            if (!this.validateForm(formData)) return;

            this.showLoading();

            const action = this.currentLeadId ? 'update_lead' : 'create_lead';
            const data = this.currentLeadId ? { ...formData, id: this.currentLeadId } : formData;

            const response = await this.makeRequest(action, data);
            
            if (response && !response.error) {
                const message = this.currentLeadId ? 'Lead atualizado com sucesso!' : 'Lead criado com sucesso!';
                this.showToast(message, 'success');
                
                // Fechar modal
                const modal = bootstrap.Modal.getInstance(this.elements.leadModal);
                if (modal) modal.hide();
                
                this.loadLeads();
                this.loadStats();
            } else {
                throw new Error(response?.error || 'Erro ao salvar lead');
            }

        } catch (error) {
            console.error('Erro ao salvar lead:', error);
            this.showToast('Erro ao salvar lead: ' + error.message, 'error');
        } finally {
            this.hideLoading();
        }
    }

    /**
     * Obter dados do formulário
     */
    getFormData() {
        const form = this.elements.leadForm;
        if (!form) return {};

        return {
            name: form.name?.value || '',
            email: form.email?.value || '',
            phone: form.phone?.value || '',
            company: form.company?.value || '',
            position: form.position?.value || '',
            source: form.source?.value || 'website',
            status: form.status?.value || 'new',
            pipeline_stage: form.pipeline_stage?.value || 'prospect',
            value: parseFloat(form.value?.value || 0),
            probability: parseInt(form.probability?.value || 25),
            notes: form.notes?.value || '',
            next_contact_date: form.next_contact_date?.value || ''
        };
    }

    /**
     * Validar formulário
     */
    validateForm(data) {
        const errors = [];

        if (!data.name?.trim()) {
            errors.push('Nome é obrigatório');
        }

        if (data.email && !this.isValidEmail(data.email)) {
            errors.push('Email inválido');
        }

        if (data.value < 0) {
            errors.push('Valor não pode ser negativo');
        }

        if (data.probability < 0 || data.probability > 100) {
            errors.push('Probabilidade deve estar entre 0% e 100%');
        }

        if (errors.length > 0) {
            this.showToast('Erros de validação:\n' + errors.join('\n'), 'error');
            return false;
        }

        return true;
    }

    /**
     * Popular formulário com dados do lead
     */
    populateForm(lead) {
        const form = this.elements.leadForm;
        if (!form || !lead) return;

        if (form.name) form.name.value = lead.name || '';
        if (form.email) form.email.value = lead.email || '';
        if (form.phone) form.phone.value = lead.phone || '';
        if (form.company) form.company.value = lead.company || '';
        if (form.position) form.position.value = lead.position || '';
        if (form.source) form.source.value = lead.source || 'website';
        if (form.status) form.status.value = lead.status || 'new';
        if (form.pipeline_stage) form.pipeline_stage.value = lead.pipeline_stage || 'prospect';
        if (form.value) form.value.value = lead.value || 0;
        if (form.probability) form.probability.value = lead.probability || 25;
        if (form.notes) form.notes.value = lead.notes || '';
        if (form.next_contact_date) form.next_contact_date.value = lead.next_contact_date || '';
    }

    /**
     * Resetar formulário
     */
    resetForm() {
        if (this.elements.leadForm) {
            this.elements.leadForm.reset();
        }
    }

    // ========== Métodos Utilitários ==========

    /**
     * Fazer requisição para API
     */
    async makeRequest(action, data = {}) {
        const requestData = {
            action: action,
            ...data
        };

        const response = await fetch(this.apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(requestData)
        });

        if (!response.ok) {
            throw new Error(`Erro HTTP: ${response.status}`);
        }

        return await response.json();
    }

    /**
     * Mostrar loading
     */
    showLoading() {
        if (this.elements.loadingOverlay) {
            this.elements.loadingOverlay.style.display = 'flex';
        }
    }

    /**
     * Esconder loading
     */
    hideLoading() {
        if (this.elements.loadingOverlay) {
            this.elements.loadingOverlay.style.display = 'none';
        }
    }

    /**
     * Mostrar toast
     */
    showToast(message, type = 'info') {
        // Criar elemento toast
        const toastHtml = `
            <div class="toast align-items-center text-white bg-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'info'} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="feather-${type === 'error' ? 'x-circle' : type === 'success' ? 'check-circle' : 'info'} me-2"></i>
                        ${this.escapeHtml(message)}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;

        // Adicionar ao container
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            document.body.appendChild(container);
        }

        const toastElement = document.createElement('div');
        toastElement.innerHTML = toastHtml;
        const toast = toastElement.firstElementChild;
        
        container.appendChild(toast);

        // Mostrar toast
        const bsToast = new bootstrap.Toast(toast, { autohide: true, delay: 5000 });
        bsToast.show();

        // Remover após ocultar
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }

    /**
     * Debounce para eventos
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Escape HTML
     */
    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text ? text.replace(/[&<>"']/g, m => map[m]) : '';
    }

    /**
     * Validar email
     */
    isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    /**
     * Formatar moeda
     */
    formatCurrency(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value || 0);
    }

    /**
     * Formatar data
     */
    formatDate(dateString) {
        if (!dateString) return '-';
        
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        }).format(date);
    }

    /**
     * Obter label do status
     */
    getStatusLabel(status) {
        return this.options.status?.[status] || status;
    }

    /**
     * Obter classe do status
     */
    getStatusClass(status) {
        const classes = {
            'new': 'bg-primary',
            'contacted': 'bg-info',
            'qualified': 'bg-success',
            'proposal': 'bg-warning',
            'negotiation': 'bg-secondary',
            'converted': 'bg-success',
            'lost': 'bg-danger'
        };
        return classes[status] || 'bg-secondary';
    }

    /**
     * Obter label do pipeline
     */
    getPipelineLabel(stage) {
        return this.options.pipeline_stages?.[stage] || stage;
    }

    /**
     * Obter label da fonte
     */
    getSourceLabel(source) {
        return this.options.sources?.[source] || source;
    }
}

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    window.duraluxLeads = new DuraluxLeads();
});

// Exportar para uso global
window.DuraluxLeads = DuraluxLeads;