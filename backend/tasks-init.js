// Script de inicialização das tarefas em português
document.addEventListener('DOMContentLoaded', function() {
    
    // Configuração inicial
    const taskConfig = {
        language: 'pt-BR',
        currency: 'BRL',
        dateFormat: 'DD/MM/YYYY',
        timeFormat: '24h'
    };
    
    // Dados de exemplo das tarefas
    const sampleTasks = [
        {
            id: 1,
            title: "Revisar relatório de vendas",
            description: "Analisar métricas de vendas do último mês",
            assignee: "João Silva",
            priority: "Alta",
            status: "Em Andamento", 
            dueDate: "15/11/2025",
            created: "01/11/2025"
        },
        {
            id: 2,
            title: "Atualizar base de clientes",
            description: "Verificar informações de contato dos clientes ativos",
            assignee: "Maria Santos",
            priority: "Média",
            status: "Pendente",
            dueDate: "20/11/2025", 
            created: "02/11/2025"
        },
        {
            id: 3,
            title: "Preparar apresentação",
            description: "Criar slides para reunião com investidores",
            assignee: "Pedro Costa", 
            priority: "Alta",
            status: "Concluída",
            dueDate: "10/11/2025",
            created: "28/10/2025"
        }
    ];
    
    // Função para renderizar tarefas
    function renderTasks(tasks = sampleTasks) {
        const container = document.querySelector('.tasks-container');
        if (!container) return;
        
        let html = '';
        tasks.forEach(task => {
            const priorityClass = task.priority === 'Alta' ? 'danger' : 
                                 task.priority === 'Média' ? 'warning' : 'success';
            const statusClass = task.status === 'Concluída' ? 'success' :
                               task.status === 'Em Andamento' ? 'primary' : 'secondary';
                               
            html += `
                <div class="task-item card mb-3" data-task-id="${task.id}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h5 class="card-title">${task.title}</h5>
                                <p class="card-text text-muted">${task.description}</p>
                                <div class="task-meta">
                                    <span class="badge bg-${priorityClass} me-2">${task.priority}</span>
                                    <span class="badge bg-${statusClass} me-2">${task.status}</span>
                                    <small class="text-muted">Responsável: ${task.assignee}</small>
                                </div>
                            </div>
                            <div class="task-actions">
                                <button class="btn btn-sm btn-outline-primary me-1" onclick="editTask(${task.id})">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteTask(${task.id})">
                                    <i class="fas fa-trash"></i> Excluir
                                </button>
                            </div>
                        </div>
                        <div class="task-dates mt-2">
                            <small class="text-muted">
                                Vencimento: ${task.dueDate} | Criado: ${task.created}
                            </small>
                        </div>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
    }
    
    // Função para adicionar nova tarefa
    window.addNewTask = function() {
        const modal = new bootstrap.Modal(document.getElementById('taskModal'));
        document.getElementById('taskModalTitle').textContent = 'Nova Tarefa';
        document.getElementById('taskForm').reset();
        modal.show();
    };
    
    // Função para editar tarefa
    window.editTask = function(taskId) {
        const task = sampleTasks.find(t => t.id === taskId);
        if (task) {
            document.getElementById('taskModalTitle').textContent = 'Editar Tarefa';
            document.getElementById('taskTitle').value = task.title;
            document.getElementById('taskDescription').value = task.description;
            document.getElementById('taskAssignee').value = task.assignee;
            document.getElementById('taskPriority').value = task.priority;
            document.getElementById('taskStatus').value = task.status;
            
            const modal = new bootstrap.Modal(document.getElementById('taskModal'));
            modal.show();
        }
    };
    
    // Função para excluir tarefa
    window.deleteTask = function(taskId) {
        if (confirm('Tem certeza que deseja excluir esta tarefa?')) {
            const index = sampleTasks.findIndex(t => t.id === taskId);
            if (index > -1) {
                sampleTasks.splice(index, 1);
                renderTasks();
                showNotification('Tarefa excluída com sucesso!', 'success');
            }
        }
    };
    
    // Função para salvar tarefa
    window.saveTask = function() {
        const form = document.getElementById('taskForm');
        const formData = new FormData(form);
        
        const newTask = {
            id: Date.now(),
            title: formData.get('title') || 'Nova Tarefa',
            description: formData.get('description') || '',
            assignee: formData.get('assignee') || 'Não atribuído',
            priority: formData.get('priority') || 'Média',
            status: formData.get('status') || 'Pendente',
            dueDate: new Date().toLocaleDateString('pt-BR'),
            created: new Date().toLocaleDateString('pt-BR')
        };
        
        sampleTasks.unshift(newTask);
        renderTasks();
        
        const modal = bootstrap.Modal.getInstance(document.getElementById('taskModal'));
        modal.hide();
        
        showNotification('Tarefa salva com sucesso!', 'success');
    };
    
    // Função para buscar tarefas
    window.searchTasks = function(query) {
        const filtered = sampleTasks.filter(task => 
            task.title.toLowerCase().includes(query.toLowerCase()) ||
            task.description.toLowerCase().includes(query.toLowerCase()) ||
            task.assignee.toLowerCase().includes(query.toLowerCase())
        );
        renderTasks(filtered);
    };
    
    // Função para filtrar por status
    window.filterByStatus = function(status) {
        if (status === 'all') {
            renderTasks(sampleTasks);
        } else {
            const filtered = sampleTasks.filter(task => task.status === status);
            renderTasks(filtered);
        }
    };
    
    // Função para mostrar notificações
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
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
    
    // Inicializar página
    renderTasks();
    
    // Event listeners
    const searchInput = document.getElementById('taskSearch');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            searchTasks(e.target.value);
        });
    }
    
    const statusFilter = document.getElementById('statusFilter');
    if (statusFilter) {
        statusFilter.addEventListener('change', (e) => {
            filterByStatus(e.target.value);
        });
    }
    
    console.log('✅ Sistema de tarefas inicializado com sucesso!');
    showNotification('Sistema de tarefas carregado!', 'success');
});