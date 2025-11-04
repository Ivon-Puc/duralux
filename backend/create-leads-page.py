import os

def create_leads_functional_page():
    """Cria página de leads totalmente funcional"""
    
    content = '''<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Duralux - Gestão de Leads</title>
    
    <!-- CSS -->
    <link href="/duralux/duralux-admin/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="/duralux/duralux-admin/assets/css/theme.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #f8f9fa;
        }
        .main-header { 
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%); 
            color: white; 
            padding: 25px; 
            border-radius: 15px; 
            margin-bottom: 30px; 
            box-shadow: 0 10px 30px rgba(40, 167, 69, 0.3);
        }
        .sidebar { 
            background: white; 
            min-height: 100vh; 
            padding: 25px 20px; 
            box-shadow: 2px 0 15px rgba(0,0,0,0.1);
        }
        .sidebar .nav-link { 
            color: #495057; 
            padding: 15px 20px; 
            margin: 5px 0; 
            border-radius: 12px; 
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { 
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%); 
            color: white; 
            transform: translateX(5px);
        }
        .lead-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.2s;
            border-left: 5px solid transparent;
        }
        .lead-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .lead-card.hot { border-left-color: #dc3545; }
        .lead-card.warm { border-left-color: #ffc107; }
        .lead-card.cold { border-left-color: #6c757d; }
        .lead-score {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9rem;
        }
        .pipeline-stage {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            min-height: 200px;
        }
        .pipeline-stage h6 {
            color: #495057;
            font-weight: 600;
            margin-bottom: 15px;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }
    </style>
</head>

<body>
    <div class="container-fluid p-0">
        <div class="row g-0">
            <!-- Sidebar -->
            <div class="col-md-2">
                <div class="sidebar">
                    <div class="logo mb-4 text-center">
                        <h3 class="text-success mb-0">
                            <i class="fas fa-gem me-2"></i>Duralux
                        </h3>
                        <small class="text-muted">CRM Professional</small>
                    </div>
                    
                    <nav class="nav flex-column">
                        <a href="/duralux/duralux-admin/index.html" class="nav-link">
                            <i class="fas fa-home me-3"></i> Dashboard
                        </a>
                        <a href="/duralux/duralux-admin/apps-tasks.html" class="nav-link">
                            <i class="fas fa-tasks me-3"></i> Tarefas
                        </a>
                        <a href="/duralux/duralux-admin/customers.html" class="nav-link">
                            <i class="fas fa-users me-3"></i> Clientes
                        </a>
                        <a href="/duralux/duralux-admin/leads.html" class="nav-link active">
                            <i class="fas fa-bullseye me-3"></i> Leads
                        </a>
                        <a href="/duralux/duralux-admin/projects.html" class="nav-link">
                            <i class="fas fa-project-diagram me-3"></i> Projetos
                        </a>
                        <a href="/duralux/duralux-admin/reports-sales.html" class="nav-link">
                            <i class="fas fa-chart-line me-3"></i> Relatórios
                        </a>
                        <a href="/duralux/duralux-admin/analytics-advanced.html" class="nav-link">
                            <i class="fas fa-chart-bar me-3"></i> Analytics
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10">
                <div class="p-4">
                    <!-- Header -->
                    <div class="main-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h1 class="mb-1">🎯 Gestão de Leads</h1>
                                <p class="mb-0 opacity-90">Pipeline de vendas e oportunidades</p>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-light btn-lg" onclick="addNewLead()">
                                    <i class="fas fa-plus me-2"></i>Novo Lead
                                </button>
                                <button class="btn btn-outline-light" onclick="importLeads()">
                                    <i class="fas fa-upload me-2"></i>Importar
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card text-center border-0 shadow-sm">
                                <div class="card-body">
                                    <i class="fas fa-bullseye fa-2x text-danger mb-2"></i>
                                    <h4 class="text-danger">23</h4>
                                    <small class="text-muted">Leads Quentes</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center border-0 shadow-sm">
                                <div class="card-body">
                                    <i class="fas fa-fire fa-2x text-warning mb-2"></i>
                                    <h4 class="text-warning">45</h4>
                                    <small class="text-muted">Leads Mornos</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center border-0 shadow-sm">
                                <div class="card-body">
                                    <i class="fas fa-snowflake fa-2x text-info mb-2"></i>
                                    <h4 class="text-info">67</h4>
                                    <small class="text-muted">Leads Frios</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center border-0 shadow-sm">
                                <div class="card-body">
                                    <i class="fas fa-handshake fa-2x text-success mb-2"></i>
                                    <h4 class="text-success">R$ 485K</h4>
                                    <small class="text-muted">Pipeline Value</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pipeline View -->
                    <div class="row">
                        <div class="col-md-3">
                            <div class="pipeline-stage">
                                <h6><i class="fas fa-eye me-2"></i>Prospecção (15)</h6>
                                <div id="prospecting-leads"></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="pipeline-stage">
                                <h6><i class="fas fa-phone me-2"></i>Contato Inicial (12)</h6>
                                <div id="contact-leads"></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="pipeline-stage">
                                <h6><i class="fas fa-handshake me-2"></i>Negociação (8)</h6>
                                <div id="negotiation-leads"></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="pipeline-stage">
                                <h6><i class="fas fa-check me-2"></i>Fechamento (5)</h6>
                                <div id="closing-leads"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lead Modal -->
    <div class="modal fade" id="leadModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Novo Lead</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="leadForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nome Completo *</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email *</label>
                                    <input type="email" class="form-control" name="email" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Telefone</label>
                                    <input type="tel" class="form-control" name="phone">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Empresa</label>
                                    <input type="text" class="form-control" name="company">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Temperatura</label>
                                    <select class="form-select" name="temperature">
                                        <option value="hot">🔥 Quente</option>
                                        <option value="warm" selected>🌡️ Morno</option>
                                        <option value="cold">❄️ Frio</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Score (0-100)</label>
                                    <input type="number" class="form-control" name="score" min="0" max="100" value="50">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Valor Estimado</label>
                                    <input type="text" class="form-control" name="value" placeholder="R$ 0,00">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Observações</label>
                            <textarea class="form-control" name="notes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" onclick="saveLead()">
                        <i class="fas fa-save me-2"></i>Salvar Lead
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sample leads data
        const leads = [
            {
                id: 1,
                name: "João Empresário",
                company: "Tech Solutions Ltda",
                email: "joao@techsolutions.com",
                phone: "(11) 9999-8888",
                temperature: "hot",
                score: 85,
                value: "R$ 25.000",
                stage: "negotiation",
                notes: "Interessado em solução completa de CRM"
            },
            {
                id: 2,
                name: "Maria Gestora",
                company: "Varejo & Cia",
                email: "maria@varejoecia.com",
                phone: "(21) 8888-7777",
                temperature: "warm",
                score: 65,
                value: "R$ 15.500",
                stage: "contact",
                notes: "Precisa de aprovação da diretoria"
            },
            {
                id: 3,
                name: "Pedro Vendedor",
                company: "AutoCenter",
                email: "pedro@autocenter.com",
                phone: "(31) 7777-6666",
                temperature: "cold",
                score: 35,
                value: "R$ 8.000",
                stage: "prospecting",
                notes: "Ainda avaliando opções no mercado"
            }
        ];

        function renderLeads() {
            // Group leads by stage
            const stages = {
                'prospecting': document.getElementById('prospecting-leads'),
                'contact': document.getElementById('contact-leads'),
                'negotiation': document.getElementById('negotiation-leads'),
                'closing': document.getElementById('closing-leads')
            };

            // Clear all stages
            Object.values(stages).forEach(stage => stage.innerHTML = '');

            // Render leads in their respective stages
            leads.forEach(lead => {
                const tempColor = lead.temperature === 'hot' ? 'danger' : 
                                 lead.temperature === 'warm' ? 'warning' : 'secondary';
                
                const leadCard = `
                    <div class="lead-card ${lead.temperature} position-relative mb-3">
                        <div class="lead-score">${lead.score}</div>
                        <h6 class="mb-2">${lead.name}</h6>
                        <p class="mb-1 text-muted small">${lead.company}</p>
                        <p class="mb-2 text-success fw-bold">${lead.value}</p>
                        <div class="d-flex gap-1">
                            <button class="btn btn-sm btn-outline-primary" onclick="editLead(${lead.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" onclick="moveLead(${lead.id})">
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                `;
                
                if (stages[lead.stage]) {
                    stages[lead.stage].innerHTML += leadCard;
                }
            });
        }

        function addNewLead() {
            const modal = new bootstrap.Modal(document.getElementById('leadModal'));
            document.getElementById('leadForm').reset();
            modal.show();
        }

        function saveLead() {
            const form = document.getElementById('leadForm');
            const formData = new FormData(form);
            
            const newLead = {
                id: Date.now(),
                name: formData.get('name'),
                company: formData.get('company') || 'Não informado',
                email: formData.get('email'),
                phone: formData.get('phone') || 'Não informado',
                temperature: formData.get('temperature'),
                score: parseInt(formData.get('score')),
                value: formData.get('value') || 'R$ 0',
                stage: 'prospecting',
                notes: formData.get('notes') || ''
            };
            
            leads.unshift(newLead);
            renderLeads();
            
            const modal = bootstrap.Modal.getInstance(document.getElementById('leadModal'));
            modal.hide();
            
            showNotification('Lead adicionado com sucesso!', 'success');
        }

        function editLead(leadId) {
            showNotification('Edição de lead em desenvolvimento', 'info');
        }

        function moveLead(leadId) {
            const lead = leads.find(l => l.id === leadId);
            if (lead) {
                const stages = ['prospecting', 'contact', 'negotiation', 'closing'];
                const currentIndex = stages.indexOf(lead.stage);
                if (currentIndex < stages.length - 1) {
                    lead.stage = stages[currentIndex + 1];
                    renderLeads();
                    showNotification('Lead movido para próxima etapa!', 'success');
                } else {
                    showNotification('Lead já está na última etapa!', 'warning');
                }
            }
        }

        function importLeads() {
            showNotification('Função de importação em desenvolvimento', 'info');
        }

        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 350px;';
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 5000);
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            renderLeads();
            showNotification('Sistema de leads carregado!', 'success');
        });
    </script>
</body>
</html>'''
    
    return content

def apply_leads_page():
    """Aplica a página de leads funcional"""
    
    wamp_path = r"C:\wamp64\www\duralux\duralux-admin"
    leads_content = create_leads_functional_page()
    
    with open(os.path.join(wamp_path, "leads.html"), 'w', encoding='utf-8') as f:
        f.write(leads_content)
    
    print("✅ Página de leads funcional criada")

if __name__ == "__main__":
    print("🚀 Criando página de leads funcional...")
    apply_leads_page()
    print("🎯 Teste: http://localhost/duralux/duralux-admin/leads.html")