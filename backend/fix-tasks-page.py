import os
import shutil

def fix_tasks_page():
    """Corrige a página de tarefas com tradução completa e funcionalidades"""
    
    # Copiar arquivo para WAMP e corrigir
    source_file = r"c:\Users\ivonm\OneDrive - sga.pucminas.br\Github\duralux\duralux\duralux-admin\apps-tasks.html"
    wamp_file = r"C:\wamp64\www\duralux\duralux-admin\apps-tasks.html"
    
    print("🔧 Corrigindo página de tarefas...")
    
    try:
        with open(source_file, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Traduções específicas para tarefas
        translations = {
            'Assignee:': 'Responsável:',
            'End date...': 'Data final...',
            'Start date...': 'Data inicial...',
            'Task Title': 'Título da Tarefa',
            'Task Description': 'Descrição da Tarefa',
            'Priority': 'Prioridade',
            'Status': 'Status',
            'Add Task': 'Adicionar Tarefa',
            'Edit Task': 'Editar Tarefa',
            'Delete Task': 'Excluir Tarefa',
            'Save Task': 'Salvar Tarefa',
            'Cancel': 'Cancelar',
            'High': 'Alta',
            'Medium': 'Média', 
            'Low': 'Baixa',
            'Pending': 'Pendente',
            'In Progress': 'Em Andamento',
            'Completed': 'Concluída',
            'Due Date': 'Data de Vencimento',
            'Created': 'Criado',
            'Updated': 'Atualizado',
            'Task List': 'Lista de Tarefas',
            'No tasks found': 'Nenhuma tarefa encontrada',
            'Search tasks...': 'Buscar tarefas...',
            'Filter by status': 'Filtrar por status',
            'All Tasks': 'Todas as Tarefas',
            'My Tasks': 'Minhas Tarefas',
            'Team Tasks': 'Tarefas da Equipe'
        }
        
        # Aplicar traduções
        for english, portuguese in translations.items():
            content = content.replace(english, portuguese)
        
        # Corrigir URLs para WAMP (caso não tenha sido feito)
        url_fixes = {
            'href="assets/': 'href="/duralux/duralux-admin/assets/',
            'src="assets/': 'src="/duralux/duralux-admin/assets/',
            '"backend/api/': '"/duralux/backend/api/',
            "'backend/api/": "'/duralux/backend/api/"
        }
        
        for old_url, new_url in url_fixes.items():
            content = content.replace(old_url, new_url)
        
        # Salvar arquivo corrigido no WAMP
        with open(wamp_file, 'w', encoding='utf-8') as f:
            f.write(content)
        
        print("✅ Página de tarefas corrigida e traduzida")
        print("📁 Arquivo atualizado em:", wamp_file)
        
        # Verificar se o arquivo JS de tarefas existe
        js_file = r"C:\wamp64\www\duralux\duralux-admin\assets\js\apps-tasks-init.min.js"
        if os.path.exists(js_file):
            print("✅ Arquivo JavaScript encontrado")
        else:
            print("⚠️ Arquivo JavaScript não encontrado - funcionalidades podem não funcionar")
        
        return True
        
    except Exception as e:
        print(f"❌ Erro ao corrigir página: {e}")
        return False

if __name__ == "__main__":
    print("🚀 DURALUX - Correção da Página de Tarefas")
    print("=" * 50)
    
    if fix_tasks_page():
        print("\n🎉 Correção concluída!")
        print("🌐 Teste: http://localhost/duralux/duralux-admin/apps-tasks.html")
        print("🔐 Faça login primeiro se necessário")
    else:
        print("\n❌ Falha na correção")