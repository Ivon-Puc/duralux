/**
 * DURALUX CRM - Performance Painel de Controle JavaScript v4.0
 * Sistema avançado de monitoramento de performance em tempo real
 * 
 * Features:
 * - Painel de Controle em tempo real com WebSockets
 * - Gráficos interativos de performance
 * - Alertas visuais e notificações
 * - Análise de tendências
 * - Controles de otimização
 * - Exportararação de relatórios
 * 
 * Dependencies: Chart.js, Socket.IO (opcional)
 * 
 * @author Duralux Development Team
 * @version 4.0.0
 */

class DuraluxPerformancePainel de Controle {
    constructor(options = {}) {
        this.options = {
            container: '#performance-dashboard',
            updateInterval: 30000, // 30 segundos
            enableWebSocket: false,
            apiEndpoint: '/backend/api/router.php',
            enableAlerts: true,
            enableExportarar: true,
            theme: 'light',
            ...options
        };
        
        this.charts = {};
        this.updateTimer = null;
        this.isUpdating = false;
        this.lastUpdateTime = null;
        this.alertsEnabled = true;
        this.currentData = null;
        
        this.init();
    }
    
    /**
     * Inicializar dashboard
     */
    init() {
        console.log('🚀 Inicializando Duralux Performance Painel de Controle v4.0');
        
        this.createPainel de ControleStructure();
        this.initializeCharts();
        this.setupEventListeners();
        this.loadInitialData();
        this.startAutoUpdate();
        
        // Configurar WebSocket se habilitado
        if (this.options.enableWebSocket) {
            this.initWebSocket();
        }
    }
    
    /**
     * Criar estrutura HTML do dashboard
     */
    createPainel de ControleStructure() {
        const container = document.querySelector(this.options.container);
        if (!container) {
            console.error('Container do dashboard não encontrado');
            return;
        }
        
        container.innerHTML = `
            <div class="performance-dashboard">
                <!-- Header -->
                <div class="dashboard-header">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h2><i class="fas fa-tachometer-alt"></i> Performance Monitor</h2>
                            <p class="text-muted">Monitoramento em tempo real do sistema</p>
                        </div>
                        <div class="col-md-6 text-end">
                            <div class="btn-group" role="group">
                                <button class="btn btn-primary" id="refreshBtn">
                                    <i class="fas fa-sync-alt"></i> Atualizar
                                </button>
                                <button class="btn btn-success" id="optimizeBtn">
                                    <i class="fas fa-rocket"></i> Otimizar
                                </button>
                                <button class="btn btn-info" id="exportBtn">
                                    <i class="fas fa-download"></i> Exportararar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Status Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="mb-0" id="healthScore">--</h4>
                                        <p class="mb-0">Score de Saúde</p>
                                    </div>
                                    <i class="fas fa-heartbeat fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="mb-0" id="avgResponseTime">--</h4>
                                        <p class="mb-0">Tempo Médio (ms)</p>
                                    </div>
                                    <i class="fas fa-clock fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="mb-0" id="cacheHitRate">--</h4>
                                        <p class="mb-0">Taxa de Cache</p>
                                    </div>
                                    <i class="fas fa-database fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="mb-0" id="activeAlerts">--</h4>
                                        <p class="mb-0">Alertas Ativos</p>
                                    </div>
                                    <i class="fas fa-exclamation-triangle fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Row -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-chart-line"></i> Tempo de Resposta</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="responseTimeChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-memory"></i> Uso de Memória</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="memoryChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-chart-area"></i> Tendências (7 dias)</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="trendsChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-server"></i> Recursos do Sistema</h5>
                            </div>
                            <div class="card-body">
                                <div id="systemResources">
                                    <div class="mb-3">
                                        <label>CPU Usage</label>
                                        <div class="progress">
                                            <div class="progress-bar" id="cpuProgress" style="width: 0%"></div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label>Memory Usage</label>
                                        <div class="progress">
                                            <div class="progress-bar bg-warning" id="memoryProgress" style="width: 0%"></div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label>Disk Usage</label>
                                        <div class="progress">
                                            <div class="progress-bar bg-info" id="diskProgress" style="width: 0%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Alertas e Recomendações -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-bell"></i> Alertas Recentes</h5>
                            </div>
                            <div class="card-body">
                                <div id="alertsList" class="alerts-container">
                                    <p class="text-muted">Carregando alertas...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-lightbulb"></i> Recomendações</h5>
                            </div>
                            <div class="card-body">
                                <div id="recommendationsList" class="recommendations-container">
                                    <p class="text-muted">Carregando recomendações...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Loading Overlay -->
                <div class="loading-overlay" id="loadingOverlay" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <p class="mt-2">Atualizando dados...</p>
                </div>
                
                <!-- Modal de Otimização -->
                <div class="modal fade" id="optimizationModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Otimização do Sistema</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div id="optimizationContent">
                                    <div class="optimization-options">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="optimizeQueries" checked>
                                            <label class="form-check-label" for="optimizeQueries">
                                                <strong>Otimizar Queries</strong><br>
                                                <small class="text-muted">Analisar e otimizar queries lentas</small>
                                            </label>
                                        </div>
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="optimizeAssets" checked>
                                            <label class="form-check-label" for="optimizeAssets">
                                                <strong>Otimizar Assets</strong><br>
                                                <small class="text-muted">Comprimir CSS, JS e imagens</small>
                                            </label>
                                        </div>
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="clearCache">
                                            <label class="form-check-label" for="clearCache">
                                                <strong>Limpar Cache</strong><br>
                                                <small class="text-muted">Remover cache expirado</small>
                                            </label>
                                        </div>
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="cleanupLogs">
                                            <label class="form-check-label" for="cleanupLogs">
                                                <strong>Limpar Logs</strong><br>
                                                <small class="text-muted">Remover logs antigos</small>
                                            </label>
                                        </div>
                                    </div>
                                    <div id="optimizationResults" style="display: none;">
                                        <div class="alert alert-success">
                                            <h6>Resultados da Otimização</h6>
                                            <div id="optimizationResultsContent"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                <button type="button" class="btn btn-primary" id="startOptimization">
                                    <i class="fas fa-rocket"></i> Iniciar Otimização
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    /**
     * Inicializar gráficos
     */
    initializeCharts() {
        // Configurações comuns dos gráficos
        const chartDefaults = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        };
        
        // Gráfico de Tempo de Resposta
        const responseTimeCtx = document.getElementById('responseTimeChart').getContext('2d');
        this.charts.responseTime = new Chart(responseTimeCtx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Tempo de Resposta (ms)',
                    data: [],
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    tension: 0.4
                }]
            },
            options: chartDefaults
        });
        
        // Gráfico de Memória
        const memoryCtx = document.getElementById('memoryChart').getContext('2d');
        this.charts.memory = new Chart(memoryCtx, {
            type: 'doughnut',
            data: {
                labels: ['Usado', 'Disponível'],
                datasets: [{
                    data: [0, 100],
                    backgroundColor: ['#dc3545', '#28a745'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
        
        // Gráfico de Tendências
        const trendsCtx = document.getElementById('trendsChart').getContext('2d');
        this.charts.trends = new Chart(trendsCtx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'Tempo de Resposta',
                        data: [],
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        yAxisID: 'y'
                    },
                    {
                        label: 'Queries Lentas',
                        data: [],
                        borderColor: '#dc3545',
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                }
            }
        });
        
        console.log('📊 Gráficos inicializados');
    }
    
    /**
     * Configurar event listeners
     */
    setupEventListeners() {
        // Botão de refresh
        document.getElementById('refreshBtn')?.addEventListener('click', () => {
            this.refreshData();
        });
        
        // Botão de otimização
        document.getElementById('optimizeBtn')?.addEventListener('click', () => {
            this.showOptimizationModal();
        });
        
        // Botão de export
        document.getElementById('exportBtn')?.addEventListener('click', () => {
            this.exportReport();
        });
        
        // Iniciar otimização
        document.getElementById('startOptimization')?.addEventListener('click', () => {
            this.runOptimization();
        });
        
        // Detectar visibilidade da página
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.pauseUpdates();
            } else {
                this.resumeUpdates();
            }
        });
        
        console.log('🎯 Event listeners configurados');
    }
    
    /**
     * Carregar dados iniciais
     */
    async loadInitialData() {
        try {
            this.showLoading();
            const data = await this.fetchPainel de ControleData();
            this.updatePainel de Controle(data);
            console.log('✅ Dados iniciais carregados');
        } catch (error) {
            console.error('❌ Erro ao carregar dados iniciais:', error);
            this.showError('Erro ao carregar dados do dashboard');
        } finally {
            this.hideLoading();
        }
    }
    
    /**
     * Buscar dados do dashboard
     */
    async fetchPainel de ControleData() {
        const response = await fetch(`${this.options.apiEndpoint}?action=get_performance_dashboard`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        return response.json();
    }
    
    /**
     * Atualizar dashboard com novos dados
     */
    updatePainel de Controle(data) {
        if (!data || data.error) {
            console.error('Dados inválidos recebidos:', data);
            return;
        }
        
        this.currentData = data;
        this.lastUpdateTime = new Date();
        
        // Atualizar cards de status
        this.updateStatusCards(data.overview || {});
        
        // Atualizar gráficos
        this.updateCharts(data);
        
        // Atualizar alertas
        this.updateAlerts(data.alerts || {});
        
        // Atualizar recomendações
        this.updateRecommendations(data.recommendations || []);
        
        // Atualizar recursos do sistema
        this.updateSystemResources(data.resources || {});
        
        console.log('📈 Painel de Controle atualizado:', this.lastUpdateTime);
    }
    
    /**
     * Atualizar cards de status
     */
    updateStatusCards(overview) {
        // Health Score
        const healthScore = overview.health_score || 0;
        document.getElementById('healthScore').textContent = healthScore;
        
        // Tempo de resposta médio
        const avgTime = overview.requests?.avg_response_time || 0;
        document.getElementById('avgResponseTime').textContent = avgTime.toFixed(0);
        
        // Taxa de cache
        const cacheRate = overview.cache?.hit_rate || 0;
        document.getElementById('cacheHitRate').textContent = cacheRate.toFixed(1) + '%';
        
        // Alertas ativos
        const alertCount = overview.alerts?.active_count || 0;
        document.getElementById('activeAlerts').textContent = alertCount;
    }
    
    /**
     * Atualizar gráficos
     */
    updateCharts(data) {
        // Atualizar gráfico de tempo de resposta
        if (data.realtime && this.charts.responseTime) {
            // Simular dados em tempo real
            const now = new Date();
            const timeLabel = now.toLocaleTimeString();
            
            this.charts.responseTime.data.labels.push(timeLabel);
            this.charts.responseTime.data.datasets[0].data.push(
                data.overview?.requests?.avg_response_time || 0
            );
            
            // Manter apenas os últimos 20 pontos
            if (this.charts.responseTime.data.labels.length > 20) {
                this.charts.responseTime.data.labels.shift();
                this.charts.responseTime.data.datasets[0].data.shift();
            }
            
            this.charts.responseTime.update('none');
        }
        
        // Atualizar gráfico de memória
        if (data.overview?.memory && this.charts.memory) {
            const memoryData = data.overview.memory;
            const used = parseInt(memoryData.current_usage) || 0;
            const total = parseInt(memoryData.limit) || 1;
            const usedPercent = (used / total) * 100;
            const freePercent = 100 - usedPercent;
            
            this.charts.memory.data.datasets[0].data = [usedPercent, freePercent];
            this.charts.memory.update('none');
        }
        
        // Atualizar gráfico de tendências
        if (data.trends && this.charts.trends) {
            const trends = data.trends;
            
            if (trends.response_time && trends.response_time.length > 0) {
                this.charts.trends.data.labels = trends.response_time.map(item => 
                    new Date(item.date).toLocaleDateString()
                );
                
                this.charts.trends.data.datasets[0].data = trends.response_time.map(item => 
                    parseFloat(item.avg_response_time) || 0
                );
                
                if (trends.slow_queries && trends.slow_queries.length > 0) {
                    this.charts.trends.data.datasets[1].data = trends.slow_queries.map(item => 
                        parseInt(item.slow_query_count) || 0
                    );
                }
                
                this.charts.trends.update('none');
            }
        }
    }
    
    /**
     * Atualizar alertas
     */
    updateAlerts(alerts) {
        const alertsList = document.getElementById('alertsList');
        if (!alertsList) return;
        
        if (!alerts.recent || alerts.recent.length === 0) {
            alertsList.innerHTML = '<p class="text-muted">Nenhum alerta recente</p>';
            return;
        }
        
        const alertsHtml = alerts.recent.map(alert => {
            const severityClass = this.getSeverityClass(alert.severity);
            const severityIcon = this.getSeverityIcon(alert.severity);
            
            return `
                <div class="alert alert-${severityClass} alert-dismissible fade show" role="alert">
                    <i class="${severityIcon}"></i>
                    <strong>${alert.alert_type}</strong><br>
                    ${alert.message}
                    <small class="d-block mt-1 text-muted">
                        ${new Date(alert.timestamp * 1000).toLocaleString()}
                    </small>
                </div>
            `;
        }).join('');
        
        alertsList.innerHTML = alertsHtml;
    }
    
    /**
     * Atualizar recomendações
     */
    updateRecommendations(recommendations) {
        const recommendationsList = document.getElementById('recommendationsList');
        if (!recommendationsList) return;
        
        if (!recommendations || recommendations.length === 0) {
            recommendationsList.innerHTML = '<p class="text-muted">Nenhuma recomendação disponível</p>';
            return;
        }
        
        const recommendationsHtml = recommendations.map(rec => {
            const priorityClass = this.getPriorityClass(rec.priority);
            const priorityIcon = this.getPriorityIcon(rec.priority);
            
            return `
                <div class="card mb-3 border-${priorityClass}">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="${priorityIcon} text-${priorityClass}"></i>
                            ${rec.title}
                        </h6>
                        <p class="card-text">${rec.description}</p>
                        <small class="text-success">
                            <i class="fas fa-chart-line"></i> ${rec.estimated_improvement}
                        </small>
                        <button class="btn btn-sm btn-outline-${priorityClass} float-end" 
                                onclick="dashboard.executeRecommendation('${rec.action}')">
                            Aplicar
                        </button>
                    </div>
                </div>
            `;
        }).join('');
        
        recommendationsList.innerHTML = recommendationsHtml;
    }
    
    /**
     * Atualizar recursos do sistema
     */
    updateSystemResources(resources) {
        if (!resources) return;
        
        // CPU Usage
        const cpuUsage = resources.cpu_load?.['1min'] || 0;
        const cpuPercent = Math.min(cpuUsage * 100, 100);
        document.getElementById('cpuProgress').style.width = cpuPercent + '%';
        document.getElementById('cpuProgress').textContent = cpuPercent.toFixed(1) + '%';
        
        // Memory Usage
        if (resources.memory_usage) {
            const memoryPercent = resources.memory_usage.percentage || 0;
            document.getElementById('memoryProgress').style.width = memoryPercent + '%';
            document.getElementById('memoryProgress').textContent = memoryPercent.toFixed(1) + '%';
        }
        
        // Disk Usage
        if (resources.disk_usage) {
            const diskPercent = resources.disk_usage.percentage || 0;
            document.getElementById('diskProgress').style.width = diskPercent + '%';
            document.getElementById('diskProgress').textContent = diskPercent.toFixed(1) + '%';
        }
    }
    
    /**
     * Iniciar atualizações automáticas
     */
    startAutoUpdate() {
        if (this.updateTimer) {
            clearInterval(this.updateTimer);
        }
        
        this.updateTimer = setInterval(() => {
            if (!this.isUpdating && !document.hidden) {
                this.refreshData();
            }
        }, this.options.updateInterval);
        
        console.log(`🔄 Auto-update iniciado (${this.options.updateInterval / 1000}s)`);
    }
    
    /**
     * Atualizar dados
     */
    async refreshData() {
        if (this.isUpdating) return;
        
        this.isUpdating = true;
        
        try {
            const data = await this.fetchPainel de ControleData();
            this.updatePainel de Controle(data);
        } catch (error) {
            console.error('❌ Erro ao atualizar dados:', error);
        } finally {
            this.isUpdating = false;
        }
    }
    
    /**
     * Mostrar modal de otimização
     */
    showOptimizationModal() {
        const modal = new bootstrap.Modal(document.getElementById('optimizationModal'));
        modal.show();
    }
    
    /**
     * Executar otimização
     */
    async runOptimization() {
        const optimizations = [];
        
        if (document.getElementById('optimizeQueries').checked) {
            optimizations.push('optimize_queries');
        }
        if (document.getElementById('optimizeAssets').checked) {
            optimizations.push('optimize_assets');
        }
        if (document.getElementById('clearCache').checked) {
            optimizations.push('clear_cache');
        }
        if (document.getElementById('cleanupLogs').checked) {
            optimizations.push('cleanup_logs');
        }
        
        if (optimizations.length === 0) {
            alert('Selecione ao menos uma otimização');
            return;
        }
        
        const startBtn = document.getElementById('startOptimization');
        startBtn.disabled = true;
        startBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Otimizando...';
        
        try {
            const results = [];
            
            for (const optimization of optimizations) {
                const response = await fetch(`${this.options.apiEndpoint}?action=execute_optimization`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ type: optimization })
                });
                
                const result = await response.json();
                results.push({ type: optimization, result });
            }
            
            this.showOptimizationResults(results);
            
        } catch (error) {
            console.error('Erro na otimização:', error);
            alert('Erro durante a otimização');
        } finally {
            startBtn.disabled = false;
            startBtn.innerHTML = '<i class="fas fa-rocket"></i> Iniciar Otimização';
        }
    }
    
    /**
     * Mostrar resultados da otimização
     */
    showOptimizationResults(results) {
        const resultsContent = document.getElementById('optimizationResultsContent');
        const resultsHtml = results.map(item => {
            const success = !item.result.error;
            const icon = success ? 'fas fa-check-circle text-success' : 'fas fa-times-circle text-danger';
            
            return `
                <div class="mb-2">
                    <i class="${icon}"></i>
                    <strong>${this.getOptimizationName(item.type)}</strong>: 
                    ${success ? 'Concluído' : item.result.error}
                </div>
            `;
        }).join('');
        
        resultsContent.innerHTML = resultsHtml;
        document.getElementById('optimizationResults').style.display = 'block';
        
        // Atualizar dados após otimização
        setTimeout(() => this.refreshData(), 2000);
    }
    
    /**
     * Executar recomendação
     */
    async executeRecommendation(action) {
        if (!confirm('Deseja executar esta otimização?')) return;
        
        try {
            const response = await fetch(`${this.options.apiEndpoint}?action=execute_optimization`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ type: action })
            });
            
            const result = await response.json();
            
            if (result.error) {
                alert('Erro: ' + result.error);
            } else {
                alert('Otimização executada com sucesso!');
                this.refreshData();
            }
            
        } catch (error) {
            console.error('Erro ao executar recomendação:', error);
            alert('Erro ao executar recomendação');
        }
    }
    
    /**
     * Exportararar relatório
     */
    exportReport() {
        if (!this.currentData) {
            alert('Nenhum dado disponível para exportar');
            return;
        }
        
        const report = {
            timestamp: new Date().toISOString(),
            performance_data: this.currentData,
            generated_by: 'Duralux Performance Painel de Controle v4.0'
        };
        
        const blob = new Blob([JSON.stringify(report, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        
        const a = document.createElement('a');
        a.href = url;
        a.download = `duralux-performance-report-${new Date().toISOString().split('T')[0]}.json`;
        a.click();
        
        URL.revokeObjectURL(url);
    }
    
    // Métodos auxiliares
    getSeverityClass(severity) {
        const map = { critical: 'danger', warning: 'warning', info: 'info' };
        return map[severity] || 'secondary';
    }
    
    getSeverityIcon(severity) {
        const map = { 
            critical: 'fas fa-exclamation-triangle', 
            warning: 'fas fa-exclamation-circle', 
            info: 'fas fa-info-circle' 
        };
        return map[severity] || 'fas fa-bell';
    }
    
    getPriorityClass(priority) {
        const map = { high: 'danger', medium: 'warning', low: 'info' };
        return map[priority] || 'secondary';
    }
    
    getPriorityIcon(priority) {
        const map = { 
            high: 'fas fa-exclamation', 
            medium: 'fas fa-arrow-up', 
            low: 'fas fa-arrow-right' 
        };
        return map[priority] || 'fas fa-lightbulb';
    }
    
    getOptimizationName(type) {
        const map = {
            optimize_queries: 'Otimizar Queries',
            optimize_assets: 'Otimizar Assets',
            clear_cache: 'Limpar Cache',
            cleanup_logs: 'Limpar Logs'
        };
        return map[type] || type;
    }
    
    showLoading() {
        document.getElementById('loadingOverlay').style.display = 'flex';
    }
    
    hideLoading() {
        document.getElementById('loadingOverlay').style.display = 'none';
    }
    
    showError(message) {
        console.error(message);
        // Implementar toast de erro
    }
    
    pauseUpdates() {
        if (this.updateTimer) {
            clearInterval(this.updateTimer);
        }
    }
    
    resumeUpdates() {
        this.startAutoUpdate();
    }
    
    destroy() {
        this.pauseUpdates();
        Object.values(this.charts).forEach(chart => {
            if (chart) chart.destroy();
        });
    }
}

// Inicializar dashboard quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Chart !== 'undefined') {
        window.dashboard = new DuraluxPerformancePainel de Controle({
            container: '#performance-dashboard-container',
            updateInterval: 30000,
            enableAlerts: true
        });
    } else {
        console.error('Chart.js não encontrado. Inclua a biblioteca antes deste script.');
    }
});

// CSS para o dashboard
const dashboardCSS = `
<style>
.performance-dashboard {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.dashboard-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 10px;
    margin-bottom: 2rem;
}

.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.9);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.alerts-container .alert {
    border-left: 4px solid;
    margin-bottom: 0.5rem;
}

.recommendations-container .card {
    transition: transform 0.2s;
}

.recommendations-container .card:hover {
    transform: translateY(-2px);
}

.progress {
    height: 20px;
}

.progress-bar {
    transition: width 0.3s ease;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.updating {
    animation: pulse 1s infinite;
}
</style>
`;

// Injetar CSS
document.head.insertAdjacentHTML('beforeend', dashboardCSS);