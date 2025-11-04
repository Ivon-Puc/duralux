/**
 * Duralux CRM - Sistema de Integração de Clientes
 * Conecta interface HTML com API backend PHP
 * @version 1.0.0
 */

class DuraluxCustomers {
    constructor() {
        this.API_BASE = '../backend/api/';
        this.authToken = null;
        this.currentPage = 1;
        this.itemsPerPage = 10;
        this.currentFiltrars = {};
        
        this.init();
    }
    
    /**
     * Inicializa o sistema
     */
    init() {
        this.checkAuthStatus();
        this.bindEvents();
        this.loadCustomers();
    }
    
    /**
     * Verifica status de autenticação
     */
    async checkAuthStatus() {
        try {
            const response = await this.apiCall('auth/me');
            
            if (response.success && response.data.data) {
                this.authToken = response.data.data.csrf_token;
                this.showElement('#main-content');
                this.hideElement('#auth-required');
            } else {
                this.showElement('#auth-required');
                this.hideElement('#main-content');
            }
        } catch (error) {
            console.error('Erro ao verificar autenticação:', error);
            this.showNotification('Erro de conectividade com o servidor', 'error');
        }
    }
    
    /**
     * Vincula eventos da interface
     */
    bindEvents() {
        // Busca em tempo real
        const searchInput = document.getElementById('customer-search');
        if (searchInput) {
            searchInput.addEventListener('input', this.debounce((e) => {
                this.currentFiltrars.search = e.target.value;
                this.loadCustomers();
            }, 500));
        }
        
        // Filtros
        const statusFiltrar = document.getElementById('status-filter');
        if (statusFiltrar) {
            statusFiltrar.addEventListener('change', (e) => {
                this.currentFiltrars.active = e.target.value;
                this.loadCustomers();
            });
        }
        
        // Paginação
        document.addEventListener('click', (e) => {
            if (e.target.matches('.pagination-btn')) {
                e.preventDefault();
                const page = parseInt(e.target.dataset.page);
                if (page && page !== this.currentPage) {
                    this.currentPage = page;
                    this.loadCustomers();
                }
            }
        });
        
        // Modal de novo cliente
        const newCustomerBtn = document.getElementById('new-customer-btn');
        if (newCustomerBtn) {
            newCustomerBtn.addEventListener('click', () => this.showNovoCustomerModal());
        }
        
        // Formulário de novo cliente
        const customerForm = document.getElementById('customer-form');
        if (customerForm) {
            customerForm.addEventListener('submit', (e) => this.handleCustomerSubmit(e));
        }
        
        // Ações da tabela
        document.addEventListener('click', (e) => {
            if (e.target.matches('.btn-view-customer')) {
                const customerId = e.target.dataset.customerId;
                this.viewCustomer(customerId);
            } else if (e.target.matches('.btn-edit-customer')) {
                const customerId = e.target.dataset.customerId;
                this.editCustomer(customerId);
            } else if (e.target.matches('.btn-delete-customer')) {
                const customerId = e.target.dataset.customerId;
                this.deleteCustomer(customerId);
            }
        });
        
        // Seleção em massa
        const selectAll = document.getElementById('checkAllCustomer');
        if (selectAll) {
            selectAll.addEventListener('change', this.handleSelectAll.bind(this));
        }
        
        // Exportararação
        document.addEventListener('click', (e) => {
            if (e.target.matches('.btn-export')) {
                const format = e.target.dataset.format;
                this.exportCustomers(format);
            }
        });
    }
    
    /**
     * Carrega lista de clientes
     */
    async loadCustomers() {
        this.showLoading();
        
        try {
            const params = new URLBuscarParams({
                page: this.currentPage,
                limit: this.itemsPerPage,
                ...this.currentFiltrars
            });
            
            const response = await this.apiCall(`customers?${params.toString()}`);
            
            if (response.success) {
                this.renderCustomersTable(response.data.data);
                this.renderPagination(response.data.data.pagination);
                this.updateStats(response.data.data.statistics);
            } else {
                throw new Error(response.data.message || 'Erro ao carregar clientes');
            }
        } catch (error) {
            console.error('Erro ao carregar clientes:', error);
            this.showNotification('Erro ao carregar clientes', 'error');
        } finally {
            this.hideLoading();
        }
    }
    
    /**
     * Renderiza tabela de clientes
     */
    renderCustomersTable(data) {
        const tableBody = document.querySelector('#customerList tbody');
        if (!tableBody) return;
        
        if (!data.customers || data.customers.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-5">
                        <i class="feather-users fs-2 text-muted mb-3 d-block"></i>
                        <h6 class="text-muted">Nenhum cliente encontrado</h6>
                        <p class="text-muted mb-4">Comece cadastrando seu primeiro cliente</p>
                        <button type="button" class="btn btn-primary" id="new-customer-btn">
                            <i class="feather-plus me-2"></i>Novo Cliente
                        </button>
                    </td>
                </tr>
            `;
            return;
        }
        
        let html = '';
        data.customers.forEach((customer, index) => {
            const avatar = customer.name ? this.generateAvatar(customer.name) : 
                `<div class="avatar-text avatar-md bg-gray-200 text-dark">?</div>`;
            
            const statusBadge = this.getStatusBadge(customer.active);
            const groupTags = this.renderGroupTags(customer.groups);
            
            html += `
                <tr class="single-item" data-customer-id="${customer.id}">
                    <td>
                        <div class="item-checkbox ms-1">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input checkbox" 
                                       id="checkBox_${customer.id}" value="${customer.id}">
                                <label class="custom-control-label" for="checkBox_${customer.id}"></label>
                            </div>
                        </div>
                    </td>
                    <td>
                        <a href="javascript:void(0)" class="hstack gap-3 btn-view-customer" data-customer-id="${customer.id}">
                            ${avatar}
                            <div>
                                <span class="text-truncate-1-line fw-semibold">${this.escapeHtml(customer.name || 'Sem nome')}</span>
                                ${customer.company ? `<small class="text-muted d-block">${this.escapeHtml(customer.company)}</small>` : ''}
                            </div>
                        </a>
                    </td>
                    <td>
                        <a href="mailto:${customer.email}" class="text-decoration-none">
                            ${this.escapeHtml(customer.email)}
                        </a>
                    </td>
                    <td>${groupTags}</td>
                    <td>
                        ${customer.phone ? 
                            `<a href="tel:${customer.phone}" class="text-decoration-none">${this.escapeHtml(customer.phone)}</a>` : 
                            '<span class="text-muted">-</span>'
                        }
                    </td>
                    <td>
                        <span class="text-muted">${customer.created_at_formatted}</span>
                    </td>
                    <td>${statusBadge}</td>
                    <td>
                        <div class="hstack gap-2 justify-content-end">
                            <a href="javascript:void(0)" class="avatar-text avatar-md btn-view-customer" 
                               data-customer-id="${customer.id}" title="Visualizar">
                                <i class="feather feather-eye"></i>
                            </a>
                            <div class="dropdown">
                                <a href="javascript:void(0)" class="avatar-text avatar-md" 
                                   data-bs-toggle="dropdown" data-bs-offset="0,21">
                                    <i class="feather feather-more-horizontal"></i>
                                </a>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item btn-edit-customer" 
                                           href="javascript:void(0)" data-customer-id="${customer.id}">
                                            <i class="feather feather-edit-3 me-3"></i>
                                            <span>Editar</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                           onclick="window.print()">
                                            <i class="feather feather-printer me-3"></i>
                                            <span>Imprimir</span>
                                        </a>
                                    </li>
                                    <li class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item btn-delete-customer text-danger" 
                                           href="javascript:void(0)" data-customer-id="${customer.id}">
                                            <i class="feather feather-trash-2 me-3"></i>
                                            <span>Excluir</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </td>
                </tr>
            `;
        });
        
        tableBody.innerHTML = html;
    }
    
    /**
     * Renderiza paginação
     */
    renderPagination(pagination) {
        const paginationContainer = document.querySelector('.pagination-container');
        if (!paginationContainer || !pagination) return;
        
        let html = `
            <nav aria-label="Navegação de páginas">
                <ul class="pagination justify-content-center">
        `;
        
        // Botão anterior
        if (pagination.current_page > 1) {
            html += `
                <li class="page-item">
                    <a class="page-link pagination-btn" href="#" data-page="${pagination.current_page - 1}">
                        <i class="feather-chevron-left"></i>
                    </a>
                </li>
            `;
        }
        
        // Números das páginas
        const startPage = Math.max(1, pagination.current_page - 2);
        const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
        
        for (let i = startPage; i <= endPage; i++) {
            html += `
                <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                    <a class="page-link pagination-btn" href="#" data-page="${i}">${i}</a>
                </li>
            `;
        }
        
        // Botão próximo
        if (pagination.current_page < pagination.total_pages) {
            html += `
                <li class="page-item">
                    <a class="page-link pagination-btn" href="#" data-page="${pagination.current_page + 1}">
                        <i class="feather-chevron-right"></i>
                    </a>
                </li>
            `;
        }
        
        html += `
                </ul>
            </nav>
            <div class="text-center text-muted mt-3">
                Mostrando ${pagination.from || 0} até ${pagination.to || 0} de ${pagination.total} clientes
            </div>
        `;
        
        paginationContainer.innerHTML = html;
    }
    
    /**
     * Atualiza estatísticas
     */
    updateStats(stats) {
        if (!stats) return;
        
        const statElements = {
            'total-customers': stats.total || 0,
            'active-customers': stats.active_count || 0,
            'new-customers': stats.recent_count || 0,
            'inactive-customers': stats.inactive_count || 0
        };
        
        Object.keys(statElements).forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = this.formatNumber(statElements[id]);
            }
        });
    }
    
    /**
     * Mostra modal de novo cliente
     */
    showNovoCustomerModal() {
        const modal = new bootstrap.Modal(document.getElementById('customerModal'));
        
        // Limpar formulário
        const form = document.getElementById('customer-form');
        if (form) {
            form.reset();
            document.getElementById('customer-id').value = '';
            document.querySelector('#customerModal .modal-title').textContent = 'Novo Cliente';
        }
        
        modal.show();
    }
    
    /**
     * Processa envio do formulário de cliente
     */
    async handleCustomerSubmit(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        const customerId = formData.get('customer_id');
        
        const customerData = {
            name: formData.get('name'),
            email: formData.get('email'),
            phone: formData.get('phone'),
            company: formData.get('company'),
            address: formData.get('address'),
            city: formData.get('city'),
            state: formData.get('state'),
            zipcode: formData.get('zipcode'),
            country: formData.get('country'),
            active: formData.get('active') ? 1 : 0
        };
        
        try {
            this.showLoading();
            
            let response;
            if (customerId) {
                // Atualizar cliente existente
                response = await this.apiCall(`customers/${customerId}`, 'PUT', customerData);
            } else {
                // Criar novo cliente
                response = await this.apiCall('customers', 'POST', customerData);
            }
            
            if (response.success) {
                this.showNotification(
                    customerId ? 'Cliente atualizado com sucesso!' : 'Cliente criado com sucesso!', 
                    'success'
                );
                
                // Fechar modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('customerModal'));
                modal.hide();
                
                // Recarregar lista
                this.loadCustomers();
            } else {
                throw new Error(response.data.message || 'Erro ao salvar cliente');
            }
        } catch (error) {
            console.error('Erro ao salvar cliente:', error);
            this.showNotification('Erro ao salvar cliente', 'error');
        } finally {
            this.hideLoading();
        }
    }
    
    /**
     * Visualiza detalhes do cliente
     */
    async viewCustomer(customerId) {
        try {
            this.showLoading();
            
            const response = await this.apiCall(`customers/${customerId}`);
            
            if (response.success) {
                const customer = response.data.data;
                this.showCustomerDetails(customer);
            } else {
                throw new Error(response.data.message || 'Erro ao carregar cliente');
            }
        } catch (error) {
            console.error('Erro ao carregar cliente:', error);
            this.showNotification('Erro ao carregar detalhes do cliente', 'error');
        } finally {
            this.hideLoading();
        }
    }
    
    /**
     * Edita cliente
     */
    async editCustomer(customerId) {
        try {
            this.showLoading();
            
            const response = await this.apiCall(`customers/${customerId}`);
            
            if (response.success) {
                const customer = response.data.data;
                this.populateCustomerForm(customer);
                this.showNovoCustomerModal();
            } else {
                throw new Error(response.data.message || 'Erro ao carregar cliente');
            }
        } catch (error) {
            console.error('Erro ao carregar cliente para edição:', error);
            this.showNotification('Erro ao carregar cliente para edição', 'error');
        } finally {
            this.hideLoading();
        }
    }
    
    /**
     * Exclui cliente
     */
    async deleteCustomer(customerId) {
        if (!confirm('Tem certeza que deseja excluir este cliente? Esta ação não pode ser desfeita.')) {
            return;
        }
        
        try {
            this.showLoading();
            
            const response = await this.apiCall(`customers/${customerId}`, 'DELETE');
            
            if (response.success) {
                this.showNotification('Cliente excluído com sucesso!', 'success');
                this.loadCustomers();
            } else {
                throw new Error(response.data.message || 'Erro ao excluir cliente');
            }
        } catch (error) {
            console.error('Erro ao excluir cliente:', error);
            this.showNotification('Erro ao excluir cliente', 'error');
        } finally {
            this.hideLoading();
        }
    }
    
    /**
     * Função utilitária para chamadas à API
     */
    async apiCall(endpoint, method = 'GET', data = null) {
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            }
        };
        
        if (this.authToken) {
            options.headers['X-CSRF-Token'] = this.authToken;
        }
        
        if (data) {
            if (this.authToken) data.csrf_token = this.authToken;
            options.body = JSON.stringify(data);
        }
        
        const response = await fetch(this.API_BASE + endpoint, options);
        const result = await response.json();
        
        return {
            status: response.status,
            data: result,
            success: response.ok
        };
    }
    
    /**
     * Funções utilitárias da interface
     */
    showLoading() {
        // Implementar loading spinner
        document.body.classList.add('loading');
    }
    
    hideLoading() {
        document.body.classList.remove('loading');
    }
    
    showElement(selector) {
        const element = document.querySelector(selector);
        if (element) element.style.display = 'block';
    }
    
    hideElement(selector) {
        const element = document.querySelector(selector);
        if (element) element.style.display = 'none';
    }
    
    showNotification(message, type = 'info') {
        // Implementar sistema de notificações toast
        console.log(`${type.toUpperCase()}: ${message}`);
    }
    
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
    
    formatNumber(num) {
        return new Intl.NumberFormat('pt-BR').format(num);
    }
    
    generateAvatar(name) {
        const initials = name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
        const colors = ['bg-primary', 'bg-success', 'bg-info', 'bg-warning', 'bg-danger', 'bg-teal'];
        const colorClass = colors[name.length % colors.length];
        
        return `<div class="avatar-text avatar-md ${colorClass} text-white">${initials}</div>`;
    }
    
    getStatusBadge(active) {
        if (active) {
            return '<span class="badge bg-success">Ativo</span>';
        } else {
            return '<span class="badge bg-danger">Inativo</span>';
        }
    }
    
    renderGroupTags(groups) {
        if (!groups || groups.length === 0) {
            return '<span class="text-muted">-</span>';
        }
        
        return groups.map(group => 
            `<span class="badge bg-primary me-1">${this.escapeHtml(group)}</span>`
        ).join('');
    }
    
    // Métodos adicionais para funcionalidades específicas
    handleSelectAll(e) {
        const checkboxes = document.querySelectorAll('.checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = e.target.checked;
        });
    }
    
    getSelectedCustomers() {
        const checkboxes = document.querySelectorAll('.checkbox:checked');
        return Array.from(checkboxes).map(cb => cb.value);
    }
    
    async exportCustomers(format) {
        const selectedIds = this.getSelectedCustomers();
        
        try {
            this.showLoading();
            
            const params = new URLBuscarParams({
                format: format,
                ...this.currentFiltrars
            });
            
            if (selectedIds.length > 0) {
                params.append('ids', selectedIds.join(','));
            }
            
            window.open(`${this.API_BASE}customers/export?${params.toString()}`, '_blank');
            
            this.showNotification('Exportararação iniciada!', 'success');
        } catch (error) {
            console.error('Erro ao exportar clientes:', error);
            this.showNotification('Erro ao exportar clientes', 'error');
        } finally {
            this.hideLoading();
        }
    }
    
    populateCustomerForm(customer) {
        const form = document.getElementById('customer-form');
        if (!form) return;
        
        // Preencher campos do formulário
        const fields = ['id', 'name', 'email', 'phone', 'company', 'address', 'city', 'state', 'zipcode', 'country'];
        
        fields.forEach(field => {
            const input = form.querySelector(`[name="${field}"], [name="customer_${field}"]`);
            if (input && customer[field] !== undefined) {
                input.value = customer[field] || '';
            }
        });
        
        // Status ativo
        const activeCheckbox = form.querySelector('[name="active"]');
        if (activeCheckbox) {
            activeCheckbox.checked = customer.active;
        }
        
        // Atualizar título do modal
        document.querySelector('#customerModal .modal-title').textContent = 'Editar Cliente';
    }
    
    showCustomerDetails(customer) {
        // Implementar modal ou página de detalhes do cliente
        console.log('Detalhes do cliente:', customer);
    }
}

// Inicializar quando o DOM estiver carregado
document.addEventListener('DOMContentLoaded', function() {
    window.duraluxCustomers = new DuraluxCustomers();
});