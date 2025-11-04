/**
 * Duralux Painel de Controle - Sistema de Painel de Controle Dinâmico
 * Carrega dados reais das APIs e atualiza o dashboard em tempo real
 */

class DuraluxPainel de Controle {
    constructor() {
        this.apiBase = '../backend/api/router.php';
        this.isAuthenticated = false;
        this.refreshInterval = 30000; // 30 segundos
        this.intervalId = null;
        this.charts = {};
        
        this.init();
    }

    async init() {
        this.showLoading();
        
        if (await this.checkAuthentication()) {
            await this.loadPainel de ControleData();
            this.setupAutoRefresh();
            this.setupEventListeners();
            this.hideLoading();
        } else {
            this.redirectToEntrar();
        }
    }

    showLoading() {
        document.body.style.cursor = 'wait';
        // Adiciona overlay de loading
        if (!document.getElementById('dashboard-loading')) {
            const overlay = document.createElement('div');
            overlay.id = 'dashboard-loading';
            overlay.className = 'dashboard-loading-overlay';
            overlay.innerHTML = `
                <div class="loading-spinner">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <p class="mt-2">Carregando estatísticas...</p>
                </div>
            `;
            document.body.appendChild(overlay);
        }
    }

    hideLoading() {
        document.body.style.cursor = 'default';
        const overlay = document.getElementById('dashboard-loading');
        if (overlay) {
            overlay.remove();
        }
    }

    async checkAuthentication() {
        try {
            const response = await fetch(`${this.apiBase}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    action: 'check_auth'
                })
            });

            const result = await response.json();
            this.isAuthenticated = result.success;
            return result.success;
        } catch (error) {
            console.error('Erro na autenticação:', error);
            return false;
        }
    }

    async loadPainel de ControleData() {
        try {
            // Carrega todos os dados do dashboard em paralelo
            const [statsData, revenueData, leadsData, projectsData] = await Promise.all([
                this.loadStats(),
                this.loadReceitaData(),
                this.loadLeadsData(),
                this.loadProjectsData()
            ]);

            // Atualiza as estatísticas principais
            this.updateMainStats(statsData);
            
            // Atualiza gráficos
            this.updateCharts({
                revenue: revenueData,
                leads: leadsData,
                projects: projectsData
            });

            // Atualiza últimas atividades
            await this.loadRecentActivities();

        } catch (error) {
            console.error('Erro ao carregar dados do dashboard:', error);
            this.showError('Erro ao carregar dados do dashboard');
        }
    }

    async loadStats() {
        const response = await fetch(`${this.apiBase}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                action: 'get_dashboard_stats'
            })
        });

        if (!response.ok) throw new Error('Erro ao carregar estatísticas');
        return await response.json();
    }

    async loadReceitaData() {
        const response = await fetch(`${this.apiBase}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                action: 'get_revenue_data',
                period: 'month'
            })
        });

        if (!response.ok) throw new Error('Erro ao carregar dados de receita');
        return await response.json();
    }

    async loadLeadsData() {
        const response = await fetch(`${this.apiBase}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                action: 'get_leads_analytics'
            })
        });

        if (!response.ok) throw new Error('Erro ao carregar dados de leads');
        return await response.json();
    }

    async loadProjectsData() {
        const response = await fetch(`${this.apiBase}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                action: 'get_projects_analytics'
            })
        });

        if (!response.ok) throw new Error('Erro ao carregar dados de projetos');
        return await response.json();
    }

    updateMainStats(data) {
        if (!data.success) return;

        const stats = data.data;
        
        // Atualiza card de faturas aguardando pagamento
        const invoicesCard = document.querySelector('.col-xxl-3:first-child .card-body');
        if (invoicesCard) {
            const counters = invoicesCard.querySelectorAll('.counter');
            if (counters.length >= 2) {
                counters[0].textContent = stats.pending_invoices || 0;
                counters[1].textContent = stats.total_invoices || 0;
            }
            
            const amount = invoicesCard.querySelector('.fs-12.text-dark');
            if (amount) {
                amount.textContent = `R$ ${this.formatCurrency(stats.pending_amount || 0)}`;
            }
            
            const percentage = invoicesCard.querySelector('.fs-11.text-muted');
            if (percentage) {
                const percent = stats.total_invoices > 0 ? 
                    Math.round((stats.pending_invoices / stats.total_invoices) * 100) : 0;
                percentage.textContent = `(${percent}%)`;
                
                // Atualiza barra de progresso
                const progressBar = invoicesCard.querySelector('.progress-bar');
                if (progressBar) {
                    progressBar.style.width = `${percent}%`;
                }
            }
        }

        // Atualiza card de leads convertidos
        const leadsCard = document.querySelector('.col-xxl-3:nth-child(2) .card-body');
        if (leadsCard) {
            const counters = leadsCard.querySelectorAll('.counter');
            if (counters.length >= 2) {
                counters[0].textContent = stats.converted_leads || 0;
                counters[1].textContent = stats.total_leads || 0;
            }
            
            const completed = leadsCard.querySelector('.fs-12.text-dark');
            if (completed) {
                completed.textContent = `${stats.converted_leads || 0} Concluídos`;
            }
            
            const percentage = leadsCard.querySelector('.fs-11.text-muted');
            if (percentage) {
                const percent = stats.total_leads > 0 ? 
                    Math.round((stats.converted_leads / stats.total_leads) * 100) : 0;
                percentage.textContent = `(${percent}%)`;
                
                // Atualiza barra de progresso
                const progressBar = leadsCard.querySelector('.progress-bar');
                if (progressBar) {
                    progressBar.style.width = `${percent}%`;
                }
            }
        }

        // Atualiza card de projetos em andamento
        const projectsCard = document.querySelector('.col-xxl-3:nth-child(3) .card-body');
        if (projectsCard) {
            const counters = projectsCard.querySelectorAll('.counter');
            if (counters.length >= 2) {
                counters[0].textContent = stats.active_projects || 0;
                counters[1].textContent = stats.total_projects || 0;
            }
            
            const completed = projectsCard.querySelector('.fs-12.text-dark');
            if (completed) {
                completed.textContent = `${stats.active_projects || 0} Concluídos`;
            }
            
            const percentage = projectsCard.querySelector('.fs-11.text-muted');
            if (percentage) {
                const percent = stats.total_projects > 0 ? 
                    Math.round((stats.active_projects / stats.total_projects) * 100) : 0;
                percentage.textContent = `(${percent}%)`;
                
                // Atualiza barra de progresso
                const progressBar = projectsCard.querySelector('.progress-bar');
                if (progressBar) {
                    progressBar.style.width = `${percent}%`;
                }
            }
        }

        // Atualiza card de taxa de conversão
        const conversionCard = document.querySelector('.col-xxl-3:nth-child(4) .card-body');
        if (conversionCard) {
            const rateElement = conversionCard.querySelector('.counter');
            if (rateElement) {
                rateElement.textContent = (stats.conversion_rate || 0).toFixed(2);
            }
            
            const amount = conversionCard.querySelector('.fs-12.text-dark');
            if (amount) {
                amount.textContent = `R$ ${this.formatCurrency(stats.conversion_value || 0)}`;
            }
            
            const percentage = conversionCard.querySelector('.fs-11.text-muted');
            if (percentage) {
                percentage.textContent = `(${Math.round(stats.conversion_rate || 0)}%)`;
                
                // Atualiza barra de progresso
                const progressBar = conversionCard.querySelector('.progress-bar');
                if (progressBar) {
                    progressBar.style.width = `${Math.round(stats.conversion_rate || 0)}%`;
                }
            }
        }
    }

    updateCharts(data) {
        this.updatePaymentChart(data.revenue);
        this.updateLeadsChart(data.leads);
        this.updateProjectsChart(data.projects);
    }

    updatePaymentChart(revenueData) {
        if (!revenueData.success) return;

        // Atualiza estatísticas do rodapé do gráfico
        const chartFooter = document.querySelector('#payment-records-chart').closest('.card').querySelector('.card-footer .row');
        if (chartFooter && revenueData.data) {
            const stats = revenueData.data;
            const cards = chartFooter.querySelectorAll('.col-lg-3');
            
            if (cards.length >= 4) {
                // Aguardando
                cards[0].querySelector('h6').textContent = `R$ ${this.formatCurrency(stats.awaiting || 0)}`;
                
                // Completado
                cards[1].querySelector('h6').textContent = `R$ ${this.formatCurrency(stats.completed || 0)}`;
                
                // Rejeitado
                cards[2].querySelector('h6').textContent = `R$ ${this.formatCurrency(stats.rejected || 0)}`;
                
                // Receita
                cards[3].querySelector('h6').textContent = `R$ ${this.formatCurrency(stats.revenue || 0)}`;
            }
        }
    }

    updateLeadsChart(leadsData) {
        if (!leadsData.success) return;
        // Implementar atualização do gráfico de leads quando os gráficos estiverem prontos
        console.log('Dados de leads:', leadsData.data);
    }

    updateProjectsChart(projectsData) {
        if (!projectsData.success) return;
        // Implementar atualização do gráfico de projetos quando os gráficos estiverem prontos
        console.log('Dados de projetos:', projectsData.data);
    }

    async loadRecentActivities() {
        try {
            const response = await fetch(`${this.apiBase}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    action: 'get_recent_activities',
                    limit: 10
                })
            });

            const result = await response.json();
            if (result.success) {
                this.updateActivitiesFeed(result.data);
            }
        } catch (error) {
            console.error('Erro ao carregar atividades recentes:', error);
        }
    }

    updateActivitiesFeed(activities) {
        // Procura por uma seção de atividades recentes no dashboard
        const activitiesSection = document.querySelector('.recent-activities, .activity-feed');
        if (activitiesSection && activities.length > 0) {
            const activitiesHtml = activities.map(activity => `
                <div class="activity-item d-flex align-items-center gap-3 p-2">
                    <div class="activity-icon">
                        <i class="feather-${this.getActivityIcon(activity.type)} text-primary"></i>
                    </div>
                    <div class="activity-content flex-grow-1">
                        <div class="activity-title fw-semibold">${activity.title}</div>
                        <div class="activity-time fs-12 text-muted">${this.timeAgo(activity.created_at)}</div>
                    </div>
                </div>
            `).join('');
            
            activitiesSection.innerHTML = activitiesHtml;
        }
    }

    getActivityIcon(type) {
        const icons = {
            'customer': 'user',
            'product': 'package',
            'order': 'shopping-cart',
            'payment': 'dollar-sign',
            'lead': 'target',
            'project': 'briefcase'
        };
        return icons[type] || 'activity';
    }

    timeAgo(timestamp) {
        const now = new Date();
        const time = new Date(timestamp);
        const diffInSeconds = Math.floor((now - time) / 1000);
        
        if (diffInSeconds < 60) return 'Agora há pouco';
        if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}min atrás`;
        if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h atrás`;
        return `${Math.floor(diffInSeconds / 86400)}d atrás`;
    }

    formatCurrency(value) {
        return new Intl.NumberFormat('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(value);
    }

    setupAutoRefresh() {
        this.intervalId = setInterval(() => {
            this.loadPainel de ControleData();
        }, this.refreshInterval);
    }

    setupEventListeners() {
        // Listener para refresh manual
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-bs-toggle="refresh"]')) {
                e.preventDefault();
                this.loadPainel de ControleData();
            }
        });

        // Listener para mudança de período
        document.addEventListener('change', (e) => {
            if (e.target.matches('.period-selector')) {
                this.loadPainel de ControleData();
            }
        });

        // Cleanup ao sair da página
        window.addEventListener('beforeunload', () => {
            if (this.intervalId) {
                clearInterval(this.intervalId);
            }
        });
    }

    showError(message) {
        // Cria toast de erro
        const toastHtml = `
            <div class="toast align-items-center text-white bg-danger border-0 position-fixed top-0 end-0 m-3" style="z-index: 9999;" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="feather-alert-circle me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', toastHtml);
        
        // Inicializa e mostra o toast
        const toastElement = document.querySelector('.toast:last-child');
        const toast = new bootstrap.Toast(toastElement, {
            autohide: true,
            delay: 5000
        });
        toast.show();
        
        // Remove o elemento após ser escondido
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
    }

    redirectToEntrar() {
        window.location.href = 'auth-login-minimal.html';
    }

    destroy() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
        }
        this.hideLoading();
    }
}

// CSS para loading overlay
const loadingStyles = `
<style>
.dashboard-loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.9);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9998;
    backdrop-filter: blur(2px);
}

.loading-spinner {
    text-align: center;
    color: #6c757d;
}

.activity-item {
    border-bottom: 1px solid #f8f9fa;
    transition: background-color 0.2s;
}

.activity-item:hover {
    background-color: #f8f9fa;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    border-radius: 50%;
}
</style>
`;

// Injeta CSS
document.head.insertAdjacentHTML('beforeend', loadingStyles);

// Inicializa o dashboard quando a página carregar
document.addEventListener('DOMContentLoaded', () => {
    window.duraluxPainel de Controle = new DuraluxPainel de Controle();
});

// Exportarara para uso global
window.DuraluxPainel de Controle = DuraluxPainel de Controle;