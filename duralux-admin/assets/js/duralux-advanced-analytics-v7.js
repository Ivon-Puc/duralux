/**
 * DURALUX CRM - Advanced Analytics Dashboard v7.0
 * Sistema de análises avançadas com gráficos interativos
 * 
 * @author Duralux Development Team
 * @version 7.0
 * @requires Chart.js, Bootstrap 5
 */

class DuraluxAdvancedAnalytics {
    constructor() {
        this.apiBaseUrl = '../backend/api/api-analytics.php';
        this.charts = {};
        this.refreshInterval = 300000; // 5 minutos
        this.realTimeInterval = 60000; // 1 minuto para dados em tempo real
        this.config = {
            dateFormat: 'YYYY-MM-DD',
            currency: 'BRL',
            locale: 'pt-BR'
        };
        
        this.init();
    }
    
    /**
     * Inicialização do sistema
     */
    async init() {
        try {
            console.log('🚀 Inicializando Duralux Advanced Analytics v7.0');
            
            this.setupEventListeners();
            await this.loadDashboard();
            this.startRealTimeUpdates();
            this.setupChartResizing();
            
            console.log('✅ Analytics Dashboard inicializado com sucesso');
        } catch (error) {
            console.error('❌ Erro na inicialização:', error);
            this.showError('Erro ao inicializar dashboard de análises');
        }
    }
    
    /**
     * Configura event listeners
     */
    setupEventListeners() {
        // Filtros de data
        const dateRangePicker = document.getElementById('analyticsDateRange');
        if (dateRangePicker) {
            dateRangePicker.addEventListener('change', () => this.updateDashboard());
        }
        
        // Botão de atualização manual
        const refreshBtn = document.getElementById('btnRefreshAnalytics');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.loadDashboard(true));
        }
        
        // Exportar relatórios
        const exportBtn = document.getElementById('btnExportReport');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => this.exportReport());
        }
        
        // Toggle de visualização em tempo real
        const realTimeToggle = document.getElementById('realTimeToggle');
        if (realTimeToggle) {
            realTimeToggle.addEventListener('change', (e) => {
                if (e.target.checked) {
                    this.startRealTimeUpdates();
                } else {
                    this.stopRealTimeUpdates();
                }
            });
        }
    }
    
    /**
     * Carrega dashboard principal
     */
    async loadDashboard(forceRefresh = false) {
        try {
            this.showLoading(true);
            
            const dateRange = this.getSelectedDateRange();
            const cacheKey = `dashboard_${JSON.stringify(dateRange)}`;
            
            let data;
            if (!forceRefresh && sessionStorage.getItem(cacheKey)) {
                data = JSON.parse(sessionStorage.getItem(cacheKey));
            } else {
                const response = await this.apiCall('dashboard-metrics', {
                    start_date: dateRange.start,
                    end_date: dateRange.end
                });
                data = response.data;
                
                // Cache por 5 minutos
                sessionStorage.setItem(cacheKey, JSON.stringify(data));
                setTimeout(() => sessionStorage.removeItem(cacheKey), 300000);
            }
            
            await Promise.all([
                this.updateKPICards(data),
                this.updateCharts(data),
                this.updateTables(data),
                this.updateTrends(data)
            ]);
            
            this.updateLastRefresh();
            
        } catch (error) {
            console.error('Erro ao carregar dashboard:', error);
            this.showError('Erro ao carregar dados do dashboard');
        } finally {
            this.showLoading(false);
        }
    }
    
    /**
     * Atualiza cards de KPIs
     */
    async updateKPICards(data) {
        const kpis = [
            {
                id: 'totalLeads',
                value: data.leads?.total_leads || 0,
                format: 'number',
                trend: this.calculateTrend(data.leads?.daily_leads),
                target: 100
            },
            {
                id: 'conversionRate',
                value: data.leads?.conversion_rate || 0,
                format: 'percentage',
                trend: data.trends?.conversion_trend || 0,
                target: 15
            },
            {
                id: 'monthlyRevenue',
                value: data.revenue?.total_revenue || 0,
                format: 'currency',
                trend: data.trends?.revenue_trend || 0,
                target: 50000
            },
            {
                id: 'activeCustomers',
                value: data.customers?.active_customers || 0,
                format: 'number',
                trend: this.calculateGrowthRate(data.customers),
                target: 200
            },
            {
                id: 'activeProjects',
                value: data.projects?.active_projects || 0,
                format: 'number',
                trend: data.trends?.projects_trend || 0,
                target: 25
            },
            {
                id: 'avgDealSize',
                value: data.revenue?.avg_deal_size || 0,
                format: 'currency',
                trend: data.trends?.deal_size_trend || 0,
                target: 2500
            }
        ];
        
        kpis.forEach(kpi => this.updateKPICard(kpi));
    }
    
    /**
     * Atualiza um card KPI individual
     */
    updateKPICard(kpi) {
        const card = document.getElementById(kpi.id);
        if (!card) return;
        
        const valueElement = card.querySelector('.kpi-value');
        const trendElement = card.querySelector('.kpi-trend');
        const progressElement = card.querySelector('.kpi-progress');
        
        if (valueElement) {
            valueElement.textContent = this.formatValue(kpi.value, kpi.format);
        }
        
        if (trendElement && kpi.trend !== undefined) {
            const trendClass = kpi.trend >= 0 ? 'trend-up text-success' : 'trend-down text-danger';
            const trendIcon = kpi.trend >= 0 ? '↗️' : '↘️';
            
            trendElement.className = `kpi-trend ${trendClass}`;
            trendElement.innerHTML = `${trendIcon} ${Math.abs(kpi.trend).toFixed(1)}%`;
        }
        
        if (progressElement && kpi.target) {
            const percentage = Math.min(100, (kpi.value / kpi.target) * 100);
            progressElement.style.width = `${percentage}%`;
            progressElement.setAttribute('aria-valuenow', percentage);
        }
    }
    
    /**
     * Atualiza gráficos
     */
    async updateCharts(data) {
        await Promise.all([
            this.updateLeadsChart(data.leads),
            this.updateRevenueChart(data.revenue),
            this.updateProjectsChart(data.projects),
            this.updateConversionFunnelChart(data.leads),
            this.updatePerformanceChart(data.performance)
        ]);
    }
    
    /**
     * Gráfico de leads ao longo do tempo
     */
    async updateLeadsChart(leadsData) {
        const ctx = document.getElementById('leadsChart');
        if (!ctx) return;
        
        if (this.charts.leads) {
            this.charts.leads.destroy();
        }
        
        const chartData = leadsData?.daily_leads || [];
        
        this.charts.leads = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.map(item => this.formatDate(item.date)),
                datasets: [{
                    label: 'Leads Diários',
                    data: chartData.map(item => item.count),
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Data'
                        }
                    },
                    y: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Quantidade de Leads'
                        },
                        beginAtZero: true
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });
    }
    
    /**
     * Gráfico de receita por mês
     */
    async updateRevenueChart(revenueData) {
        const ctx = document.getElementById('revenueChart');
        if (!ctx) return;
        
        if (this.charts.revenue) {
            this.charts.revenue.destroy();
        }
        
        const monthlyData = revenueData?.revenue_by_month || [];
        const forecast = revenueData?.revenue_forecast?.forecast || [];
        
        this.charts.revenue = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [...monthlyData.map(item => item.month), ...forecast.map(item => item.month)],
                datasets: [
                    {
                        label: 'Receita Realizada',
                        data: [...monthlyData.map(item => item.revenue), ...Array(forecast.length).fill(null)],
                        backgroundColor: '#28a745',
                        borderColor: '#1e7e34',
                        borderWidth: 1
                    },
                    {
                        label: 'Previsão',
                        data: [...Array(monthlyData.length).fill(null), ...forecast.map(item => item.forecasted_revenue)],
                        backgroundColor: 'rgba(255, 193, 7, 0.6)',
                        borderColor: '#ffc107',
                        borderWidth: 1,
                        borderDash: [5, 5]
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                return `${context.dataset.label}: ${this.formatValue(context.parsed.y, 'currency')}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Mês'
                        }
                    },
                    y: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Receita (R$)'
                        },
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => this.formatValue(value, 'currency')
                        }
                    }
                }
            }
        });
    }
    
    /**
     * Gráfico de distribuição de projetos
     */
    async updateProjectsChart(projectsData) {
        const ctx = document.getElementById('projectsChart');
        if (!ctx) return;
        
        if (this.charts.projects) {
            this.charts.projects.destroy();
        }
        
        const data = [
            { label: 'Em Andamento', value: projectsData?.active_projects || 0, color: '#007bff' },
            { label: 'Concluídos', value: projectsData?.completed_projects || 0, color: '#28a745' },
            { label: 'Pausados', value: projectsData?.on_hold_projects || 0, color: '#ffc107' },
            { label: 'Atrasados', value: projectsData?.overdue_projects || 0, color: '#dc3545' }
        ];
        
        this.charts.projects = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.map(item => item.label),
                datasets: [{
                    data: data.map(item => item.value),
                    backgroundColor: data.map(item => item.color),
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
    
    /**
     * Funil de conversão
     */
    async updateConversionFunnelChart(leadsData) {
        const ctx = document.getElementById('conversionFunnelChart');
        if (!ctx) return;
        
        if (this.charts.funnel) {
            this.charts.funnel.destroy();
        }
        
        const funnelData = [
            { stage: 'Total de Leads', count: leadsData?.total_leads || 0 },
            { stage: 'Leads Qualificados', count: leadsData?.qualified_leads || 0 },
            { stage: 'Propostas Enviadas', count: Math.floor((leadsData?.qualified_leads || 0) * 0.7) },
            { stage: 'Leads Convertidos', count: leadsData?.converted_leads || 0 }
        ];
        
        this.charts.funnel = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: funnelData.map(item => item.stage),
                datasets: [{
                    label: 'Quantidade',
                    data: funnelData.map(item => item.count),
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(255, 159, 64, 0.8)',
                        'rgba(75, 192, 192, 0.8)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(75, 192, 192, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                const value = context.parsed.x;
                                const total = funnelData[0].count;
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return `${value} leads (${percentage}% do total)`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Quantidade de Leads'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Estágio do Funil'
                        }
                    }
                }
            }
        });
    }
    
    /**
     * Atualiza dados em tempo real
     */
    async updateRealTimeData() {
        try {
            const response = await this.apiCall('real-time-stats');
            const data = response.data;
            
            // Atualiza indicadores em tempo real
            this.updateRealTimeIndicators(data);
            
            // Atualiza atividades recentes
            this.updateLatestActivities(data.latest_activities);
            
            // Atualiza status do sistema
            this.updateSystemHealth(data.system_health);
            
        } catch (error) {
            console.warn('Erro ao atualizar dados em tempo real:', error);
        }
    }
    
    /**
     * Inicia atualizações em tempo real
     */
    startRealTimeUpdates() {
        if (this.realTimeTimer) {
            clearInterval(this.realTimeTimer);
        }
        
        this.realTimeTimer = setInterval(() => {
            this.updateRealTimeData();
        }, this.realTimeInterval);
        
        // Primeira atualização imediata
        this.updateRealTimeData();
    }
    
    /**
     * Para atualizações em tempo real
     */
    stopRealTimeUpdates() {
        if (this.realTimeTimer) {
            clearInterval(this.realTimeTimer);
            this.realTimeTimer = null;
        }
    }
    
    /**
     * Gera relatório customizado
     */
    async generateCustomReport(config) {
        try {
            this.showLoading(true, 'Gerando relatório...');
            
            const response = await this.apiCall('custom-report', {
                config: config
            }, 'POST');
            
            return response.data;
            
        } catch (error) {
            console.error('Erro ao gerar relatório:', error);
            this.showError('Erro ao gerar relatório customizado');
        } finally {
            this.showLoading(false);
        }
    }
    
    /**
     * Exporta relatório para PDF
     */
    async exportReport(reportId = null) {
        try {
            if (!reportId) {
                // Gera relatório padrão se não fornecido
                const defaultConfig = {
                    name: 'Relatório Executivo',
                    type: 'executive',
                    sections: [
                        { type: 'metrics', name: 'kpis' },
                        { type: 'chart', name: 'revenue_chart' },
                        { type: 'chart', name: 'leads_chart' },
                        { type: 'table', name: 'top_customers' }
                    ],
                    date_range: this.getSelectedDateRange()
                };
                
                const report = await this.generateCustomReport(defaultConfig);
                reportId = report.report_id;
            }
            
            const response = await this.apiCall('export-pdf', {
                report_id: reportId,
                template: 'executive'
            }, 'POST');
            
            // Trigger download
            if (response.data.filename) {
                this.downloadFile(response.data.filename, response.data.html);
            }
            
        } catch (error) {
            console.error('Erro ao exportar relatório:', error);
            this.showError('Erro ao exportar relatório');
        }
    }
    
    /**
     * Métodos utilitários
     */
    
    formatValue(value, format) {
        if (value === null || value === undefined) return '-';
        
        switch (format) {
            case 'currency':
                return new Intl.NumberFormat(this.config.locale, {
                    style: 'currency',
                    currency: this.config.currency
                }).format(value);
                
            case 'percentage':
                return `${parseFloat(value).toFixed(1)}%`;
                
            case 'number':
                return new Intl.NumberFormat(this.config.locale).format(value);
                
            default:
                return value.toString();
        }
    }
    
    formatDate(date) {
        return new Date(date).toLocaleDateString(this.config.locale);
    }
    
    getSelectedDateRange() {
        const picker = document.getElementById('analyticsDateRange');
        if (picker && picker.value) {
            const [start, end] = picker.value.split(' - ');
            return { start, end };
        }
        
        // Default: últimos 30 dias
        const end = new Date();
        const start = new Date();
        start.setDate(start.getDate() - 30);
        
        return {
            start: start.toISOString().split('T')[0],
            end: end.toISOString().split('T')[0]
        };
    }
    
    calculateTrend(dailyData) {
        if (!dailyData || dailyData.length < 2) return 0;
        
        const recent = dailyData.slice(-7).reduce((sum, item) => sum + item.count, 0);
        const previous = dailyData.slice(-14, -7).reduce((sum, item) => sum + item.count, 0);
        
        if (previous === 0) return recent > 0 ? 100 : 0;
        
        return ((recent - previous) / previous) * 100;
    }
    
    async apiCall(endpoint, params = {}, method = 'GET') {
        const url = new URL(`${this.apiBaseUrl}/${endpoint}`, window.location.origin);
        
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            }
        };
        
        if (method === 'GET') {
            Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));
        } else {
            options.body = JSON.stringify(params);
        }
        
        const response = await fetch(url, options);
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Erro na API');
        }
        
        return data;
    }
    
    showLoading(show, message = 'Carregando...') {
        const loader = document.getElementById('analyticsLoader');
        if (loader) {
            loader.style.display = show ? 'block' : 'none';
            const text = loader.querySelector('.loading-text');
            if (text) text.textContent = message;
        }
    }
    
    showError(message) {
        const alert = document.createElement('div');
        alert.className = 'alert alert-danger alert-dismissible fade show';
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        const container = document.querySelector('.analytics-container') || document.body;
        container.insertBefore(alert, container.firstChild);
        
        setTimeout(() => alert.remove(), 5000);
    }
    
    updateLastRefresh() {
        const element = document.getElementById('lastRefreshTime');
        if (element) {
            element.textContent = new Date().toLocaleTimeString(this.config.locale);
        }
    }
    
    setupChartResizing() {
        window.addEventListener('resize', () => {
            Object.values(this.charts).forEach(chart => {
                if (chart && typeof chart.resize === 'function') {
                    chart.resize();
                }
            });
        });
    }
}

// Inicialização global
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('analyticsContainer')) {
        window.duraluxAnalytics = new DuraluxAdvancedAnalytics();
    }
});

// Export para uso em módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DuraluxAdvancedAnalytics;
}