/* ===================================================================
   DURALUX CRM - API REST Documentation Generator v2.0
   Gerador automático de documentação para integração com Swagger UI
   =================================================================== */

document.addEventListener('DOMContentLoaded', function() {
    // Verificar se estamos na página de documentação da API
    if (window.location.pathname.includes('/api/v1/docs')) {
        initializeAPIDocumentation();
    }
});

/**
 * Inicializar sistema de documentação da API
 */
function initializeAPIDocumentation() {
    console.log('🔄 Inicializando Duralux API Documentation System v2.0...');
    
    // Adicionar funcionalidades extras ao Swagger UI
    setTimeout(() => {
        enhanceSwaggerUI();
        addCustomAPIFeatures();
        setupAPITesting();
    }, 2000);
}

/**
 * Melhorar interface do Swagger UI
 */
function enhanceSwaggerUI() {
    // Adicionar informações de status da API
    addAPIStatusIndicator();
    
    // Adicionar shortcuts úteis
    addKeyboardShortcuts();
    
    // Personalizar aparência
    customizeSwaggerAppearance();
}

/**
 * Adicionar indicador de status da API
 */
function addAPIStatusIndicator() {
    const headerDiv = document.querySelector('.duralux-header');
    if (!headerDiv) return;
    
    const statusDiv = document.createElement('div');
    statusDiv.className = 'api-status-indicator';
    statusDiv.innerHTML = `
        <div style="display: flex; justify-content: center; gap: 20px; margin-top: 15px;">
            <div class="status-item">
                <span class="status-dot" style="background: #27ae60;"></span>
                <span>API Status: Online</span>
            </div>
            <div class="status-item">
                <span class="status-dot" style="background: #3498db;"></span>
                <span>Endpoints: 25+</span>
            </div>
            <div class="status-item">
                <span class="status-dot" style="background: #f39c12;"></span>
                <span>Rate Limit: 60/min</span>
            </div>
        </div>
    `;
    
    // Adicionar estilos
    const style = document.createElement('style');
    style.textContent = `
        .api-status-indicator .status-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            opacity: 0.9;
        }
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }
        .api-quick-actions {
            margin: 20px;
            text-align: center;
        }
        .quick-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            margin: 5px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .quick-btn:hover {
            background: #5a67d8;
        }
    `;
    document.head.appendChild(style);
    
    headerDiv.appendChild(statusDiv);
}

/**
 * Adicionar ações rápidas
 */
function addQuickActions() {
    const actionsDiv = document.createElement('div');
    actionsDiv.className = 'api-quick-actions';
    actionsDiv.innerHTML = `
        <a href="/duralux/backend/api/v1/health" class="quick-btn" target="_blank">🏥 Health Check</a>
        <button class="quick-btn" onclick="testAPIConnection()">🔌 Test Connection</button>
        <a href="/duralux/backend/api/v1/docs.json" class="quick-btn" target="_blank">📄 OpenAPI JSON</a>
    `;
    
    const headerDiv = document.querySelector('.duralux-header');
    if (headerDiv) {
        headerDiv.appendChild(actionsDiv);
    }
}

/**
 * Configurar shortcuts de teclado
 */
function addKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + K: Foco na busca
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.querySelector('.filter-container input');
            if (searchInput) {
                searchInput.focus();
            }
        }
        
        // Ctrl/Cmd + /: Mostrar shortcuts
        if ((e.ctrlKey || e.metaKey) && e.key === '/') {
            e.preventDefault();
            showKeyboardShortcuts();
        }
        
        // Escape: Fechar modais ou limpar busca
        if (e.key === 'Escape') {
            const searchInput = document.querySelector('.filter-container input');
            if (searchInput && searchInput.value) {
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input'));
            }
        }
    });
}

/**
 * Mostrar shortcuts disponíveis
 */
function showKeyboardShortcuts() {
    const shortcuts = [
        { key: 'Ctrl/Cmd + K', description: 'Focar na busca de endpoints' },
        { key: 'Ctrl/Cmd + /', description: 'Mostrar esta ajuda' },
        { key: 'Escape', description: 'Limpar busca ou fechar modais' },
        { key: 'Enter', description: 'Executar requisição selecionada' }
    ];
    
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.7);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 10000;
    `;
    
    const content = document.createElement('div');
    content.style.cssText = `
        background: white;
        padding: 30px;
        border-radius: 10px;
        max-width: 500px;
        width: 90%;
    `;
    
    content.innerHTML = `
        <h2>⌨️ Keyboard Shortcuts</h2>
        <div style="margin: 20px 0;">
            ${shortcuts.map(s => `
                <div style="display: flex; justify-content: space-between; margin: 10px 0; padding: 5px 0;">
                    <kbd style="background: #f5f5f5; padding: 3px 8px; border-radius: 3px; font-family: monospace;">${s.key}</kbd>
                    <span>${s.description}</span>
                </div>
            `).join('')}
        </div>
        <button onclick="this.closest('.modal').remove()" style="background: #667eea; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">Fechar</button>
    `;
    
    modal.appendChild(content);
    modal.className = 'modal';
    document.body.appendChild(modal);
    
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.remove();
        }
    });
}

/**
 * Personalizar aparência do Swagger
 */
function customizeSwaggerAppearance() {
    const style = document.createElement('style');
    style.textContent = `
        /* Melhorias visuais para o Swagger UI */
        .swagger-ui .info .title {
            color: #2c3e50 !important;
        }
        
        .swagger-ui .scheme-container {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }
        
        .swagger-ui .opblock.opblock-get .opblock-summary {
            background: rgba(61, 142, 185, 0.1);
        }
        
        .swagger-ui .opblock.opblock-post .opblock-summary {
            background: rgba(73, 204, 144, 0.1);
        }
        
        .swagger-ui .opblock.opblock-put .opblock-summary {
            background: rgba(252, 161, 48, 0.1);
        }
        
        .swagger-ui .opblock.opblock-delete .opblock-summary {
            background: rgba(249, 62, 62, 0.1);
        }
        
        /* Animações suaves */
        .swagger-ui .opblock {
            transition: all 0.3s ease;
        }
        
        .swagger-ui .opblock:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        /* Melhor destaque para códigos de status */
        .swagger-ui .responses .response .response-col_status {
            font-weight: bold;
        }
        
        .swagger-ui .response-col_status .response-undocumented {
            background: #27ae60;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
        }
    `;
    document.head.appendChild(style);
}

/**
 * Configurar sistema de testes da API
 */
function setupAPITesting() {
    // Adicionar botões de teste rápido
    addQuickTestButtons();
    
    // Interceptar respostas para melhor feedback
    interceptAPIResponses();
}

/**
 * Adicionar botões de teste rápido
 */
function addQuickTestButtons() {
    // Aguardar o Swagger carregar completamente
    setTimeout(() => {
        const operations = document.querySelectorAll('.swagger-ui .opblock');
        
        operations.forEach(operation => {
            const summary = operation.querySelector('.opblock-summary');
            if (summary && !summary.querySelector('.quick-test-btn')) {
                const testBtn = document.createElement('button');
                testBtn.className = 'quick-test-btn';
                testBtn.innerHTML = '⚡ Teste Rápido';
                testBtn.style.cssText = `
                    background: #17a2b8;
                    color: white;
                    border: none;
                    padding: 4px 8px;
                    margin-left: 10px;
                    border-radius: 3px;
                    font-size: 12px;
                    cursor: pointer;
                `;
                
                testBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    performQuickTest(operation);
                });
                
                summary.appendChild(testBtn);
            }
        });
    }, 3000);
}

/**
 * Executar teste rápido de endpoint
 */
function performQuickTest(operationElement) {
    const method = operationElement.querySelector('.opblock-summary-method')?.textContent?.trim();
    const path = operationElement.querySelector('.opblock-summary-path span')?.textContent?.trim();
    
    if (!method || !path) return;
    
    console.log(`🧪 Executando teste rápido: ${method} ${path}`);
    
    // Expandir a operação se não estiver expandida
    const summary = operationElement.querySelector('.opblock-summary');
    if (summary && !operationElement.classList.contains('is-open')) {
        summary.click();
    }
    
    // Aguardar expansão e tentar executar
    setTimeout(() => {
        const tryBtn = operationElement.querySelector('.btn.try-out__btn');
        if (tryBtn && tryBtn.textContent.includes('Try it out')) {
            tryBtn.click();
            
            setTimeout(() => {
                const executeBtn = operationElement.querySelector('.btn.execute');
                if (executeBtn) {
                    executeBtn.click();
                }
            }, 500);
        }
    }, 500);
}

/**
 * Interceptar respostas da API
 */
function interceptAPIResponses() {
    // Monitorar requisições AJAX
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        const url = args[0];
        
        return originalFetch.apply(this, args).then(response => {
            if (typeof url === 'string' && url.includes('/duralux/backend/api/')) {
                console.log(`📡 API Response: ${response.status} - ${url}`);
                
                // Adicionar notificação visual para respostas
                showAPIResponseNotification(response.status, url);
            }
            
            return response;
        });
    };
}

/**
 * Mostrar notificação de resposta da API
 */
function showAPIResponseNotification(status, url) {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${status >= 200 && status < 300 ? '#27ae60' : '#e74c3c'};
        color: white;
        padding: 12px 20px;
        border-radius: 6px;
        z-index: 9999;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        font-size: 14px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        transform: translateX(100%);
        transition: transform 0.3s ease;
    `;
    
    const endpoint = url.split('/duralux/backend/api')[1] || url;
    notification.innerHTML = `
        <strong>${status >= 200 && status < 300 ? '✅' : '❌'} ${status}</strong><br>
        <small>${endpoint}</small>
    `;
    
    document.body.appendChild(notification);
    
    // Animar entrada
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Auto-remover após 3 segundos
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

/**
 * Testar conexão com a API
 */
function testAPIConnection() {
    console.log('🔌 Testando conexão com a API...');
    
    fetch('/duralux/backend/api/v1/health')
        .then(response => response.json())
        .then(data => {
            console.log('✅ API conectada:', data);
            alert('✅ API está funcionando!\n\nStatus: ' + (data.status || 'OK') + '\nVersão: ' + (data.version || '2.0'));
        })
        .catch(error => {
            console.error('❌ Erro na conexão:', error);
            alert('❌ Erro ao conectar com a API.\n\nVerifique o console para mais detalhes.');
        });
}

// Adicionar função global para testes
window.DuraluxAPI = {
    testConnection: testAPIConnection,
    showShortcuts: showKeyboardShortcuts
};

console.log('✅ Duralux API Documentation System carregado com sucesso!');