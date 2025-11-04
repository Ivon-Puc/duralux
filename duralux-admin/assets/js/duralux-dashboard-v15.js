/**
 * DURALUX CRM - Dashboard Analytics JavaScript v1.5
 * Sistema Avançado de Dashboard Executivo
 * 
 * Features Avançadas:
 * - KPIs dinâmicos com comparação temporal em tempo real
 * - Gráficos interativos com Chart.js e ApexCharts
 * - Alertas inteligentes e notificações push
 * - Dashboard personalizável e responsivo
 * - Previsões e análise de tendências
 * - Interface executiva moderna
 * 
 * @author Duralux Development Team
 * @version 1.5.0
 * @since 2025-11-04
 */

'use strict';

class DuraluxDashboard {
    constructor() {
        console.log('🚀 Inicializando Duralux Dashboard Analytics v1.5...');
        
        // Configurações da API
        this.apiBase = '../backend/api/router.php';
        this.refreshInterval = 30000; // 30 segundos
        this.charts = {};
        this.settings = {};
        
        // Estado do dashboard
        this.currentPeriod = '30';
        this.comparisonMode = 'previous';
        this.realTimeMode = false;
        this.lastUpdate = null;
        
        // Configurações de KPIs
        this.kpiConfig = {
            revenue: { format: 'currency', color: 'success', target: 100000 },
            leads: { format: 'number', color: 'primary', target: 100 },
            conversion: { format: 'percentage', color: 'warning', target: 15 },
            customers: { format: 'number', color: 'info', target: 50 }
        };
        
        // Cache de dados
        this.cache = new Map();
        this.cacheTimeout = 60000; // 1 minuto
        
        this.init();
    }

    /**
     * Inicialização do dashboard avançado
     */
    async init() {
        try {
            // Verificar autenticação
            await this.checkAuthentication();
            
            // Carregar configurações do usuário
            await this.loadUserSettings();
            
            // Configurar elementos DOM
            this.setupDOMElements();
            
            // Configurar event listeners
            this.setupEventListeners();
            
            // Carregar dados iniciais
            await this.loadDashboardData();
            
            // Iniciar modo tempo real se habilitado
            if (this.settings.realTimeMode) {
                this.startRealTimeUpdates();
            }
            
            // Configurar auto-refresh
            this.setupAutoRefresh();
            
            console.log('✅ Dashboard Analytics v1.5 inicializado com sucesso!');
            this.showNotification('Dashboard Executivo carregado com sucesso!', 'success');
            
        } catch (error) {
            console.error('❌ Erro ao inicializar dashboard:', error);
            this.showNotification('Erro ao carregar dashboard: ' + error.message, 'error');
        }
    }

    /**
     * Configurar elementos DOM
     */
    setupDOMElements() {
        this.elements = {
            // Controles principais
            periodSelector: document.getElementById('periodSelector'),
            comparisonSelector: document.getElementById('comparisonSelector'),
            refreshButton: document.getElementById('refreshButton'),
            realTimeToggle: document.getElementById('realTimeToggle'),
            
            // Containers de KPIs
            kpiContainer: document.getElementById('kpiContainer'),
            kpiCards: document.querySelectorAll('.kpi-card'),
            
            // Containers de gráficos
            revenueChart: document.getElementById('revenueChart'),
            conversionChart: document.getElementById('conversionChart'),
            trendsChart: document.getElementById('trendsChart'),
            performanceRadar: document.getElementById('performanceRadar'),
            
            // Alertas e notificações
            alertsContainer: document.getElementById('alertsContainer'),
            notificationsPanel: document.getElementById('notificationsPanel'),
            
            // Métricas em tempo real
            realTimeMetrics: document.getElementById('realTimeMetrics'),
            lastUpdateTime: document.getElementById('lastUpdateTime'),
            
            // Loading e estados
            loadingOverlay: document.getElementById('loadingOverlay'),
            errorContainer: document.getElementById('errorContainer')
        };
        
        // Verificar se elementos essenciais existem
        if (!this.elements.kpiContainer) {
            throw new Error('Elementos essenciais do dashboard não encontrados');
        }
    }

    /**
     * Configurar event listeners
     */
    setupEventListeners() {
        // Seletor de período
        if (this.elements.periodSelector) {
            this.elements.periodSelector.addEventListener('change', (e) => {
                this.currentPeriod = e.target.value;
                this.loadDashboardData();
            });
        }

        // Modo de comparação
        if (this.elements.comparisonSelector) {
            this.elements.comparisonSelector.addEventListener('change', (e) => {
                this.comparisonMode = e.target.value;
                this.loadDashboardData();
            });
        }

        // Botão de refresh manual
        if (this.elements.refreshButton) {
            this.elements.refreshButton.addEventListener('click', () => {
                this.clearCache();
                this.loadDashboardData();
            });
        }

        // Toggle tempo real
        if (this.elements.realTimeToggle) {
            this.elements.realTimeToggle.addEventListener('change', (e) => {
                this.toggleRealTimeMode(e.target.checked);
            });
        }

        // Redimensionamento da janela
        window.addEventListener('resize', () => {
            this.debounce(this.resizeCharts.bind(this), 250)();
        });

        // Visibilidade da página para pausar/retomar atualizações
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.pauseUpdates();
            } else {
                this.resumeUpdates();
            }
        });
    }

    /**
     * Carregar todos os dados do dashboard
     */
    async loadDashboardData() {
        try {
            this.showLoading(true);
            
            // Carregar dados em paralelo
            const [
                executiveDashboard,
                smartAlerts,
                realTimeMetrics
            ] = await Promise.all([
                this.loadExecutiveDashboard(),
                this.loadSmartAlerts(),
                this.loadRealTimeMetrics()
            ]);

            // Renderizar componentes
            await this.renderKPIs(executiveDashboard.kpis);
            await this.renderCharts(executiveDashboard.charts_data);
            await this.renderAlerts(smartAlerts);
            await this.renderRealTimeMetrics(realTimeMetrics);
            await this.renderTrends(executiveDashboard.trends);
            
            // Atualizar timestamp
            this.updateLastUpdateTime();
            
        } catch (error) {
            console.error('Erro ao carregar dados do dashboard:', error);
            this.showError('Erro ao carregar dados: ' + error.message);
        } finally {
            this.showLoading(false);
        }
    }

    /**
     * Carregar dashboard executivo via API
     */
    async loadExecutiveDashboard() {
        const cacheKey = `executive_dashboard_${this.currentPeriod}_${this.comparisonMode}`;
        
        if (this.cache.has(cacheKey)) {
            const cached = this.cache.get(cacheKey);
            if (Date.now() - cached.timestamp < this.cacheTimeout) {
                return cached.data;
            }
        }

        const response = await this.apiRequest('get_executive_dashboard', {
            period: this.currentPeriod,
            comparison: this.comparisonMode
        });

        this.cache.set(cacheKey, {
            data: response,
            timestamp: Date.now()
        });

        return response;
    }

    /**
     * Renderizar KPIs avançados
     */
    async renderKPIs(kpis) {
        if (!kpis || !this.elements.kpiContainer) return;

        let kpiHTML = '';
        
        Object.entries(kpis).forEach(([key, kpi]) => {
            const config = this.kpiConfig[key] || {};
            const changeIcon = kpi.change.type === 'increase' ? 'fa-arrow-up' : 
                              kpi.change.type === 'decrease' ? 'fa-arrow-down' : 'fa-minus';
            const changeColor = kpi.change.type === 'increase' ? 'text-success' : 
                               kpi.change.type === 'decrease' ? 'text-danger' : 'text-muted';

            kpiHTML += `
                <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                    <div class="card kpi-card border-0 shadow-sm h-100" data-kpi="${key}">
                        <div class="card-body p-4">
                            <!-- Header do KPI -->
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="kpi-icon">
                                    <div class="avatar-text avatar-lg bg-${config.color}-subtle text-${config.color} rounded">
                                        <i class="${kpi.icon}"></i>
                                    </div>
                                </div>
                                <div class="kpi-status">
                                    <span class="badge bg-${kpi.performance.color}-subtle text-${kpi.performance.color}">
                                        ${kpi.performance.status.toUpperCase()}
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Valores principais -->
                            <div class="kpi-values mb-3">
                                <h3 class="text-dark mb-1 fw-bold">
                                    ${this.formatKPIValue(kpi.current, kpi.unit)}
                                </h3>
                                <p class="text-muted mb-0 small">${kpi.name}</p>
                            </div>
                            
                            <!-- Comparação e mudança -->
                            <div class="kpi-comparison d-flex align-items-center justify-content-between mb-3">
                                <div class="change-indicator ${changeColor}">
                                    <i class="fas ${changeIcon} me-1"></i>
                                    <span class="fw-medium">${Math.abs(kpi.change.value)}%</span>
                                    <small class="text-muted ms-1">vs período anterior</small>
                                </div>
                                <div class="performance-indicator">
                                    <span class="small text-muted">${kpi.performance.level}% do target</span>
                                </div>
                            </div>
                            
                            <!-- Barra de progresso do target -->
                            <div class="progress progress-sm mb-3" style="height: 4px;">
                                <div class="progress-bar bg-${kpi.performance.color}" 
                                     style="width: ${Math.min(100, kpi.performance.level)}%"></div>
                            </div>
                            
                            <!-- Mini gráfico de tendência -->
                            <div class="kpi-trend">
                                <canvas id="trend_${key}" width="100" height="30"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        this.elements.kpiContainer.innerHTML = kpiHTML;
        
        // Renderizar mini gráficos de tendência
        Object.entries(kpis).forEach(([key, kpi]) => {
            this.renderKPITrend(key, kpi.trend);
        });
    }

    /**
     * Renderizar mini gráfico de tendência para KPI
     */
    renderKPITrend(kpiKey, trendData) {
        const canvas = document.getElementById(`trend_${kpiKey}`);
        if (!canvas || !trendData) return;

        const ctx = canvas.getContext('2d');
        const config = this.kpiConfig[kpiKey] || {};
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: trendData.map(item => item.period),
                datasets: [{
                    data: trendData.map(item => item.value),
                    borderColor: this.getColorByName(config.color),
                    backgroundColor: this.getColorByName(config.color, 0.1),
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: false }
                },
                scales: {
                    x: { display: false },
                    y: { display: false }
                },
                elements: {
                    point: { radius: 0 }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
    }

    /**
     * Renderizar gráficos principais
     */
    async renderCharts(chartsData) {
        if (!chartsData) return;

        try {
            // Gráfico principal de receita
            if (chartsData.revenue_timeline && this.elements.revenueChart) {
                this.renderRevenueChart(chartsData.revenue_timeline);
            }

            // Gráfico de funil de conversão
            if (chartsData.conversion_funnel && this.elements.conversionChart) {
                this.renderConversionChart(chartsData.conversion_funnel);
            }

            // Gráfico radar de performance
            if (chartsData.performance_radar && this.elements.performanceRadar) {
                this.renderPerformanceRadar(chartsData.performance_radar);
            }

            // Gráfico de tendências comparativas
            if (chartsData.trend_comparison && this.elements.trendsChart) {
                this.renderTrendsChart(chartsData.trend_comparison);
            }

        } catch (error) {
            console.error('Erro ao renderizar gráficos:', error);
        }
    }

    /**
     * Gráfico principal de receita com previsões
     */
    renderRevenueChart(data) {
        const ctx = this.elements.revenueChart.getContext('2d');
        
        if (this.charts.revenue) {
            this.charts.revenue.destroy();
        }

        this.charts.revenue = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(item => this.formatDate(item.date)),
                datasets: [
                    {
                        label: 'Receita Diária',
                        data: data.map(item => item.revenue),
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Média Móvel (7 dias)',
                        data: this.calculateMovingAverage(data.map(item => item.revenue), 7),
                        borderColor: '#17a2b8',
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        fill: false,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Evolução da Receita - Últimos ' + this.currentPeriod + ' Dias',
                        font: { size: 16, weight: 'bold' }
                    },
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: (context) => {
                                return context.dataset.label + ': ' + this.formatCurrency(context.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Data'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Receita (R$)'
                        },
                        ticks: {
                            callback: (value) => this.formatCurrency(value)
                        }
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
     * Gráfico de funil de conversão
     */
    renderConversionChart(data) {
        const ctx = this.elements.conversionChart.getContext('2d');
        
        if (this.charts.conversion) {
            this.charts.conversion.destroy();
        }

        const stages = ['Leads', 'Qualificados', 'Propostas', 'Convertidos'];
        const counts = [
            data.counts.leads,
            data.counts.qualified,
            data.counts.proposal,
            data.counts.converted
        ];
        const colors = ['#007bff', '#28a745', '#ffc107', '#dc3545'];

        this.charts.conversion = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: stages,
                datasets: [{
                    data: counts,
                    backgroundColor: colors,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Funil de Conversão - Pipeline de Vendas',
                        font: { size: 16, weight: 'bold' }
                    },
                    legend: {
                        position: 'right'
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed * 100) / total).toFixed(1);
                                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    /**
     * Renderizar alertas inteligentes
     */
    async renderAlerts(alerts) {
        if (!alerts || !this.elements.alertsContainer) return;

        let alertsHTML = '';
        
        if (alerts.length === 0) {
            alertsHTML = `
                <div class="alert alert-success border-0" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle me-3 fs-4"></i>
                        <div>
                            <h6 class="alert-heading mb-1">Tudo funcionando perfeitamente!</h6>
                            <p class="mb-0">Não há alertas críticos no momento.</p>
                        </div>
                    </div>
                </div>
            `;
        } else {
            alerts.forEach(alert => {
                const alertClass = this.getAlertClass(alert.type);
                const iconClass = this.getAlertIcon(alert.type);
                
                alertsHTML += `
                    <div class="alert ${alertClass} border-0 mb-3" role="alert">
                        <div class="d-flex align-items-start">
                            <i class="${alert.icon || iconClass} me-3 mt-1"></i>
                            <div class="flex-grow-1">
                                <h6 class="alert-heading mb-1">${alert.title}</h6>
                                <p class="mb-2">${alert.message}</p>
                                ${alert.action ? `
                                    <div class="alert-action">
                                        <small class="text-muted">
                                            <strong>Ação recomendada:</strong> ${alert.action}
                                        </small>
                                    </div>
                                ` : ''}
                                <small class="text-muted d-block mt-2">
                                    ${this.formatRelativeTime(alert.timestamp)}
                                </small>
                            </div>
                            <button type="button" class="btn-close" onclick="this.parentElement.parentElement.remove()"></button>
                        </div>
                    </div>
                `;
            });
        }

        this.elements.alertsContainer.innerHTML = alertsHTML;
    }

    /**
     * Renderizar métricas em tempo real
     */
    async renderRealTimeMetrics(metrics) {
        if (!metrics || !this.elements.realTimeMetrics) return;

        const metricsHTML = `
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <h4 class="mb-0">${metrics.active_users || 0}</h4>
                            <small>Usuários Ativos</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h4 class="mb-0">${this.formatCurrency(metrics.today_revenue || 0)}</h4>
                            <small>Receita Hoje</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body text-center">
                            <h4 class="mb-0">${metrics.pending_tasks || 0}</h4>
                            <small>Tarefas Pendentes</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <div class="d-flex align-items-center justify-content-center">
                                <i class="fas fa-circle text-success me-2" style="font-size: 8px;"></i>
                                <span>Sistema Saudável</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        this.elements.realTimeMetrics.innerHTML = metricsHTML;
    }

    /**
     * Fazer requisição para API
     */
    async apiRequest(action, data = {}) {
        try {
            const response = await fetch(this.apiBase, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    action: action,
                    ...data
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP Error: ${response.status}`);
            }

            const result = await response.json();
            
            if (result.error) {
                throw new Error(result.error);
            }

            return result.data || result;

        } catch (error) {
            console.error(`API Request Error (${action}):`, error);
            throw error;
        }
    }

    /**
     * Carregar alertas inteligentes
     */
    async loadSmartAlerts() {
        return await this.apiRequest('get_smart_alerts');
    }

    /**
     * Carregar métricas em tempo real
     */
    async loadRealTimeMetrics() {
        return await this.apiRequest('get_real_time_metrics');
    }

    /**
     * Configurar auto-refresh
     */
    setupAutoRefresh() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
        }

        this.refreshTimer = setInterval(() => {
            if (!document.hidden && this.realTimeMode) {
                this.loadRealTimeMetrics().then(metrics => {
                    this.renderRealTimeMetrics(metrics);
                    this.updateLastUpdateTime();
                });
            }
        }, this.refreshInterval);
    }

    /**
     * Alternar modo tempo real
     */
    toggleRealTimeMode(enabled) {
        this.realTimeMode = enabled;
        
        if (enabled) {
            this.startRealTimeUpdates();
            this.showNotification('Modo tempo real ativado', 'info');
        } else {
            this.stopRealTimeUpdates();
            this.showNotification('Modo tempo real desativado', 'info');
        }
    }

    /**
     * Iniciar atualizações em tempo real
     */
    startRealTimeUpdates() {
        this.realTimeMode = true;
        this.setupAutoRefresh();
    }

    /**
     * Parar atualizações em tempo real
     */
    stopRealTimeUpdates() {
        this.realTimeMode = false;
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
        }
    }

    // ==========================================
    // MÉTODOS AUXILIARES E UTILITÁRIOS
    // ==========================================

    /**
     * Formatar valor de KPI baseado no tipo
     */
    formatKPIValue(value, unit) {
        switch (unit) {
            case 'BRL':
                return this.formatCurrency(value);
            case 'percentage':
                return (value * 100).toFixed(1) + '%';
            case 'count':
                return this.formatNumber(value);
            default:
                return value.toString();
        }
    }

    /**
     * Formatar moeda brasileira
     */
    formatCurrency(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value || 0);
    }

    /**
     * Formatar número com separadores
     */
    formatNumber(value) {
        return new Intl.NumberFormat('pt-BR').format(value || 0);
    }

    /**
     * Formatar data para exibição
     */
    formatDate(dateString) {
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('pt-BR', {
            day: '2-digit',
            month: '2-digit'
        }).format(date);
    }

    /**
     * Formatar tempo relativo
     */
    formatRelativeTime(timestamp) {
        const now = new Date();
        const time = new Date(timestamp);
        const diff = Math.floor((now - time) / 1000);

        if (diff < 60) return 'Agora há pouco';
        if (diff < 3600) return Math.floor(diff / 60) + ' min atrás';
        if (diff < 86400) return Math.floor(diff / 3600) + ' h atrás';
        return Math.floor(diff / 86400) + ' d atrás';
    }

    /**
     * Obter classe CSS para alertas
     */
    getAlertClass(type) {
        const classes = {
            'critical': 'alert-danger',
            'warning': 'alert-warning',
            'info': 'alert-info',
            'success': 'alert-success'
        };
        return classes[type] || 'alert-secondary';
    }

    /**
     * Obter ícone para alertas
     */
    getAlertIcon(type) {
        const icons = {
            'critical': 'fas fa-exclamation-triangle',
            'warning': 'fas fa-exclamation-circle',
            'info': 'fas fa-info-circle',
            'success': 'fas fa-check-circle'
        };
        return icons[type] || 'fas fa-bell';
    }

    /**
     * Obter cor por nome
     */
    getColorByName(colorName, alpha = 1) {
        const colors = {
            'primary': `rgba(0, 123, 255, ${alpha})`,
            'secondary': `rgba(108, 117, 125, ${alpha})`,
            'success': `rgba(40, 167, 69, ${alpha})`,
            'danger': `rgba(220, 53, 69, ${alpha})`,
            'warning': `rgba(255, 193, 7, ${alpha})`,
            'info': `rgba(23, 162, 184, ${alpha})`
        };
        return colors[colorName] || `rgba(108, 117, 125, ${alpha})`;
    }

    /**
     * Calcular média móvel
     */
    calculateMovingAverage(data, window) {
        const result = [];
        for (let i = 0; i < data.length; i++) {
            if (i < window - 1) {
                result.push(null);
            } else {
                const sum = data.slice(i - window + 1, i + 1).reduce((a, b) => a + b, 0);
                result.push(sum / window);
            }
        }
        return result;
    }

    /**
     * Debounce para otimização de performance
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
     * Redimensionar gráficos
     */
    resizeCharts() {
        Object.values(this.charts).forEach(chart => {
            if (chart && typeof chart.resize === 'function') {
                chart.resize();
            }
        });
    }

    /**
     * Limpar cache
     */
    clearCache() {
        this.cache.clear();
    }

    /**
     * Pausar atualizações
     */
    pauseUpdates() {
        this.updatesPaused = true;
    }

    /**
     * Retomar atualizações
     */
    resumeUpdates() {
        this.updatesPaused = false;
        if (this.realTimeMode) {
            this.loadRealTimeMetrics();
        }
    }

    /**
     * Atualizar timestamp da última atualização
     */
    updateLastUpdateTime() {
        this.lastUpdate = new Date();
        if (this.elements.lastUpdateTime) {
            this.elements.lastUpdateTime.textContent = 
                'Última atualização: ' + this.lastUpdate.toLocaleTimeString('pt-BR');
        }
    }

    /**
     * Mostrar loading
     */
    showLoading(show) {
        if (this.elements.loadingOverlay) {
            this.elements.loadingOverlay.style.display = show ? 'flex' : 'none';
        }
    }

    /**
     * Mostrar erro
     */
    showError(message) {
        if (this.elements.errorContainer) {
            this.elements.errorContainer.innerHTML = `
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    ${message}
                </div>
            `;
            this.elements.errorContainer.style.display = 'block';
        }
    }

    /**
     * Mostrar notificação
     */
    showNotification(message, type = 'info') {
        // Implementar sistema de notificações toast
        console.log(`[${type.toUpperCase()}] ${message}`);
    }

    /**
     * Verificar autenticação
     */
    async checkAuthentication() {
        try {
            await this.apiRequest('check_auth');
            return true;
        } catch (error) {
            window.location.href = '/login';
            return false;
        }
    }

    /**
     * Carregar configurações do usuário
     */
    async loadUserSettings() {
        try {
            this.settings = await this.apiRequest('get_dashboard_settings');
            
            // Aplicar configurações
            if (this.settings.refresh_interval) {
                this.refreshInterval = this.settings.refresh_interval;
            }
            
            if (this.settings.realTimeMode) {
                this.realTimeMode = this.settings.realTimeMode;
            }
            
        } catch (error) {
            console.warn('Erro ao carregar configurações do usuário:', error);
            // Usar configurações padrão
            this.settings = {
                layout: 'executive',
                refresh_interval: 30000,
                realTimeMode: false
            };
        }
    }

    // Métodos auxiliares vazios para implementação futura
    renderTrends(trends) { /* Implementar análise de tendências */ }
    renderPerformanceRadar(data) { /* Implementar gráfico radar */ }
    renderTrendsChart(data) { /* Implementar gráfico de tendências */ }
}

// Inicialização automática quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    window.duraluxDashboard = new DuraluxDashboard();
});

// Exportar para uso global
window.DuraluxDashboard = DuraluxDashboard;