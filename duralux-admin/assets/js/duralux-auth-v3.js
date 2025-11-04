/* ===================================================================
   DURALUX CRM - JWT Authentication System v3.0 Frontend
   Sistema de autenticação JWT com 2FA, roles e segurança avançada
   =================================================================== */

class DuraluxAuthSystem {
    constructor(options = {}) {
        this.config = {
            apiBaseUrl: '/duralux/backend/api',
            tokenKey: 'duralux_access_token',
            refreshKey: 'duralux_refresh_token',
            userKey: 'duralux_user_data',
            autoRefresh: true,
            refreshThreshold: 300, // 5 minutos antes de expirar
            maxRetries: 3,
            ...options
        };
        
        this.currentUser = null;
        this.permissions = [];
        this.refreshTimer = null;
        this.eventListeners = {};
        
        this.init();
    }
    
    /**
     * Inicializar sistema de autenticação
     */
    init() {
        console.log('🔐 Inicializando Duralux Auth System v3.0...');
        
        // Verificar token existente
        this.loadStoredAuth();
        
        // Configurar auto-refresh
        if (this.config.autoRefresh) {
            this.setupAutoRefresh();
        }
        
        // Interceptar requisições AJAX
        this.setupAjaxInterceptors();
        
        // Configurar event listeners
        this.setupEventListeners();
        
        console.log('✅ Auth System inicializado com sucesso!');
    }
    
    /**
     * Entrar do usuário
     */
    async login(credentials) {
        try {
            this.emit('loginStart');
            
            const response = await this.makeRequest('/v1/auth/login', {
                method: 'POST',
                body: JSON.stringify(credentials)
            });
            
            if (response.success) {
                await this.handleAuthSuccess(response.data);
                this.emit('loginSuccess', response.data);
                return response.data;
            } else {
                throw new Error(response.message || 'Entrar failed');
            }
            
        } catch (error) {
            this.emit('loginError', error);
            throw error;
        }
    }
    
    /**
     * Verificar 2FA
     */
    async verify2FA(userId, totpCode, remember = false) {
        try {
            const response = await this.makeRequest('/v1/auth/verify-2fa', {
                method: 'POST',
                body: JSON.stringify({
                    user_id: userId,
                    totp_code: totpCode,
                    remember: remember
                })
            });
            
            if (response.success) {
                await this.handleAuthSuccess(response.data);
                this.emit('2faSuccess', response.data);
                return response.data;
            } else {
                throw new Error(response.message || '2FA verification failed');
            }
            
        } catch (error) {
            this.emit('2faError', error);
            throw error;
        }
    }
    
    /**
     * Refresh do token
     */
    async refreshToken() {
        try {
            const refreshToken = this.getRefreshToken();
            if (!refreshToken) {
                throw new Error('No refresh token available');
            }
            
            const response = await this.makeRequest('/v1/auth/refresh', {
                method: 'POST',
                body: JSON.stringify({
                    refresh_token: refreshToken
                })
            });
            
            if (response.success) {
                this.setToken(response.data.access_token);
                this.emit('tokenRefreshed');
                return response.data.access_token;
            } else {
                throw new Error('Token refresh failed');
            }
            
        } catch (error) {
            this.handleAuthError(error);
            throw error;
        }
    }
    
    /**
     * Logout do usuário
     */
    async logout() {
        try {
            this.emit('logoutStart');
            
            // Tentar logout no servidor
            await this.makeRequest('/v1/auth/logout', {
                method: 'POST'
            }).catch(() => {}); // Ignorar erro do servidor
            
            // Limpar dados locais
            this.clearAuth();
            this.emit('logoutSuccess');
            
        } catch (error) {
            this.emit('logoutError', error);
        }
    }
    
    /**
     * Registrar novo usuário
     */
    async register(userData) {
        try {
            this.emit('registerStart');
            
            const response = await this.makeRequest('/v1/auth/register', {
                method: 'POST',
                body: JSON.stringify(userData)
            });
            
            if (response.success) {
                this.emit('registerSuccess', response.data);
                return response.data;
            } else {
                throw new Error(response.message || 'Registration failed');
            }
            
        } catch (error) {
            this.emit('registerError', error);
            throw error;
        }
    }
    
    /**
     * Obter dados do usuário atual
     */
    async getCurrentUser() {
        try {
            const response = await this.makeRequest('/v1/auth/me');
            
            if (response.success) {
                this.currentUser = response.data.user;
                this.permissions = response.data.permissions || [];
                this.storeUserData(response.data);
                return response.data;
            } else {
                throw new Error('Failed to get user data');
            }
            
        } catch (error) {
            this.handleAuthError(error);
            throw error;
        }
    }
    
    /**
     * Alterar senha
     */
    async changePassword(currentPassword, newPassword) {
        try {
            const response = await this.makeRequest('/v1/auth/change-password', {
                method: 'POST',
                body: JSON.stringify({
                    current_password: currentPassword,
                    new_password: newPassword
                })
            });
            
            if (response.success) {
                this.emit('passwordChanged');
                return true;
            } else {
                throw new Error(response.message || 'Password change failed');
            }
            
        } catch (error) {
            this.emit('passwordChangeError', error);
            throw error;
        }
    }
    
    /**
     * Verificar se usuário tem permissão específica
     */
    hasPermission(permission) {
        if (!this.permissions || this.permissions.length === 0) {
            return false;
        }
        
        // Verificar permissão específica ou wildcard
        return this.permissions.includes(permission) || 
               this.permissions.includes('*') ||
               this.permissions.some(p => {
                   if (p.includes('*')) {
                       const pattern = p.replace('*', '.*');
                       return new RegExp('^' + pattern + '$').test(permission);
                   }
                   return false;
               });
    }
    
    /**
     * Verificar se usuário tem uma das roles especificadas
     */
    hasRole(roles) {
        if (!this.currentUser) return false;
        
        const userRole = this.currentUser.role;
        const allowedRoles = Array.isArray(roles) ? roles : [roles];
        
        return allowedRoles.includes(userRole);
    }
    
    /**
     * Verificar se usuário está autenticado
     */
    isAuthenticated() {
        return !!this.getToken() && !!this.currentUser;
    }
    
    /**
     * Obter token de acesso
     */
    getToken() {
        return localStorage.getItem(this.config.tokenKey);
    }
    
    /**
     * Definir token de acesso
     */
    setToken(token) {
        if (token) {
            localStorage.setItem(this.config.tokenKey, token);
        } else {
            localStorage.removeItem(this.config.tokenKey);
        }
    }
    
    /**
     * Obter refresh token
     */
    getRefreshToken() {
        return localStorage.getItem(this.config.refreshKey);
    }
    
    /**
     * Definir refresh token
     */
    setRefreshToken(token) {
        if (token) {
            localStorage.setItem(this.config.refreshKey, token);
        } else {
            localStorage.removeItem(this.config.refreshKey);
        }
    }
    
    // ==========================================
    // MÉTODOS INTERNOS
    // ==========================================
    
    /**
     * Processar sucesso de autenticação
     */
    async handleAuthSuccess(data) {
        // Armazenar tokens
        if (data.tokens) {
            this.setToken(data.tokens.access_token);
            this.setRefreshToken(data.tokens.refresh_token);
        }
        
        // Armazenar dados do usuário
        if (data.user) {
            this.currentUser = data.user;
            this.permissions = data.permissions || [];
            this.storeUserData(data);
        }
        
        // Configurar auto-refresh
        if (this.config.autoRefresh) {
            this.setupTokenRefresh(data.expires_at);
        }
    }
    
    /**
     * Processar erro de autenticação
     */
    handleAuthError(error) {
        console.error('Auth Error:', error);
        
        // Se token expirou, tentar refresh
        if (error.status === 401 && this.getRefreshToken()) {
            this.refreshToken().catch(() => {
                this.clearAuth();
                this.emit('authExpired');
            });
        } else {
            this.clearAuth();
            this.emit('authError', error);
        }
    }
    
    /**
     * Limpar dados de autenticação
     */
    clearAuth() {
        this.setToken(null);
        this.setRefreshToken(null);
        this.currentUser = null;
        this.permissions = [];
        localStorage.removeItem(this.config.userKey);
        
        if (this.refreshTimer) {
            clearTimeout(this.refreshTimer);
            this.refreshTimer = null;
        }
    }
    
    /**
     * Carregar autenticação armazenada
     */
    loadStoredAuth() {
        const token = this.getToken();
        const userData = localStorage.getItem(this.config.userKey);
        
        if (token && userData) {
            try {
                const data = JSON.parse(userData);
                this.currentUser = data.user;
                this.permissions = data.permissions || [];
                
                // Verificar se token não expirou
                const payload = this.parseJWT(token);
                if (payload && payload.exp > Date.now() / 1000) {
                    this.setupTokenRefresh(payload.exp * 1000);
                } else {
                    // Token expirado, tentar refresh
                    this.refreshToken().catch(() => this.clearAuth());
                }
            } catch (error) {
                console.error('Erro ao carregar auth:', error);
                this.clearAuth();
            }
        }
    }
    
    /**
     * Armazenar dados do usuário
     */
    storeUserData(data) {
        localStorage.setItem(this.config.userKey, JSON.stringify({
            user: data.user,
            permissions: data.permissions,
            timestamp: Date.now()
        }));
    }
    
    /**
     * Configurar refresh automático do token
     */
    setupTokenRefresh(expiresAt) {
        if (this.refreshTimer) {
            clearTimeout(this.refreshTimer);
        }
        
        const expirationTime = typeof expiresAt === 'string' ? 
            new Date(expiresAt).getTime() : 
            expiresAt;
            
        const refreshTime = expirationTime - Date.now() - (this.config.refreshThreshold * 1000);
        
        if (refreshTime > 0) {
            this.refreshTimer = setTimeout(() => {
                this.refreshToken().catch(error => {
                    console.error('Auto refresh failed:', error);
                    this.clearAuth();
                    this.emit('authExpired');
                });
            }, refreshTime);
        }
    }
    
    /**
     * Configurar auto-refresh
     */
    setupAutoRefresh() {
        // Verificar token a cada 5 minutos
        setInterval(() => {
            if (this.isAuthenticated()) {
                const token = this.getToken();
                const payload = this.parseJWT(token);
                
                if (payload) {
                    const timeUntilExpiry = payload.exp - (Date.now() / 1000);
                    
                    // Se faltam menos de 5 minutos para expirar
                    if (timeUntilExpiry < this.config.refreshThreshold) {
                        this.refreshToken().catch(error => {
                            console.error('Auto refresh failed:', error);
                        });
                    }
                }
            }
        }, 300000); // 5 minutos
    }
    
    /**
     * Configurar interceptadores AJAX
     */
    setupAjaxInterceptors() {
        // Interceptar fetch
        const originalFetch = window.fetch;
        window.fetch = async (...args) => {
            const [url, options = {}] = args;
            
            // Add token de autorização se disponível
            if (this.getToken() && !options.headers?.Authorization) {
                options.headers = {
                    ...options.headers,
                    'Authorization': `Bearer ${this.getToken()}`
                };
            }
            
            const response = await originalFetch(url, options);
            
            // Verificar se token expirou
            if (response.status === 401 && this.getRefreshToken()) {
                try {
                    await this.refreshToken();
                    
                    // Repetir requisição com novo token
                    options.headers.Authorization = `Bearer ${this.getToken()}`;
                    return originalFetch(url, options);
                } catch (error) {
                    this.handleAuthError(error);
                }
            }
            
            return response;
        };
        
        // Interceptar jQuery AJAX se disponível
        if (window.jQuery) {
            $(document).ajaxSend((event, xhr, settings) => {
                const token = this.getToken();
                if (token && settings.url?.includes(this.config.apiBaseUrl)) {
                    xhr.setRequestHeader('Authorization', `Bearer ${token}`);
                }
            });
            
            $(document).ajaxError((event, xhr, settings) => {
                if (xhr.status === 401 && this.getRefreshToken()) {
                    this.refreshToken().catch(() => {
                        this.clearAuth();
                        this.emit('authExpired');
                    });
                }
            });
        }
    }
    
    /**
     * Fazer requisição para API
     */
    async makeRequest(endpoint, options = {}) {
        const url = `${this.config.apiBaseUrl}${endpoint}`;
        const token = this.getToken();
        
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                ...(token && { 'Authorization': `Bearer ${token}` })
            }
        };
        
        const response = await fetch(url, { ...defaultOptions, ...options });
        
        if (!response.ok) {
            const error = await response.json().catch(() => ({ message: 'Request failed' }));
            error.status = response.status;
            throw error;
        }
        
        return response.json();
    }
    
    /**
     * Parse JWT token
     */
    parseJWT(token) {
        try {
            const base64Url = token.split('.')[1];
            const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
            const jsonPayload = decodeURIComponent(atob(base64).split('').map(c => {
                return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
            }).join(''));
            
            return JSON.parse(jsonPayload);
        } catch (error) {
            console.error('Erro ao fazer parse do JWT:', error);
            return null;
        }
    }
    
    /**
     * Configurar event listeners
     */
    setupEventListeners() {
        // Detectar mudança de aba/janela
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && this.isAuthenticated()) {
                // Verificar se token ainda é válido quando usuário volta à aba
                this.getCurrentUser().catch(error => {
                    console.error('Erro ao verificar usuário:', error);
                });
            }
        });
        
        // Detectar storage changes (logout em outra aba)
        window.addEventListener('storage', (e) => {
            if (e.key === this.config.tokenKey && !e.newValue) {
                // Token foi removido em outra aba
                this.clearAuth();
                this.emit('logoutOtherTab');
            }
        });
    }
    
    /**
     * Sistema de eventos
     */
    on(event, callback) {
        if (!this.eventListeners[event]) {
            this.eventListeners[event] = [];
        }
        this.eventListeners[event].push(callback);
    }
    
    off(event, callback) {
        if (this.eventListeners[event]) {
            this.eventListeners[event] = this.eventListeners[event].filter(cb => cb !== callback);
        }
    }
    
    emit(event, data) {
        if (this.eventListeners[event]) {
            this.eventListeners[event].forEach(callback => {
                try {
                    callback(data);
                } catch (error) {
                    console.error(`Erro no event listener para ${event}:`, error);
                }
            });
        }
    }
}

// Instância global
window.DuraluxAuth = new DuraluxAuthSystem();

// Event listeners globais
DuraluxAuth.on('authExpired', () => {
    console.warn('🔒 Sessão expirada - redirecionando para login...');
    // Redirecionar para login ou mostrar modal
    if (window.location.pathname !== '/login.html') {
        window.location.href = '/duralux-admin/auth-login-cover.html';
    }
});

DuraluxAuth.on('loginSuccess', (data) => {
    console.log('✅ Entrar realizado com sucesso!', data.user);
    
    // Redirecionar para dashboard se não estiver lá
    if (window.location.pathname.includes('auth-') || window.location.pathname.includes('login')) {
        window.location.href = '/duralux-admin/index.html';
    }
});

DuraluxAuth.on('logoutSuccess', () => {
    console.log('👋 Logout realizado com sucesso!');
    
    // Redirecionar para login
    if (!window.location.pathname.includes('auth-')) {
        window.location.href = '/duralux-admin/auth-login-cover.html';
    }
});

// Funções auxiliares globais
window.authHelpers = {
    // Verificar se usuário pode acessar página
    canAccessPage: (requiredPermission) => {
        return DuraluxAuth.isAuthenticated() && 
               (!requiredPermission || DuraluxAuth.hasPermission(requiredPermission));
    },
    
    // Redirecionar se não autenticado
    requireAuth: (requiredPermission) => {
        if (!DuraluxAuth.isAuthenticated()) {
            window.location.href = '/duralux-admin/auth-login-cover.html';
            return false;
        }
        
        if (requiredPermission && !DuraluxAuth.hasPermission(requiredPermission)) {
            alert('Você não tem permissão para acessar esta página.');
            window.location.href = '/duralux-admin/index.html';
            return false;
        }
        
        return true;
    },
    
    // Obter dados do usuário atual
    getCurrentUser: () => DuraluxAuth.currentUser,
    
    // Verificar permissão
    hasPermission: (permission) => DuraluxAuth.hasPermission(permission),
    
    // Verificar role
    hasRole: (role) => DuraluxAuth.hasRole(role)
};

console.log('🔐 Duralux Auth System v3.0 carregado com sucesso!');