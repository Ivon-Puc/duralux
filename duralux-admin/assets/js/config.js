/**
 * Configuração Global da API - Duralux
 * Detecta automaticamente o ambiente e define URLs corretas
 */
window.DuraluxConfig = {
    // Detecta se está em produção (Vercel)
    isProduction: window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1',
    
    // Base URL da API
    get apiBaseUrl() {
        if (this.isProduction) {
            // URL do Vercel (será atualizada automaticamente)
            return window.location.origin + '/api';
        } else {
            // URL local de desenvolvimento
            return 'http://localhost/duralux/api';
        }
    },
    
    // URLs específicas das APIs
    get endpoints() {
        return {
            notifications: this.apiBaseUrl + '/api-notifications.php',
            customers: this.apiBaseUrl + '/customers.php',
            leads: this.apiBaseUrl + '/api-leads.php',
            projects: this.apiBaseUrl + '/projects.php',
            workflows: this.apiBaseUrl + '/workflows.php',
            analytics: this.apiBaseUrl + '/analytics.php'
        };
    },
    
    // Headers padrão para todas as requisições
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    },
    
    // Função helper para fazer requisições
    async request(endpoint, options = {}) {
        const url = this.endpoints[endpoint] || endpoint;
        const config = {
            headers: this.headers,
            ...options
        };
        
        try {
            const response = await fetch(url, config);
            if (!response.ok) {
                console.warn(`API Error: ${response.status} - ${response.statusText}`);
                return { error: true, status: response.status, message: response.statusText };
            }
            return await response.json();
        } catch (error) {
            console.error('Network Error:', error);
            return { error: true, message: 'Erro de conexão com a API' };
        }
    }
};

// Log da configuração atual (apenas em desenvolvimento)
if (!window.DuraluxConfig.isProduction) {
    console.log('Duralux API Config:', window.DuraluxConfig);
}