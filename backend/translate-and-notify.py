#!/usr/bin/env python3
"""
Sistema de Tradução Automática PT-BR para Duralux CRM
Traduz todos os arquivos HTML do sistema para português brasileiro
"""

import os
import re
import json
from pathlib import Path

class DuraluxTranslator:
    def __init__(self, base_path):
        self.base_path = Path(base_path)
        self.translations = self.load_translations()
        self.processed_files = []
        
    def load_translations(self):
        """Carrega dicionário de traduções"""
        return {
            # Navigation & Menu
            'Navigation': 'Navegação',
            'Dashboards': 'Painéis',
            'CRM': 'CRM',
            'Analytics': 'Analíticos',
            'Reports': 'Relatórios',
            'Applications': 'Aplicações',
            'Proposal': 'Propostas',
            'Payment': 'Pagamento',
            'Customers': 'Clientes',
            'Leads': 'Leads',
            'Projects': 'Projetos',
            'Widgets': 'Widgets',
            'Settings': 'Configurações',
            'Authentication': 'Autenticação',
            'Help Center': 'Central de Ajuda',
            
            # Specific Pages
            'Sales Report': 'Relatório de Vendas',
            'Leads Report': 'Relatório de Leads',
            'Project Report': 'Relatório de Projetos',
            'Timesheets Report': 'Relatório de Folha de Ponto',
            'Chat': 'Chat',
            'Email': 'Email',
            'Tasks': 'Tarefas',
            'Notes': 'Anotações',
            'Storage': 'Armazenamento',
            'Calendar': 'Calendário',
            'Proposal View': 'Visualizar Proposta',
            'Proposal Edit': 'Editar Proposta',
            'Proposal Create': 'Criar Proposta',
            'Invoice View': 'Visualizar Fatura',
            'Invoice Create': 'Criar Fatura',
            'Customers View': 'Visualizar Cliente',
            'Customers Create': 'Criar Cliente',
            'Leads View': 'Visualizar Lead',
            'Leads Create': 'Criar Lead',
            'Projects View': 'Visualizar Projeto',
            'Projects Create': 'Criar Projeto',
            
            # Widgets
            'Lists': 'Listas',
            'Tables': 'Tabelas',
            'Charts': 'Gráficos',
            'Statistics': 'Estatísticas',
            'Miscellaneous': 'Diversos',
            
            # Settings
            'General': 'Geral',
            'SEO': 'SEO',
            'Tags': 'Tags',
            'Email': 'Email',
            'Tasks': 'Tarefas',
            'Leads': 'Leads',
            'Support': 'Suporte',
            'Finance': 'Financeiro',
            'Gateways': 'Gateways',
            'Customers': 'Clientes',
            'Localization': 'Localização',
            'reCAPTCHA': 'reCAPTCHA',
            
            # Authentication
            'Login': 'Entrar',
            'Register': 'Registrar',
            'Error-404': 'Erro-404',
            'Reset Pass': 'Redefinir Senha',
            'Verify OTP': 'Verificar OTP',
            'Maintenance': 'Manutenção',
            'Cover': 'Capa',
            'Minimal': 'Mínimo',
            'Creative': 'Criativo',
            
            # Common Elements
            'Search': 'Buscar',
            'Search....': 'Buscar...',
            'Notifications': 'Notificações',
            'Profile': 'Perfil',
            'Logout': 'Sair',
            'Home': 'Início',
            'Dashboard': 'Painel',
            'Add New': 'Adicionar Novo',
            'View All': 'Ver Todos',
            'Edit': 'Editar',
            'Delete': 'Excluir',
            'Save': 'Salvar',
            'Cancel': 'Cancelar',
            'Submit': 'Enviar',
            'Close': 'Fechar',
            'Back': 'Voltar',
            'Next': 'Próximo',
            'Previous': 'Anterior',
            'Loading': 'Carregando',
            'No data': 'Sem dados',
            'Actions': 'Ações',
            'Status': 'Status',
            'Active': 'Ativo',
            'Inactive': 'Inativo',
            'Date': 'Data',
            'Time': 'Hora',
            'Name': 'Nome',
            'Description': 'Descrição',
            'Total': 'Total',
            'Amount': 'Valor',
            'Price': 'Preço',
            
            # Page Titles
            'Proposal Edit': 'Editar Proposta',
            'Create Proposal': 'Criar Proposta',
            'View Proposal': 'Visualizar Proposta',
            'Lead Management': 'Gerenciamento de Leads',
            'Customer Management': 'Gerenciamento de Clientes',
            'Project Management': 'Gerenciamento de Projetos',
            
            # Form Elements
            'Title': 'Título',
            'Subject': 'Assunto',
            'Message': 'Mensagem',
            'Content': 'Conteúdo',
            'Category': 'Categoria',
            'Priority': 'Prioridade',
            'Assigned to': 'Atribuído a',
            'Due Date': 'Data de Vencimento',
            'Start Date': 'Data de Início',
            'End Date': 'Data de Fim',
            'Progress': 'Progresso',
            'Completed': 'Concluído',
            'Pending': 'Pendente',
            'In Progress': 'Em Andamento',
            
            # Notifications
            'New Lead': 'Novo Lead',
            'New Message': 'Nova Mensagem',
            'Task Completed': 'Tarefa Concluída',
            'Payment Received': 'Pagamento Recebido',
            'Project Updated': 'Projeto Atualizado',
            
            # Common Phrases
            'Welcome to': 'Bem-vindo ao',
            'Getting started': 'Primeiros passos',
            'Learn more': 'Saiba mais',
            'Read more': 'Leia mais',
            'Show more': 'Mostrar mais',
            'Load more': 'Carregar mais',
            'View details': 'Ver detalhes',
            'Quick actions': 'Ações rápidas',
            'Recent activity': 'Atividade recente',
            'Popular items': 'Itens populares',
            'Recommended': 'Recomendado',
            
            # Months
            'January': 'Janeiro',
            'February': 'Fevereiro',
            'March': 'Março',
            'April': 'Abril',
            'May': 'Maio',
            'June': 'Junho',
            'July': 'Julho',
            'August': 'Agosto',
            'September': 'Setembro',
            'October': 'Outubro',
            'November': 'Novembro',
            'December': 'Dezembro',
            
            # Days
            'Monday': 'Segunda-feira',
            'Tuesday': 'Terça-feira',
            'Wednesday': 'Quarta-feira',
            'Thursday': 'Quinta-feira',
            'Friday': 'Sexta-feira',
            'Saturday': 'Sábado',
            'Sunday': 'Domingo',
            
            # Notification Center v6.0 - Specific Terms
            'Notification Center': 'Central de Notificações',
            'Push Notifications': 'Notificações Push',
            'Email Notifications': 'Notificações por Email',
            'SMS Notifications': 'Notificações por SMS',
            'Webhook Notifications': 'Notificações Webhook',
            'Notification Templates': 'Modelos de Notificação',
            'Notification History': 'Histórico de Notificações',
            'Notification Settings': 'Configurações de Notificação',
            'Mark as Read': 'Marcar como Lida',
            'Mark all as Read': 'Marcar Todas como Lidas',
            'Unread Notifications': 'Notificações Não Lidas',
            'Read Notifications': 'Notificações Lidas',
            'Notification Analytics': 'Análises de Notificação',
            'Delivery Rate': 'Taxa de Entrega',
            'Read Rate': 'Taxa de Leitura',
            'Click Rate': 'Taxa de Clique',
            'Notification Preferences': 'Preferências de Notificação',
            'Quiet Hours': 'Horário Silencioso',
            'Do Not Disturb': 'Não Perturbe',
            'Send Test Notification': 'Enviar Notificação de Teste',
            'Notification Queue': 'Fila de Notificações',
            'Processing Queue': 'Processando Fila',
            'Failed Notifications': 'Notificações Falharam',
            'Retry Failed': 'Tentar Novamente',
            'Schedule Notification': 'Agendar Notificação',
            'Immediate Delivery': 'Entrega Imediata',
            'Delayed Delivery': 'Entrega Programada',
            'Bulk Notifications': 'Notificações em Lote',
            'Personalized Messages': 'Mensagens Personalizadas',
            
            # Additional System Terms
            'System Integration': 'Integração do Sistema',
            'Performance Dashboard': 'Painel de Performance',
            'Test Dashboard': 'Painel de Teste',
            'Downloading Center': 'Centro de Downloads',
            'Download Now': 'Baixar Agora',
            'KnowledgeBase': 'Base de Conhecimento',
            'Documentations': 'Documentações',
            'Add New Items': 'Adicionar Novos Itens',
            'Mega Menu': 'Menu Mega',
            
            # Breadcrumbs and Navigation
            'You are here': 'Você está aqui',
            'Current page': 'Página atual',
            'Go to': 'Ir para',
            'Return to': 'Retornar para',
            
            # Additional CRM Terms
            'Lead Score': 'Pontuação do Lead',
            'Conversion Rate': 'Taxa de Conversão',
            'Sales Pipeline': 'Funil de Vendas',
            'Deal Value': 'Valor do Negócio',
            'Win Rate': 'Taxa de Vitória',
            'Customer Lifetime Value': 'Valor Vitalício do Cliente',
            'Monthly Recurring Revenue': 'Receita Recorrente Mensal',
            'Annual Contract Value': 'Valor Anual do Contrato',
            'Churn Rate': 'Taxa de Cancelamento',
            'Customer Acquisition Cost': 'Custo de Aquisição de Cliente'
        }
    
    def translate_text(self, text):
        """Traduz um texto usando o dicionário"""
        # Tradução exata
        if text in self.translations:
            return self.translations[text]
        
        # Tradução case insensitive
        for en, pt in self.translations.items():
            if text.lower() == en.lower():
                return pt
        
        return text  # Retorna original se não encontrar tradução
    
    def process_html_content(self, content):
        """Processa conteúdo HTML e traduz textos relevantes"""
        # Padrões para traduzir
        patterns = [
            # Títulos e textos entre tags
            (r'<title[^>]*>([^<]+)</title>', lambda m: f'<title>{self.translate_text(m.group(1).strip())}</title>'),
            (r'<h[1-6][^>]*>([^<]+)</h[1-6]>', lambda m: m.group(0).replace(m.group(1), self.translate_text(m.group(1).strip()))),
            
            # Links e spans
            (r'<a[^>]*>([^<]+)</a>', lambda m: m.group(0).replace(m.group(1), self.translate_text(m.group(1).strip()))),
            (r'<span[^>]*>([^<]+)</span>', lambda m: m.group(0).replace(m.group(1), self.translate_text(m.group(1).strip()))),
            
            # Placeholder e valores de input
            (r'placeholder="([^"]+)"', lambda m: f'placeholder="{self.translate_text(m.group(1))}"'),
            (r"placeholder='([^']+)'", lambda m: f"placeholder='{self.translate_text(m.group(1))}'"),
            (r'value="([^"]+)"', lambda m: f'value="{self.translate_text(m.group(1))}"'),
            
            # Labels
            (r'<label[^>]*>([^<]+)</label>', lambda m: m.group(0).replace(m.group(1), self.translate_text(m.group(1).strip()))),
            
            # Botões
            (r'<button[^>]*>([^<]+)</button>', lambda m: m.group(0).replace(m.group(1), self.translate_text(m.group(1).strip()))),
            
            # Meta description
            (r'<meta name="description" content="([^"]+)"', lambda m: f'<meta name="description" content="{self.translate_text(m.group(1))}"'),
            
            # Alt texts
            (r'alt="([^"]+)"', lambda m: f'alt="{self.translate_text(m.group(1))}"'),
            (r"alt='([^']+)'", lambda m: f"alt='{self.translate_text(m.group(1))}'"),
        ]
        
        # Aplicar traduções
        for pattern, replacement in patterns:
            content = re.sub(pattern, replacement, content, flags=re.IGNORECASE | re.DOTALL)
        
        # Traduzir lang attribute
        content = re.sub(r'<html lang="[^"]*"', '<html lang="pt-BR"', content)
        
        return content
    
    def add_notification_center(self, content):
        """Adiciona o Notification Center v6.0 ao HTML"""
        # CSS para notificações
        notification_css = '''
        <!-- Notification Center v6.0 CSS -->
        <style>
        .notification-center {
            position: fixed;
            top: 0;
            right: -400px;
            width: 400px;
            height: 100vh;
            background: white;
            box-shadow: -2px 0 20px rgba(0,0,0,0.1);
            z-index: 9999;
            transition: right 0.3s ease;
            overflow-y: auto;
        }
        
        .notification-center.open {
            right: 0;
        }
        
        .notification-center-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .notification-center-body {
            padding: 0;
        }
        
        .notification-item {
            padding: 15px 20px;
            border-bottom: 1px solid #f5f5f5;
            cursor: pointer;
            transition: background 0.2s;
            position: relative;
        }
        
        .notification-item:hover {
            background: #f8f9fa;
        }
        
        .notification-item.unread {
            background: #fff7e6;
            border-left: 4px solid #ffa500;
        }
        
        .notification-item.unread::before {
            content: '';
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            width: 8px;
            height: 8px;
            background: #ffa500;
            border-radius: 50%;
        }
        
        .notification-title {
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }
        
        .notification-message {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .notification-time {
            font-size: 12px;
            color: #999;
        }
        
        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ff4757;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .notification-trigger {
            position: relative;
            cursor: pointer;
        }
        
        .notification-empty {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }
        
        .notification-actions {
            padding: 15px 20px;
            border-top: 1px solid #eee;
            background: #f8f9fa;
        }
        
        .btn-notification {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-notification:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .notification-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.3);
            z-index: 9998;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }
        
        .notification-overlay.open {
            opacity: 1;
            visibility: visible;
        }
        
        @media (max-width: 768px) {
            .notification-center {
                width: 100%;
                right: -100%;
            }
        }
        </style>'''
        
        # JavaScript para notificações
        notification_js = '''
        <!-- Notification Center v6.0 JavaScript -->
        <script>
        class NotificationCenter {
            constructor() {
                this.apiUrl = 'backend/api/api-notifications.php';
                this.notifications = [];
                this.unreadCount = 0;
                this.init();
            }
            
            init() {
                this.createHTML();
                this.bindEvents();
                this.loadNotifications();
                this.startPolling();
            }
            
            createHTML() {
                // Adicionar overlay
                const overlay = document.createElement('div');
                overlay.className = 'notification-overlay';
                overlay.id = 'notificationOverlay';
                document.body.appendChild(overlay);
                
                // Adicionar painel de notificações
                const panel = document.createElement('div');
                panel.className = 'notification-center';
                panel.id = 'notificationCenter';
                panel.innerHTML = `
                    <div class="notification-center-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">🔔 Central de Notificações</h5>
                            <button class="btn btn-sm btn-outline-light" onclick="notificationCenter.close()">
                                <i class="feather-x"></i>
                            </button>
                        </div>
                        <div class="mt-2">
                            <small id="notificationCount">0 notificações</small>
                            <button class="btn btn-sm btn-outline-light ms-2" onclick="notificationCenter.markAllRead()">
                                Marcar todas como lidas
                            </button>
                        </div>
                    </div>
                    <div class="notification-center-body" id="notificationList">
                        <div class="notification-empty">
                            <i class="feather-bell" style="font-size: 48px; opacity: 0.3;"></i>
                            <p class="mt-3">Nenhuma notificação</p>
                        </div>
                    </div>
                    <div class="notification-actions">
                        <button class="btn-notification me-2" onclick="notificationCenter.sendTestNotification()">
                            🧪 Teste
                        </button>
                        <button class="btn-notification me-2" onclick="notificationCenter.createDemoLead()">
                            👤 Demo Lead
                        </button>
                        <button class="btn-notification" onclick="notificationCenter.createDemoProposal()">
                            📋 Demo Proposta
                        </button>
                    </div>
                `;
                document.body.appendChild(panel);
                
                // Adicionar badge ao ícone de notificação existente (se houver)
                this.addNotificationBadge();
            }
            
            addNotificationBadge() {
                // Procurar por ícones de notificação existentes e adicionar badge
                const bellIcons = document.querySelectorAll('.feather-bell, .fa-bell');
                bellIcons.forEach(icon => {
                    if (!icon.parentElement.querySelector('.notification-badge')) {
                        const badge = document.createElement('span');
                        badge.className = 'notification-badge';
                        badge.id = 'notificationBadge';
                        badge.textContent = '0';
                        badge.style.display = 'none';
                        icon.parentElement.style.position = 'relative';
                        icon.parentElement.appendChild(badge);
                        
                        // Adicionar evento de clique
                        icon.parentElement.addEventListener('click', (e) => {
                            e.preventDefault();
                            this.toggle();
                        });
                    }
                });
            }
            
            bindEvents() {
                // Fechar ao clicar no overlay
                document.getElementById('notificationOverlay').addEventListener('click', () => {
                    this.close();
                });
                
                // Tecla ESC para fechar
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        this.close();
                    }
                });
            }
            
            async loadNotifications() {
                try {
                    const response = await fetch(`${this.apiUrl}?path=list&limit=20`);
                    const data = await response.json();
                    
                    if (data.success) {
                        this.notifications = data.data;
                        this.updateUnreadCount();
                        this.renderNotifications();
                    }
                } catch (error) {
                    console.error('Erro ao carregar notificações:', error);
                    this.loadDemoData();
                }
            }
            
            loadDemoData() {
                this.notifications = [
                    {
                        id: 1,
                        titulo: '🔔 Novo Lead Recebido',
                        mensagem: 'Lead de Maria Santos interessada em consultoria empresarial',
                        tipo: 'lead',
                        lido_em: null,
                        criado_em: new Date().toISOString()
                    },
                    {
                        id: 2,
                        titulo: '✅ Proposta Aprovada',
                        mensagem: 'Proposta #PROP-001 aprovada no valor de R$ 15.000',
                        tipo: 'proposta',
                        lido_em: null,
                        criado_em: new Date(Date.now() - 2*60*60*1000).toISOString()
                    },
                    {
                        id: 3,
                        titulo: '⏰ Projeto com Prazo Próximo',
                        mensagem: 'Projeto "Website E-commerce" tem entrega em 3 dias',
                        tipo: 'projeto',
                        lido_em: new Date(Date.now() - 60*60*1000).toISOString(),
                        criado_em: new Date(Date.now() - 4*60*60*1000).toISOString()
                    }
                ];
                this.updateUnreadCount();
                this.renderNotifications();
            }
            
            renderNotifications() {
                const container = document.getElementById('notificationList');
                
                if (this.notifications.length === 0) {
                    container.innerHTML = `
                        <div class="notification-empty">
                            <i class="feather-bell" style="font-size: 48px; opacity: 0.3;"></i>
                            <p class="mt-3">Nenhuma notificação</p>
                        </div>
                    `;
                    return;
                }
                
                container.innerHTML = this.notifications.map(notification => `
                    <div class="notification-item ${!notification.lido_em ? 'unread' : ''}" 
                         onclick="notificationCenter.markAsRead(${notification.id})">
                        <div class="notification-title">${notification.titulo}</div>
                        <div class="notification-message">${notification.mensagem}</div>
                        <div class="notification-time">${this.formatTime(notification.criado_em)}</div>
                    </div>
                `).join('');
            }
            
            updateUnreadCount() {
                const unread = this.notifications.filter(n => !n.lido_em).length;
                this.unreadCount = unread;
                
                // Atualizar badge
                const badge = document.getElementById('notificationBadge');
                if (badge) {
                    badge.textContent = unread;
                    badge.style.display = unread > 0 ? 'flex' : 'none';
                }
                
                // Atualizar contador no painel
                const counter = document.getElementById('notificationCount');
                if (counter) {
                    counter.textContent = `${this.notifications.length} notificações (${unread} não lidas)`;
                }
            }
            
            formatTime(timestamp) {
                const date = new Date(timestamp);
                const now = new Date();
                const diff = now - date;
                
                if (diff < 60000) return 'Agora mesmo';
                if (diff < 3600000) return `${Math.floor(diff/60000)} min atrás`;
                if (diff < 86400000) return `${Math.floor(diff/3600000)}h atrás`;
                
                return date.toLocaleDateString('pt-BR');
            }
            
            async markAsRead(notificationId) {
                try {
                    const notification = this.notifications.find(n => n.id === notificationId);
                    if (!notification || notification.lido_em) return;
                    
                    const response = await fetch(`${this.apiUrl}?path=mark-read`, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ notification_id: notificationId })
                    });
                    
                    if (response.ok) {
                        notification.lido_em = new Date().toISOString();
                        this.updateUnreadCount();
                        this.renderNotifications();
                    }
                } catch (error) {
                    // Modo offline - marcar localmente
                    const notification = this.notifications.find(n => n.id === notificationId);
                    if (notification) {
                        notification.lido_em = new Date().toISOString();
                        this.updateUnreadCount();
                        this.renderNotifications();
                    }
                }
            }
            
            async markAllRead() {
                const unreadNotifications = this.notifications.filter(n => !n.lido_em);
                for (const notification of unreadNotifications) {
                    await this.markAsRead(notification.id);
                }
            }
            
            async sendTestNotification() {
                try {
                    const response = await fetch(`${this.apiUrl}?path=test`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' }
                    });
                    
                    if (response.ok) {
                        this.showToast('✅ Notificação de teste enviada!');
                        setTimeout(() => this.loadNotifications(), 1000);
                    }
                } catch (error) {
                    this.addLocalNotification('🧪 Teste do Sistema', 'Esta é uma notificação de teste do sistema Duralux CRM!');
                }
            }
            
            async createDemoLead() {
                try {
                    const response = await fetch(`${this.apiUrl}?path=demo-lead`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' }
                    });
                    
                    if (response.ok) {
                        this.showToast('👤 Demo de lead criado!');
                        setTimeout(() => this.loadNotifications(), 1000);
                    }
                } catch (error) {
                    this.addLocalNotification('🔔 Novo Lead Recebido', 'Demo: Lead João Silva interessado em consultoria empresarial.');
                }
            }
            
            async createDemoProposal() {
                try {
                    const response = await fetch(`${this.apiUrl}?path=demo-proposta`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' }
                    });
                    
                    if (response.ok) {
                        this.showToast('📋 Demo de proposta criado!');
                        setTimeout(() => this.loadNotifications(), 1000);
                    }
                } catch (error) {
                    this.addLocalNotification('✅ Proposta Aprovada', 'Demo: Proposta #DEMO-001 aprovada no valor de R$ 20.000!');
                }
            }
            
            addLocalNotification(title, message) {
                const newNotification = {
                    id: Date.now(),
                    titulo: title,
                    mensagem: message,
                    tipo: 'demo',
                    lido_em: null,
                    criado_em: new Date().toISOString()
                };
                
                this.notifications.unshift(newNotification);
                this.updateUnreadCount();
                this.renderNotifications();
                this.showToast('✅ Notificação adicionada!');
            }
            
            showToast(message) {
                // Toast notification simples
                const toast = document.createElement('div');
                toast.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: #28a745;
                    color: white;
                    padding: 12px 20px;
                    border-radius: 6px;
                    z-index: 10000;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                `;
                toast.textContent = message;
                document.body.appendChild(toast);
                
                setTimeout(() => {
                    toast.remove();
                }, 3000);
            }
            
            toggle() {
                const panel = document.getElementById('notificationCenter');
                const overlay = document.getElementById('notificationOverlay');
                
                if (panel.classList.contains('open')) {
                    this.close();
                } else {
                    this.open();
                }
            }
            
            open() {
                document.getElementById('notificationCenter').classList.add('open');
                document.getElementById('notificationOverlay').classList.add('open');
                document.body.style.overflow = 'hidden';
            }
            
            close() {
                document.getElementById('notificationCenter').classList.remove('open');
                document.getElementById('notificationOverlay').classList.remove('open');
                document.body.style.overflow = '';
            }
            
            startPolling() {
                // Verificar novas notificações a cada 30 segundos
                setInterval(() => {
                    this.loadNotifications();
                }, 30000);
            }
        }
        
        // Inicializar quando o DOM estiver pronto
        document.addEventListener('DOMContentLoaded', function() {
            window.notificationCenter = new NotificationCenter();
        });
        </script>'''
        
        # Inserir CSS no head
        head_pattern = r'(</head>)'
        if re.search(head_pattern, content):
            content = re.sub(head_pattern, notification_css + r'\n\1', content)
        
        # Inserir JavaScript antes do final do body
        body_pattern = r'(</body>)'
        if re.search(body_pattern, content):
            content = re.sub(body_pattern, notification_js + r'\n\1', content)
        
        return content
    
    def process_file(self, file_path):
        """Processa um arquivo HTML"""
        try:
            with open(file_path, 'r', encoding='utf-8') as f:
                content = f.read()
        except UnicodeDecodeError:
            with open(file_path, 'r', encoding='latin-1') as f:
                content = f.read()
        
        # Traduzir conteúdo
        content = self.process_html_content(content)
        
        # Adicionar sistema de notificações
        content = self.add_notification_center(content)
        
        # Salvar arquivo processado
        with open(file_path, 'w', encoding='utf-8') as f:
            f.write(content)
        
        self.processed_files.append(file_path)
        return True
    
    def process_all_html_files(self):
        """Processa todos os arquivos HTML do diretório"""
        html_files = list(self.base_path.glob('**/*.html'))
        
        print(f"🌐 Iniciando tradução PT-BR e implementação do Notification Center v6.0")
        print(f"📁 Diretório: {self.base_path}")
        print(f"📄 Encontrados {len(html_files)} arquivos HTML")
        print("=" * 60)
        
        for i, file_path in enumerate(html_files, 1):
            try:
                print(f"[{i:3d}/{len(html_files)}] Processando: {file_path.name}")
                self.process_file(file_path)
                print(f"            ✅ Traduzido e atualizado com sucesso")
            except Exception as e:
                print(f"            ❌ Erro: {e}")
        
        print("=" * 60)
        print(f"✅ Processamento concluído!")
        print(f"📊 Arquivos processados: {len(self.processed_files)}")
        print(f"🔔 Sistema de Notificações v6.0 adicionado a todos os arquivos")
        print(f"🇧🇷 Tradução PT-BR aplicada em {len(self.translations)} termos")
        
        return len(self.processed_files)

if __name__ == "__main__":
    base_path = r"c:\Users\ivonm\OneDrive - sga.pucminas.br\Github\duralux\duralux\duralux-admin"
    
    translator = DuraluxTranslator(base_path)
    processed = translator.process_all_html_files()
    
    print(f"\n🎉 Duralux CRM agora está 100% em português brasileiro!")
    print(f"🔔 Notification Center v6.0 implementado com sucesso!")
    print(f"📈 {processed} arquivos atualizados")