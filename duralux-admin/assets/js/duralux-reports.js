/**
 * Duralux Reports - Sistema de Relatórios Avançados
 * 
 * Sistema completo para geração de relatórios executivos e análises
 * Funcionalidades: Relatórios customizáveis, gráficos, exportação, filtros
 * 
 * @version 1.4.0
 * @author Ivon Martins
 * @created 2025-01-03
 */

class DuraluxReports {
    constructor() {
        this.apiUrl = '/duralux/backend/api';
        this.charts = {};
        this.currentReport = null;
        this.filters = {
            period: 'month',
            start_date: '',
            end_date: '',
            report_type: 'dashboard'
        };
        
        this.init();
    }

    async init() {
        try {
            this.setupEventListeners();
            await this.loadPainel de ControleReport();
            this.initializeDatePickers();
        } catch (error) {
            console.error('Erro ao inicializar sistema de relatórios:', error);
            this.showToast('Erro ao carregar sistema de relatórios', 'error');
        }
    }

    setupEventListeners() {
        // Filtros de período
        document.getElementById('periodFiltrar')?.addEventListener('change', (e) => {
            this.filters.period = e.target.value;
            this.loadCurrentReport();
        });

        // Filtros de data
        document.getElementById('startDateFiltrar')?.addEventListener('change', (e) => {
            this.filters.start_date = e.target.value;
            this.loadCurrentReport();
        });

        document.getElementById('endDateFiltrar')?.addEventListener('change', (e) => {
            this.filters.end_date = e.target.value;
            this.loadCurrentReport();
        });

        // Botões de relatórios
        document.getElementById('dashboardReportBtn')?.addEventListener('click', () => this.loadPainel de ControleReport());
        document.getElementById('salesReportBtn')?.addEventListener('click', () => this.loadVendasReport());
        document.getElementById('leadsReportBtn')?.addEventListener('click', () => this.loadLeadsReport());
        document.getElementById('projectsReportBtn')?.addEventListener('click', () => this.loadProjectsReport());
        document.getElementById('customersReportBtn')?.addEventListener('click', () => this.loadCustomersReport());
        document.getElementById('financialReportBtn')?.addEventListener('click', () => this.loadFinancialReport());

        // Exportararação
        document.getElementById('exportPdfBtn')?.addEventListener('click', () => this.exportReport('pdf'));
        document.getElementById('exportExcelBtn')?.addEventListener('click', () => this.exportReport('excel'));
        document.getElementById('exportCsvBtn')?.addEventListener('click', () => this.exportReport('csv'));

        // Atualização
        document.getElementById('refreshReportBtn')?.addEventListener('click', () => this.loadCurrentReport());

        // Navegação de abas
        document.querySelectorAll('.report-tab').forEach(tab => {
            tab.addEventListener('click', (e) => this.handleTabClick(e));
        });
    }

    initializeDatePickers() {
        // Configurar datas padrão
        const today = new Date();
        const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));
        
        const startInput = document.getElementById('startDateFiltrar');
        const endInput = document.getElementById('endDateFiltrar');
        
        if (startInput && !startInput.value) {
            startInput.value = thirtyDaysAgo.toISOString().split('T')[0];
            this.filters.start_date = startInput.value;
        }
        
        if (endInput && !endInput.value) {
            endInput.value = today.toISOString().split('T')[0];
            this.filters.end_date = endInput.value;
        }
    }

    async loadPainel de ControleReport() {
        try {
            this.showLoading(true);
            this.setActiveTab('dashboard');
            this.filters.report_type = 'dashboard';

            const queryParams = new URLBuscarParams(this.filters);
            const response = await this.apiRequest('GET', `/reports/dashboard?${queryParams}`);

            if (response.success) {
                this.currentReport = response.data;
                this.renderPainel de ControleReport(response.data);
            } else {
                throw new Error(response.message || 'Erro ao carregar relatório dashboard');
            }

        } catch (error) {
            console.error('Erro ao carregar dashboard:', error);
            this.showToast('Erro ao carregar dashboard executivo', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    async loadVendasReport() {
        try {
            this.showLoading(true);
            this.setActiveTab('sales');
            this.filters.report_type = 'sales';

            const queryParams = new URLBuscarParams(this.filters);
            const response = await this.apiRequest('GET', `/reports/sales?${queryParams}`);

            if (response.success) {
                this.currentReport = response.data;
                this.renderVendasReport(response.data);
            } else {
                throw new Error(response.message || 'Erro ao carregar relatório de vendas');
            }

        } catch (error) {
            console.error('Erro ao carregar relatório de vendas:', error);
            this.showToast('Erro ao carregar relatório de vendas', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    async loadLeadsReport() {
        try {
            this.showLoading(true);
            this.setActiveTab('leads');
            this.filters.report_type = 'leads';

            const queryParams = new URLBuscarParams(this.filters);
            const response = await this.apiRequest('GET', `/reports/leads?${queryParams}`);

            if (response.success) {
                this.currentReport = response.data;
                this.renderLeadsReport(response.data);
            } else {
                throw new Error(response.message || 'Erro ao carregar relatório de leads');
            }

        } catch (error) {
            console.error('Erro ao carregar relatório de leads:', error);
            this.showToast('Erro ao carregar relatório de leads', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    async loadProjectsReport() {
        try {
            this.showLoading(true);
            this.setActiveTab('projects');
            this.filters.report_type = 'projects';

            const queryParams = new URLBuscarParams(this.filters);
            const response = await this.apiRequest('GET', `/reports/projects?${queryParams}`);

            if (response.success) {
                this.currentReport = response.data;
                this.renderProjectsReport(response.data);
            } else {
                throw new Error(response.message || 'Erro ao carregar relatório de projetos');
            }

        } catch (error) {
            console.error('Erro ao carregar relatório de projetos:', error);
            this.showToast('Erro ao carregar relatório de projetos', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    async loadCustomersReport() {
        try {
            this.showLoading(true);
            this.setActiveTab('customers');
            this.filters.report_type = 'customers';

            const queryParams = new URLBuscarParams(this.filters);
            const response = await this.apiRequest('GET', `/reports/customers?${queryParams}`);

            if (response.success) {
                this.currentReport = response.data;
                this.renderCustomersReport(response.data);
            } else {
                throw new Error(response.message || 'Erro ao carregar relatório de clientes');
            }

        } catch (error) {
            console.error('Erro ao carregar relatório de clientes:', error);
            this.showToast('Erro ao carregar relatório de clientes', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    async loadFinancialReport() {
        try {
            this.showLoading(true);
            this.setActiveTab('financial');
            this.filters.report_type = 'financial';

            const queryParams = new URLBuscarParams(this.filters);
            const response = await this.apiRequest('GET', `/reports/financial?${queryParams}`);

            if (response.success) {
                this.currentReport = response.data;
                this.renderFinancialReport(response.data);
            } else {
                throw new Error(response.message || 'Erro ao carregar relatório financeiro');
            }

        } catch (error) {
            console.error('Erro ao carregar relatório financeiro:', error);
            this.showToast('Erro ao carregar relatório financeiro', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    renderPainel de ControleReport(data) {
        // Renderizar métricas principais
        this.renderGeneralMetrics(data.general_metrics);
        
        // Renderizar gráfico de evolução temporal
        this.renderTimeEvolutionChart(data.time_evolution);
        
        // Renderizar top performers
        this.renderTopPerformers(data.top_performers);
        
        // Renderizar distribuições
        this.renderDistributions(data.distributions);
    }

    renderGeneralMetrics(metrics) {
        if (!metrics) return;

        // Atualizar cards de métricas
        this.updateMetricCard('totalCustomersMetric', metrics.total_customers);
        this.updateMetricCard('totalLeadsMetric', metrics.total_leads);
        this.updateMetricCard('totalProjectsMetric', metrics.total_projects);
        this.updateMetricCard('totalOrdersMetric', metrics.total_orders);
        this.updateMetricCard('totalReceitaMetric', this.formatCurrency(metrics.total_revenue));
        this.updateMetricCard('conversionRateMetric', `${metrics.conversion_rate}%`);
        this.updateMetricCard('avgOrderValueMetric', this.formatCurrency(metrics.avg_order_value));
        this.updateMetricCard('convertedLeadsMetric', metrics.converted_leads);
    }

    updateMetricCard(id, value) {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = value;
            element.classList.add('metric-updated');
            setTimeout(() => element.classList.remove('metric-updated'), 500);
        }
    }

    renderTimeEvolutionChart(data) {
        if (!data || data.length === 0) return;

        const ctx = document.getElementById('timeEvolutionChart')?.getContext('2d');
        if (!ctx) return;

        // Destruir gráfico anterior se existir
        if (this.charts.timeEvolution) {
            this.charts.timeEvolution.destroy();
        }

        const labels = data.map(item => this.formatPeriodLabel(item.period));
        const revenueData = data.map(item => parseFloat(item.revenue || 0));
        const ordersData = data.map(item => parseInt(item.orders_count || 0));
        const leadsData = data.map(item => parseInt(item.leads_count || 0));

        this.charts.timeEvolution = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Receita (R$)',
                        data: revenueData,
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        yAxisID: 'y'
                    },
                    {
                        label: 'Pedidos',
                        data: ordersData,
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        yAxisID: 'y1'
                    },
                    {
                        label: 'Leads',
                        data: leadsData,
                        borderColor: '#fd7e14',
                        backgroundColor: 'rgba(253, 126, 20, 0.1)',
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Evolução Temporal - Vendas, Pedidos e Leads'
                    },
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Receita (R$)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Quantidade'
                        },
                        grid: {
                            drawOnChartArea: false,
                        }
                    }
                },
                interaction: {
                    mode: 'index',
                    intersect: false,
                }
            }
        });
    }

    renderVendasReport(data) {
        // Renderizar gráfico de vendas por período
        this.renderVendasByPeriodChart(data.sales_by_period);
        
        // Renderizar vendas por produto
        this.renderVendasByProductChart(data.sales_by_product);
        
        // Renderizar métricas de performance
        this.renderVendasPerformanceMetrics(data.performance_metrics);
        
        // Renderizar tabela de vendas por cliente
        this.renderVendasByCustomerTable(data.sales_by_customer);
    }

    renderVendasByPeriodChart(data) {
        if (!data || data.length === 0) return;

        const ctx = document.getElementById('salesByPeriodChart')?.getContext('2d');
        if (!ctx) return;

        // Destruir gráfico anterior se existir
        if (this.charts.salesByPeriod) {
            this.charts.salesByPeriod.destroy();
        }

        const labels = data.map(item => this.formatPeriodLabel(item.period));
        const revenueData = data.map(item => parseFloat(item.total_revenue || 0));
        const ordersData = data.map(item => parseInt(item.orders_count || 0));

        this.charts.salesByPeriod = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Receita (R$)',
                        data: revenueData,
                        backgroundColor: '#28a745',
                        yAxisID: 'y'
                    },
                    {
                        label: 'Número de Pedidos',
                        data: ordersData,
                        backgroundColor: '#007bff',
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Vendas por Período'
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Receita (R$)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Número de Pedidos'
                        },
                        grid: {
                            drawOnChartArea: false,
                        }
                    }
                }
            }
        });
    }

    renderVendasByProductChart(data) {
        if (!data || data.length === 0) return;

        const ctx = document.getElementById('salesByProductChart')?.getContext('2d');
        if (!ctx) return;

        // Destruir gráfico anterior se existir
        if (this.charts.salesByProduct) {
            this.charts.salesByProduct.destroy();
        }

        const labels = data.slice(0, 10).map(item => item.product_name);
        const revenueData = data.slice(0, 10).map(item => parseFloat(item.total_revenue || 0));

        this.charts.salesByProduct = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Receita por Produto',
                    data: revenueData,
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                        '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF',
                        '#4BC0C0', '#FF6384'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Top 10 Produtos por Receita'
                    },
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
    }

    renderLeadsReport(data) {
        // Renderizar funil de conversão
        this.renderConversionFunnelChart(data.conversion_pipeline);
        
        // Renderizar leads por fonte
        this.renderLeadsBySourceChart(data.leads_by_source);
        
        // Renderizar métricas de conversão
        this.renderConversionMetrics(data.conversion_rates);
    }

    renderConversionFunnelChart(data) {
        if (!data || data.length === 0) return;

        const ctx = document.getElementById('conversionFunnelChart')?.getContext('2d');
        if (!ctx) return;

        // Destruir gráfico anterior se existir
        if (this.charts.conversionFunnel) {
            this.charts.conversionFunnel.destroy();
        }

        // Dados do funil de conversão
        const funnelStages = ['Novos Leads', 'Qualificados', 'Propostas', 'Negociação', 'Convertidos'];
        const funnelData = [100, 75, 50, 30, 20]; // Exemplo percentual

        this.charts.conversionFunnel = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: funnelStages,
                datasets: [{
                    label: 'Taxa de Conversão (%)',
                    data: funnelData,
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'
                    ]
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Funil de Conversão de Leads'
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Porcentagem (%)'
                        }
                    }
                }
            }
        });
    }

    async exportReport(format) {
        try {
            this.showLoading(true);

            const formData = new FormData();
            formData.append('report_type', this.filters.report_type);
            formData.append('format', format);
            formData.append('filters', JSON.stringify(this.filters));

            const response = await fetch(this.apiUrl + '/reports/export', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (response.ok) {
                // Criar download do arquivo
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `relatorio_${this.filters.report_type}_${Date.now()}.${format}`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);

                this.showToast(`Relatório exportado em ${format.toUpperCase()} com sucesso`, 'success');
            } else {
                throw new Error('Erro na exportação do relatório');
            }

        } catch (error) {
            console.error('Erro ao exportar relatório:', error);
            this.showToast('Erro ao exportar relatório', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    loadCurrentReport() {
        switch (this.filters.report_type) {
            case 'dashboard':
                this.loadPainel de ControleReport();
                break;
            case 'sales':
                this.loadVendasReport();
                break;
            case 'leads':
                this.loadLeadsReport();
                break;
            case 'projects':
                this.loadProjectsReport();
                break;
            case 'customers':
                this.loadCustomersReport();
                break;
            case 'financial':
                this.loadFinancialReport();
                break;
            default:
                this.loadPainel de ControleReport();
        }
    }

    setActiveTab(tabName) {
        // Remover classe ativa de todas as abas
        document.querySelectorAll('.report-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Adicionar classe ativa à aba atual
        const activeTab = document.querySelector(`[data-tab="${tabName}"]`);
        if (activeTab) {
            activeTab.classList.add('active');
        }

        // Mostrar/ocultar conteúdo das abas
        document.querySelectorAll('.tab-content').forEach(content => {
            content.style.display = 'none';
        });
        
        const activeContent = document.getElementById(`${tabName}Tab`);
        if (activeContent) {
            activeContent.style.display = 'block';
        }
    }

    handleTabClick(event) {
        event.preventDefault();
        const tabName = event.currentTarget.getAttribute('data-tab');
        if (tabName) {
            this.setActiveTab(tabName);
            this.filters.report_type = tabName;
            this.loadCurrentReport();
        }
    }

    formatPeriodLabel(period) {
        if (!period) return '';
        
        // Formatação baseada no tipo de período
        if (period.match(/^\d{4}-\d{2}-\d{2}$/)) {
            // Formato data (YYYY-MM-DD)
            const date = new Date(period);
            return date.toLocaleDateString('pt-BR');
        } else if (period.match(/^\d{4}-\d{2}$/)) {
            // Formato mês (YYYY-MM)
            const [year, month] = period.split('-');
            const date = new Date(year, month - 1);
            return date.toLocaleDateString('pt-BR', { year: 'numeric', month: 'long' });
        } else if (period.match(/^\d{4}$/)) {
            // Formato ano (YYYY)
            return period;
        }
        
        return period;
    }

    formatCurrency(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value || 0);
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
        
        // Criar toast bootstrap se disponível
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

    // Métodos de renderização adicionais
    renderTopPerformers(data) { /* Implementar top performers */ }
    renderDistributions(data) { /* Implementar distribuições */ }
    renderVendasPerformanceMetrics(data) { /* Implementar métricas de performance */ }
    renderVendasByCustomerTable(data) { /* Implementar tabela de clientes */ }
    renderLeadsBySourceChart(data) { /* Implementar leads por fonte */ }
    renderConversionMetrics(data) { /* Implementar métricas de conversão */ }
    renderProjectsReport(data) { /* Implementar relatório de projetos */ }
    renderCustomersReport(data) { /* Implementar relatório de clientes */ }
    renderFinancialReport(data) { /* Implementar relatório financeiro */ }
}

// Inicializar quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    window.duraluxReports = new DuraluxReports();
});