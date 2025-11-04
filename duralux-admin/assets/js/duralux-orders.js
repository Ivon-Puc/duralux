/**
 * Duralux Orders - Sistema de Gestão de Pedidos
 * 
 * Sistema completo para gerenciamento de pedidos e faturas
 * Funcionalidades: CRUD de pedidos, itens, estatísticas, faturas
 * 
 * @version 1.3.0
 * @author Ivon Martins
 * @created 2025-01-03
 */

class DuraluxOrders {
    constructor() {
        this.apiUrl = '/duralux/backend/api';
        this.currentPage = 1;
        this.itemsPerPage = 20;
        this.orders = [];
        this.customers = [];
        this.products = [];
        this.statistics = {};
        this.filters = {
            search: '',
            status: '',
            payment_status: '',
            customer_id: '',
            start_date: '',
            end_date: ''
        };
        
        this.init();
    }

    async init() {
        try {
            this.setupEventListeners();
            await this.loadInitialData();
            await this.loadOrders();
        } catch (error) {
            console.error('Erro ao inicializar sistema de pedidos:', error);
            this.showToast('Erro ao carregar sistema de pedidos', 'error');
        }
    }

    setupEventListeners() {
        // Botões principais
        document.getElementById('newOrderBtn')?.addEventListener('click', () => this.showOrderModal());
        document.getElementById('refreshBtn')?.addEventListener('click', () => this.loadOrders());

        // Filtros
        document.getElementById('searchInput')?.addEventListener('input', this.debounce((e) => {
            this.filters.search = e.target.value;
            this.currentPage = 1;
            this.loadOrders();
        }, 300));

        document.getElementById('statusFiltrar')?.addEventListener('change', (e) => {
            this.filters.status = e.target.value;
            this.currentPage = 1;
            this.loadOrders();
        });

        document.getElementById('paymentStatusFiltrar')?.addEventListener('change', (e) => {
            this.filters.payment_status = e.target.value;
            this.currentPage = 1;
            this.loadOrders();
        });

        document.getElementById('customerFiltrar')?.addEventListener('change', (e) => {
            this.filters.customer_id = e.target.value;
            this.currentPage = 1;
            this.loadOrders();
        });

        document.getElementById('startDateFiltrar')?.addEventListener('change', (e) => {
            this.filters.start_date = e.target.value;
            this.loadOrders();
        });

        document.getElementById('endDateFiltrar')?.addEventListener('change', (e) => {
            this.filters.end_date = e.target.value;
            this.loadOrders();
        });

        // Botão de limpar filtros
        document.getElementById('clearFiltrarsBtn')?.addEventListener('click', () => this.clearFiltrars());

        // Modal de pedido
        document.getElementById('saveOrderBtn')?.addEventListener('click', () => this.saveOrder());
        
        // Gerenciamento de itens no modal
        document.getElementById('addItemBtn')?.addEventListener('click', () => this.addOrderItem());

        // Ordenação da tabela
        document.querySelectorAll('.sortable').forEach(header => {
            header.addEventListener('click', () => this.handleSort(header));
        });
    }

    async loadInitialData() {
        try {
            // Carrega clientes
            const customersResponse = await this.apiRequest('GET', '/customers?limit=1000');
            this.customers = customersResponse.success ? customersResponse.data : [];

            // Carrega produtos
            const productsResponse = await this.apiRequest('GET', '/products?limit=1000');
            this.products = productsResponse.success ? productsResponse.data : [];

            // Popula filtros
            this.populateCustomerFiltrar();
            this.populateProductSelects();

            // Carrega estatísticas
            await this.loadStatistics();

        } catch (error) {
            console.error('Erro ao carregar dados iniciais:', error);
        }
    }

    async loadOrders() {
        try {
            this.showLoading(true);

            const queryParams = new URLSearchParams({
                page: this.currentPage,
                limit: this.itemsPerPage,
                ...this.filters
            });

            // Remove parâmetros vazios
            for (let [key, value] of queryParams.entries()) {
                if (!value) queryParams.delete(key);
            }

            const response = await this.apiRequest('GET', `/orders?${queryParams}`);

            if (response.success) {
                this.orders = response.data;
                this.renderOrdersTable();
                this.renderPagination(response.pagination);
            } else {
                throw new Error(response.message || 'Erro ao carregar pedidos');
            }

        } catch (error) {
            console.error('Erro ao carregar pedidos:', error);
            this.showToast('Erro ao carregar pedidos', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    async loadStatistics() {
        try {
            const response = await this.apiRequest('GET', '/orders/statistics');
            
            if (response.success) {
                this.statistics = response.data;
                this.renderStatistics();
            }
        } catch (error) {
            console.error('Erro ao carregar estatísticas:', error);
        }
    }

    renderStatistics() {
        const stats = this.statistics;

        // Atualizar cards de estatísticas
        this.updateStatCard('totalOrders', stats.general?.total_orders || 0);
        this.updateStatCard('totalReceita', this.formatCurrency(stats.general?.total_revenue || 0));
        this.updateStatCard('averageOrder', this.formatCurrency(stats.general?.average_order_value || 0));
        this.updateStatCard('paidOrders', stats.general?.paid_orders || 0);

        // Gráficos de status
        this.renderStatusChart();
    }

    updateStatCard(id, value) {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = value;
            element.classList.add('stat-updated');
            setTimeout(() => element.classList.remove('stat-updated'), 500);
        }
    }

    renderStatusChart() {
        const statusData = this.statistics.by_status || [];
        const paymentData = this.statistics.by_payment_status || [];

        // Implementar gráficos se necessário (Chart.js, ApexCharts, etc.)
        console.log('Status:', statusData);
        console.log('Payment:', paymentData);
    }

    renderOrdersTable() {
        const tbody = document.getElementById('ordersTableBody');
        if (!tbody) return;

        if (this.orders.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-4">
                        <div class="d-flex flex-column align-items-center">
                            <i class="bi bi-inbox fs-1 text-muted mb-2"></i>
                            <p class="text-muted">Nenhum pedido encontrado</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = this.orders.map(order => `
            <tr data-order-id="${order.id}">
                <td>
                    <input type="checkbox" class="form-check-input order-checkbox" 
                           value="${order.id}">
                </td>
                <td>
                    <div class="fw-semibold">${order.order_number}</div>
                    <small class="text-muted">${order.created_at_formatted}</small>
                </td>
                <td>
                    <div>${order.customer_name}</div>
                    <small class="text-muted">${order.customer_email}</small>
                </td>
                <td>
                    <span class="badge bg-${this.getStatusColor(order.status)}">
                        ${this.getStatusText(order.status)}
                    </span>
                </td>
                <td>
                    <span class="badge bg-${this.getPaymentStatusColor(order.payment_status)}">
                        ${this.getPaymentStatusText(order.payment_status)}
                    </span>
                </td>
                <td>
                    <div class="text-end fw-semibold">
                        ${this.formatCurrency(order.total_amount)}
                    </div>
                    <small class="text-muted">${order.total_items} item(s)</small>
                </td>
                <td>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-primary" 
                                onclick="duraluxOrders.viewOrder(${order.id})" 
                                title="View">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" 
                                onclick="duraluxOrders.editOrder(${order.id})" 
                                title="Editar">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-info" 
                                onclick="duraluxOrders.generateInvoice(${order.id})" 
                                title="Gerar Fatura">
                            <i class="bi bi-receipt"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" 
                                onclick="duraluxOrders.deleteOrder(${order.id})" 
                                title="Delete">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');

        // Atualizar contador
        this.updateOrderCount();
    }

    updateOrderCount() {
        const totalElement = document.getElementById('ordersTotal');
        if (totalElement) {
            totalElement.textContent = this.orders.length;
        }
    }

    populateCustomerFiltrar() {
        const select = document.getElementById('customerFiltrar');
        if (!select) return;

        select.innerHTML = '<option value="">Todos os clientes</option>' +
            this.customers.map(customer => 
                `<option value="${customer.id}">${customer.name}</option>`
            ).join('');
    }

    populateProductSelects() {
        const selects = document.querySelectorAll('.product-select');
        const productOptions = this.products.map(product => 
            `<option value="${product.id}" data-price="${product.price}">${product.name} - ${this.formatCurrency(product.price)}</option>`
        ).join('');

        selects.forEach(select => {
            select.innerHTML = '<option value="">Selecione um produto</option>' + productOptions;
        });
    }

    showOrderModal(order = null) {
        const modal = document.getElementById('orderModal');
        if (!modal) return;

        // Limpar modal
        this.clearOrderModal();

        if (order) {
            // Preencher dados para edição
            this.fillOrderModal(order);
        } else {
            // Novo pedido - adicionar primeiro item
            this.addOrderItem();
        }

        // Mostrar modal
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }

    clearOrderModal() {
        document.getElementById('orderId').value = '';
        document.getElementById('customerId').value = '';
        document.getElementById('orderStatus').value = 'pending';
        document.getElementById('paymentStatus').value = 'unpaid';
        document.getElementById('orderNotes').value = '';
        
        // Limpar itens
        const itemsContainer = document.getElementById('orderItemsContainer');
        if (itemsContainer) {
            itemsContainer.innerHTML = '';
        }

        // Limpar total
        this.updateOrderTotal();
    }

    fillOrderModal(order) {
        document.getElementById('orderId').value = order.id;
        document.getElementById('customerId').value = order.customer_id;
        document.getElementById('orderStatus').value = order.status;
        document.getElementById('paymentStatus').value = order.payment_status;
        document.getElementById('orderNotes').value = order.notes || '';

        // Add itens
        if (order.items && order.items.length > 0) {
            order.items.forEach(item => this.addOrderItem(item));
        }

        this.updateOrderTotal();
    }

    addOrderItem(item = null) {
        const container = document.getElementById('orderItemsContainer');
        if (!container) return;

        const itemId = Date.now() + Math.random();
        const itemHtml = `
            <div class="order-item border p-3 mb-3" data-item-id="${itemId}">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Produto</label>
                        <select class="form-select product-select" name="product_id" required>
                            <option value="">Selecione um produto</option>
                            ${this.products.map(product => 
                                `<option value="${product.id}" data-price="${product.price}" 
                                    ${item && item.product_id == product.id ? 'selected' : ''}>
                                    ${product.name} - ${this.formatCurrency(product.price)}
                                </option>`
                            ).join('')}
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Quantidade</label>
                        <input type="number" class="form-control quantity-input" name="quantity" 
                               min="1" value="${item?.quantity || 1}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Preço Unitário</label>
                        <input type="number" class="form-control price-input" name="price" 
                               step="0.01" min="0" value="${item?.price || ''}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Total</label>
                        <input type="text" class="form-control total-input" readonly 
                               value="${item ? this.formatCurrency(item.total) : '0,00'}">
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-outline-danger btn-sm remove-item-btn">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-12">
                        <label class="form-label">Descrição</label>
                        <input type="text" class="form-control" name="description" 
                               placeholder="Descrição adicional (opcional)" 
                               value="${item?.description || ''}">
                    </div>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', itemHtml);

        // Configurar eventos do item
        this.setupItemEvents(container.lastElementChild);
        this.updateOrderTotal();
    }

    setupItemEvents(itemElement) {
        // Seleção de produto
        const productSelect = itemElement.querySelector('.product-select');
        productSelect.addEventListener('change', (e) => {
            const option = e.target.selectedOptions[0];
            const price = option?.dataset.price || 0;
            const priceInput = itemElement.querySelector('.price-input');
            priceInput.value = price;
            this.calculateItemTotal(itemElement);
        });

        // Quantidade
        const quantityInput = itemElement.querySelector('.quantity-input');
        quantityInput.addEventListener('input', () => {
            this.calculateItemTotal(itemElement);
        });

        // Preço
        const priceInput = itemElement.querySelector('.price-input');
        priceInput.addEventListener('input', () => {
            this.calculateItemTotal(itemElement);
        });

        // Remover item
        const removeBtn = itemElement.querySelector('.remove-item-btn');
        removeBtn.addEventListener('click', () => {
            itemElement.remove();
            this.updateOrderTotal();
        });
    }

    calculateItemTotal(itemElement) {
        const quantity = parseFloat(itemElement.querySelector('.quantity-input').value) || 0;
        const price = parseFloat(itemElement.querySelector('.price-input').value) || 0;
        const total = quantity * price;
        
        itemElement.querySelector('.total-input').value = this.formatCurrency(total);
        this.updateOrderTotal();
    }

    updateOrderTotal() {
        const items = document.querySelectorAll('.order-item');
        let total = 0;

        items.forEach(item => {
            const quantity = parseFloat(item.querySelector('.quantity-input').value) || 0;
            const price = parseFloat(item.querySelector('.price-input').value) || 0;
            total += quantity * price;
        });

        const totalElement = document.getElementById('orderTotal');
        if (totalElement) {
            totalElement.textContent = this.formatCurrency(total);
        }
    }

    async saveOrder() {
        try {
            const orderId = document.getElementById('orderId').value;
            const formData = this.getOrderFormData();

            if (!this.validateOrderData(formData)) {
                return;
            }

            this.showLoading(true);

            let response;
            if (orderId) {
                // Atualizar pedido existente
                response = await this.apiRequest('PUT', `/orders/${orderId}`, formData);
            } else {
                // Create novo pedido
                response = await this.apiRequest('POST', '/orders', formData);
            }

            if (response.success) {
                this.showToast(response.message || 'Pedido salvo com sucesso', 'success');
                
                // Fechar modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('orderModal'));
                modal?.hide();

                // Recarregar dados
                await this.loadOrders();
                await this.loadStatistics();
            } else {
                throw new Error(response.message || 'Erro ao salvar pedido');
            }

        } catch (error) {
            console.error('Erro ao salvar pedido:', error);
            this.showToast(error.message || 'Erro ao salvar pedido', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    getOrderFormData() {
        const items = [];
        const itemElements = document.querySelectorAll('.order-item');

        itemElements.forEach(item => {
            const productId = item.querySelector('.product-select').value;
            const quantity = parseInt(item.querySelector('.quantity-input').value);
            const price = parseFloat(item.querySelector('.price-input').value);
            const description = item.querySelector('input[name="description"]').value;

            if (productId && quantity > 0 && price >= 0) {
                items.push({
                    product_id: productId,
                    quantity: quantity,
                    price: price,
                    description: description
                });
            }
        });

        return {
            customer_id: document.getElementById('customerId').value,
            status: document.getElementById('orderStatus').value,
            payment_status: document.getElementById('paymentStatus').value,
            notes: document.getElementById('orderNotes').value,
            items: items
        };
    }

    validateOrderData(data) {
        if (!data.customer_id) {
            this.showToast('Por favor, selecione um cliente', 'warning');
            return false;
        }

        if (!data.items || data.items.length === 0) {
            this.showToast('Por favor, adicione pelo menos um item', 'warning');
            return false;
        }

        return true;
    }

    async viewOrder(id) {
        try {
            this.showLoading(true);
            
            const response = await this.apiRequest('GET', `/orders/${id}`);
            
            if (response.success) {
                this.showOrderDetailsModal(response.data);
            } else {
                throw new Error(response.message || 'Erro ao carregar pedido');
            }
        } catch (error) {
            console.error('Erro ao visualizar pedido:', error);
            this.showToast('Erro ao carregar detalhes do pedido', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    showOrderDetailsModal(order) {
        // Implementar modal de detalhes do pedido
        const modalHtml = `
            <div class="modal fade" id="orderDetailsModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Details do Pedido ${order.order_number}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Informações do Cliente</h6>
                                    <p><strong>Nome:</strong> ${order.customer_name}</p>
                                    <p><strong>Email:</strong> ${order.customer_email}</p>
                                    <p><strong>Telefone:</strong> ${order.customer_phone || 'Não informado'}</p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Informações do Pedido</h6>
                                    <p><strong>Status:</strong> 
                                        <span class="badge bg-${this.getStatusColor(order.status)}">
                                            ${this.getStatusText(order.status)}
                                        </span>
                                    </p>
                                    <p><strong>Pagamento:</strong> 
                                        <span class="badge bg-${this.getPaymentStatusColor(order.payment_status)}">
                                            ${this.getPaymentStatusText(order.payment_status)}
                                        </span>
                                    </p>
                                    <p><strong>Data:</strong> ${order.created_at_formatted}</p>
                                </div>
                            </div>
                            <hr>
                            <h6>Itens do Pedido</h6>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Produto</th>
                                            <th>Quantidade</th>
                                            <th>Preço Unit.</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${order.items.map(item => `
                                            <tr>
                                                <td>${item.product_name || 'Produto não encontrado'}</td>
                                                <td>${item.quantity}</td>
                                                <td>${this.formatCurrency(item.price)}</td>
                                                <td>${this.formatCurrency(item.total)}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                    <tfoot>
                                        <tr class="fw-bold">
                                            <td colspan="3">Total Geral:</td>
                                            <td>${this.formatCurrency(order.total_amount)}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            ${order.notes ? `
                                <hr>
                                <h6>Observações</h6>
                                <p>${order.notes}</p>
                            ` : ''}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                            <button type="button" class="btn btn-primary" onclick="duraluxOrders.editOrder(${order.id})">Editar</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remover modal anterior se existir
        const existingModal = document.getElementById('orderDetailsModal');
        if (existingModal) {
            existingModal.remove();
        }

        // Add novo modal
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Mostrar modal
        const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
        modal.show();
    }

    async editOrder(id) {
        try {
            this.showLoading(true);
            
            const response = await this.apiRequest('GET', `/orders/${id}`);
            
            if (response.success) {
                // Fechar modal de detalhes se estiver aberto
                const detailsModal = bootstrap.Modal.getInstance(document.getElementById('orderDetailsModal'));
                detailsModal?.hide();
                
                // Mostrar modal de edição
                this.showOrderModal(response.data);
            } else {
                throw new Error(response.message || 'Erro ao carregar pedido');
            }
        } catch (error) {
            console.error('Erro ao carregar pedido para edição:', error);
            this.showToast('Erro ao carregar pedido', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    async deleteOrder(id) {
        if (!await this.confirm('Tem certeza que deseja excluir este pedido? Esta ação não pode ser desfeita.')) {
            return;
        }

        try {
            this.showLoading(true);
            
            const response = await this.apiRequest('DELETE', `/orders/${id}`);
            
            if (response.success) {
                this.showToast('Pedido excluído com sucesso', 'success');
                await this.loadOrders();
                await this.loadStatistics();
            } else {
                throw new Error(response.message || 'Erro ao excluir pedido');
            }
        } catch (error) {
            console.error('Erro ao excluir pedido:', error);
            this.showToast('Erro ao excluir pedido', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    async generateInvoice(id) {
        try {
            this.showLoading(true);
            
            const response = await this.apiRequest('POST', `/orders/${id}/invoice`);
            
            if (response.success) {
                this.showToast('Fatura gerada com sucesso', 'success');
                // Implementar visualização/download da fatura
                console.log('Fatura:', response.data);
            } else {
                throw new Error(response.message || 'Erro ao gerar fatura');
            }
        } catch (error) {
            console.error('Erro ao gerar fatura:', error);
            this.showToast('Erro ao gerar fatura', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    clearFiltrars() {
        this.filters = {
            search: '',
            status: '',
            payment_status: '',
            customer_id: '',
            start_date: '',
            end_date: ''
        };

        // Limpar campos
        document.getElementById('searchInput').value = '';
        document.getElementById('statusFiltrar').value = '';
        document.getElementById('paymentStatusFiltrar').value = '';
        document.getElementById('customerFiltrar').value = '';
        document.getElementById('startDateFiltrar').value = '';
        document.getElementById('endDateFiltrar').value = '';

        this.currentPage = 1;
        this.loadOrders();
    }

    renderPagination(pagination) {
        const container = document.getElementById('paginationContainer');
        if (!container) return;

        if (pagination.total_pages <= 1) {
            container.innerHTML = '';
            return;
        }

        const { current_page, total_pages } = pagination;
        let html = '<nav><ul class="pagination justify-content-center">';

        // Botão anterior
        html += `<li class="page-item ${current_page === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="duraluxOrders.changePage(${current_page - 1})">Anterior</a>
        </li>`;

        // Páginas
        for (let i = Math.max(1, current_page - 2); i <= Math.min(total_pages, current_page + 2); i++) {
            html += `<li class="page-item ${i === current_page ? 'active' : ''}">
                <a class="page-link" href="#" onclick="duraluxOrders.changePage(${i})">${i}</a>
            </li>`;
        }

        // Botão próximo
        html += `<li class="page-item ${current_page === total_pages ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="duraluxOrders.changePage(${current_page + 1})">Próximo</a>
        </li>`;

        html += '</ul></nav>';
        container.innerHTML = html;
    }

    changePage(page) {
        this.currentPage = page;
        this.loadOrders();
    }

    // ==================== MÉTODOS UTILITÁRIOS ====================

    getStatusColor(status) {
        const colors = {
            'pending': 'warning',
            'confirmed': 'info',
            'processing': 'primary',
            'shipped': 'secondary',
            'delivered': 'success',
            'completed': 'success',
            'cancelled': 'danger'
        };
        return colors[status] || 'secondary';
    }

    getStatusText(status) {
        const texts = {
            'pending': 'Pendente',
            'confirmed': 'Confirmado',
            'processing': 'Processando',
            'shipped': 'Enviado',
            'delivered': 'Entregue',
            'completed': 'Completo',
            'cancelled': 'Cancelado'
        };
        return texts[status] || status;
    }

    getPaymentStatusColor(status) {
        const colors = {
            'unpaid': 'danger',
            'pending': 'warning',
            'paid': 'success',
            'partially_paid': 'info',
            'refunded': 'secondary'
        };
        return colors[status] || 'secondary';
    }

    getPaymentStatusText(status) {
        const texts = {
            'unpaid': 'Não Pago',
            'pending': 'Pendente',
            'paid': 'Pago',
            'partially_paid': 'Parcialmente Pago',
            'refunded': 'Reembolsado'
        };
        return texts[status] || status;
    }

    formatCurrency(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value || 0);
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

    async apiRequest(method, endpoint, data = null) {
        const url = this.apiUrl + endpoint;
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        if (data && (method === 'POST' || method === 'PUT')) {
            options.body = JSON.stringify(data);
        }

        const response = await fetch(url, options);
        return await response.json();
    }

    showLoading(show) {
        const loader = document.getElementById('loadingOverlay');
        if (loader) {
            loader.style.display = show ? 'flex' : 'none';
        }
    }

    showToast(message, type = 'info') {
        // Implementar sistema de toasts
        console.log(`${type.toUpperCase()}: ${message}`);
        
        // Create toast bootstrap se disponível
        const toastContainer = document.getElementById('toastContainer') || this.createToastContainer();
        const toastId = 'toast-' + Date.now();
        
        const toastHtml = `
            <div id="${toastId}" class="toast" role="alert">
                <div class="toast-header">
                    <i class="bi bi-${this.getToastIcon(type)} me-2"></i>
                    <strong class="me-auto">${this.getToastTitle(type)}</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">${message}</div>
            </div>
        `;
        
        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement);
        toast.show();
        
        // Remover após ocultar
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
    }

    createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '1055';
        document.body.appendChild(container);
        return container;
    }

    getToastIcon(type) {
        const icons = {
            'success': 'check-circle-fill',
            'error': 'exclamation-triangle-fill',
            'warning': 'exclamation-triangle-fill',
            'info': 'info-circle-fill'
        };
        return icons[type] || 'info-circle-fill';
    }

    getToastTitle(type) {
        const titles = {
            'success': 'Sucesso',
            'error': 'Erro',
            'warning': 'Atenção',
            'info': 'Informação'
        };
        return titles[type] || 'Informação';
    }

    async confirm(message) {
        return new Promise((resolve) => {
            if (typeof bootstrap !== 'undefined') {
                // Implementar modal de confirmação
                const confirmModal = document.createElement('div');
                confirmModal.innerHTML = `
                    <div class="modal fade" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Confirmação</h5>
                                </div>
                                <div class="modal-body">
                                    <p>${message}</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="button" class="btn btn-primary confirm-btn">Confirmar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                document.body.appendChild(confirmModal);
                const modal = new bootstrap.Modal(confirmModal.querySelector('.modal'));
                
                confirmModal.querySelector('.confirm-btn').addEventListener('click', () => {
                    modal.hide();
                    resolve(true);
                });
                
                confirmModal.querySelector('.modal').addEventListener('hidden.bs.modal', () => {
                    confirmModal.remove();
                    resolve(false);
                });
                
                modal.show();
            } else {
                resolve(confirm(message));
            }
        });
    }

    handleSort(header) {
        // Implementar ordenação se necessário
        console.log('Ordenar por:', header.dataset.sort);
    }
}

// Inicializar quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    window.duraluxOrders = new DuraluxOrders();
});