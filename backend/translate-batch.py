#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
🌍 TRADUTOR EM LOTE - PÁGINAS CRÍTICAS DO DURALUX CRM
Script para traduzir múltiplas páginas críticas para PT-BR
"""

import os
import shutil

def batch_translate_critical_pages():
    """Traduz em lote as páginas mais críticas do sistema"""
    
    print("🌍 TRADUÇÃO EM LOTE - PÁGINAS CRÍTICAS")
    print("=" * 50)
    
    # Caminhos base
    admin_path = r"c:\Users\ivonm\OneDrive - sga.pucminas.br\Github\duralux\duralux\duralux-admin"
    wamp_admin_path = r"C:\wamp64\www\duralux\duralux-admin"
    
    # Páginas críticas para traduzir
    critical_pages = [
        'customers.html',
        'reports.html',
        'settings-general.html',
        'analytics.html',
        'apps-calendar.html',
        'apps-email.html',
        'apps-tasks.html'
    ]
    
    # Dicionário universal de traduções
    universal_translations = {
        # Títulos comuns
        'Analytics': 'Análises',
        'Customers': 'Clientes', 
        'Reports': 'Relatórios',
        'Settings': 'Configurações',
        'Calendar': 'Calendário',
        'Email': 'Email',
        'Tasks': 'Tarefas',
        'General': 'Geral',
        
        # Breadcrumbs
        '<li class="breadcrumb-item">Analytics</li>': '<li class="breadcrumb-item">Análises</li>',
        '<li class="breadcrumb-item">Customers</li>': '<li class="breadcrumb-item">Clientes</li>',
        '<li class="breadcrumb-item">Reports</li>': '<li class="breadcrumb-item">Relatórios</li>',
        '<li class="breadcrumb-item">Settings</li>': '<li class="breadcrumb-item">Configurações</li>',
        '<li class="breadcrumb-item">Calendar</li>': '<li class="breadcrumb-item">Calendário</li>',
        '<li class="breadcrumb-item">Email</li>': '<li class="breadcrumb-item">Email</li>',
        '<li class="breadcrumb-item">Tasks</li>': '<li class="breadcrumb-item">Tarefas</li>',
        
        # Títulos de páginas
        'Duralux || Analytics': 'Duralux || Análises',
        'Duralux || Customers': 'Duralux || Clientes',
        'Duralux || Reports': 'Duralux || Relatórios',
        'Duralux || Settings': 'Duralux || Configurações',
        'Duralux || Calendar': 'Duralux || Calendário',
        'Duralux || Email': 'Duralux || Email',
        'Duralux || Tasks': 'Duralux || Tarefas',
        'Duralux || General Settings': 'Duralux || Configurações Gerais',
        
        # Botões universais
        'Create New': 'Criar Novo',
        'Add New': 'Adicionar Novo',
        'New Customer': 'Novo Cliente',
        'Create Customer': 'Criar Cliente',
        'Edit Customer': 'Editar Cliente',
        'Delete Customer': 'Excluir Cliente',
        'Save Customer': 'Salvar Cliente',
        'View Customer': 'Visualizar Cliente',
        'Customer Details': 'Detalhes do Cliente',
        
        'New Task': 'Nova Tarefa',
        'Create Task': 'Criar Tarefa',
        'Edit Task': 'Editar Tarefa',
        'Delete Task': 'Excluir Tarefa',
        'Save Task': 'Salvar Tarefa',
        'Complete Task': 'Concluir Tarefa',
        'Task Details': 'Detalhes da Tarefa',
        
        'New Event': 'Novo Evento',
        'Create Event': 'Criar Evento',
        'Edit Event': 'Editar Evento',
        'Delete Event': 'Excluir Evento',
        'Save Event': 'Salvar Evento',
        'Event Details': 'Detalhes do Evento',
        
        'New Report': 'Novo Relatório',
        'Create Report': 'Criar Relatório',
        'Generate Report': 'Gerar Relatório',
        'Export Report': 'Exportar Relatório',
        'View Report': 'Visualizar Relatório',
        
        # Campos comuns
        'Customer Name': 'Nome do Cliente',
        'Company Name': 'Nome da Empresa',
        'Contact Person': 'Pessoa de Contato',
        'Email Address': 'Endereço de Email',
        'Phone Number': 'Número de Telefone',
        'Task Name': 'Nome da Tarefa',
        'Task Description': 'Descrição da Tarefa',
        'Due Date': 'Data de Vencimento',
        'Start Date': 'Data de Início',
        'End Date': 'Data de Término',
        'Priority': 'Prioridade',
        'Status': 'Status',
        'Assigned To': 'Atribuído para',
        'Created By': 'Criado por',
        'Created At': 'Criado em',
        'Updated At': 'Atualizado em',
        
        # Status universais
        'Active': 'Ativo',
        'Inactive': 'Inativo',
        'Pending': 'Pendente',
        'Completed': 'Concluído',
        'In Progress': 'Em Andamento',
        'On Hold': 'Pausado',
        'Cancelled': 'Cancelado',
        'Draft': 'Rascunho',
        'Published': 'Publicado',
        'Archived': 'Arquivado',
        
        # Prioridades
        'Low': 'Baixa',
        'Medium': 'Média',
        'High': 'Alta',
        'Urgent': 'Urgente',
        'Critical': 'Crítica',
        
        # Ações comuns
        'Edit': 'Editar',
        'View': 'Visualizar',
        'Delete': 'Excluir',
        'Save': 'Salvar',
        'Cancel': 'Cancelar',
        'Close': 'Fechar',
        'Update': 'Atualizar',
        'Submit': 'Enviar',
        'Reset': 'Limpar',
        'Clear': 'Limpar',
        'Search': 'Buscar',
        'Filter': 'Filtrar',
        'Export': 'Exportar',
        'Import': 'Importar',
        'Print': 'Imprimir',
        'Download': 'Baixar',
        'Upload': 'Enviar',
        'Select': 'Selecionar',
        'Choose': 'Escolher',
        'Browse': 'Navegar',
        'Back': 'Voltar',
        'Next': 'Próximo',
        'Previous': 'Anterior',
        'Finish': 'Finalizar',
        'Continue': 'Continuar',
        'Skip': 'Pular',
        
        # Headers de tabela
        'Name': 'Nome',
        'Email': 'Email',
        'Phone': 'Telefone',
        'Company': 'Empresa',
        'Address': 'Endereço',
        'City': 'Cidade',
        'State': 'Estado',
        'Country': 'País',
        'Date': 'Data',
        'Time': 'Hora',
        'Actions': 'Ações',
        'Details': 'Detalhes',
        'Notes': 'Observações',
        'Comments': 'Comentários',
        'Description': 'Descrição',
        
        # Mensagens comuns
        'Loading...': 'Carregando...',
        'Processing...': 'Processando...',
        'Please wait...': 'Por favor, aguarde...',
        'Success': 'Sucesso',
        'Error': 'Erro',
        'Warning': 'Aviso',
        'Info': 'Informação',
        'Confirm': 'Confirmar',
        'Yes': 'Sim',
        'No': 'Não',
        'OK': 'OK',
        
        # Formulários
        'This field is required': 'Este campo é obrigatório',
        'Please enter a valid email': 'Por favor, insira um email válido',
        'Please select an option': 'Por favor, selecione uma opção',
        'Form submitted successfully': 'Formulário enviado com sucesso',
        'Error submitting form': 'Erro ao enviar formulário',
        
        # Paginação
        'Showing': 'Mostrando',
        'of': 'de',
        'entries': 'registros',
        'No data available': 'Nenhum dado disponível',
        'First': 'Primeiro',
        'Last': 'Último',
        'Records per page': 'Registros por página',
        
        # Filtros
        'All': 'Todos',
        'Filter by': 'Filtrar por',
        'Sort by': 'Ordenar por',
        'Order': 'Ordem',
        'Ascending': 'Crescente',
        'Descending': 'Decrescente',
        
        # Configurações
        'General Settings': 'Configurações Gerais',
        'System Settings': 'Configurações do Sistema',
        'User Settings': 'Configurações do Usuário',
        'Application Settings': 'Configurações da Aplicação',
        'Security Settings': 'Configurações de Segurança',
        'Privacy Settings': 'Configurações de Privacidade',
        'Notification Settings': 'Configurações de Notificação',
        
        # Email
        'Inbox': 'Caixa de Entrada',
        'Sent': 'Enviados',
        'Drafts': 'Rascunhos',
        'Trash': 'Lixeira',
        'Compose': 'Redigir',
        'Reply': 'Responder',
        'Forward': 'Encaminhar',
        'Subject': 'Assunto',
        'Message': 'Mensagem',
        'Attachment': 'Anexo',
        'Send': 'Enviar',
        
        # Calendário
        'Today': 'Hoje',
        'Tomorrow': 'Amanhã',
        'Yesterday': 'Ontem',
        'This Week': 'Esta Semana',
        'Next Week': 'Próxima Semana',
        'This Month': 'Este Mês',
        'Next Month': 'Próximo Mês',
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
        'Sunday': 'Domingo',
        'Monday': 'Segunda-feira',
        'Tuesday': 'Terça-feira',
        'Wednesday': 'Quarta-feira',
        'Thursday': 'Quinta-feira',
        'Friday': 'Sexta-feira',
        'Saturday': 'Sábado',
    }
    
    total_translated = 0
    processed_files = 0
    
    for page in critical_pages:
        try:
            original_file = os.path.join(admin_path, page)
            wamp_file = os.path.join(wamp_admin_path, page)
            
            if not os.path.exists(original_file):
                print(f"⚠️ Arquivo não encontrado: {page}")
                continue
            
            print(f"\n🔄 Processando: {page}")
            
            # Lê arquivo
            with open(original_file, 'r', encoding='utf-8') as f:
                content = f.read()
            
            # Aplica traduções
            file_translations = 0
            for english_text, portuguese_text in universal_translations.items():
                if english_text in content and english_text != portuguese_text:
                    content = content.replace(english_text, portuguese_text)
                    file_translations += 1
            
            if file_translations > 0:
                # Salva arquivo
                with open(original_file, 'w', encoding='utf-8') as f:
                    f.write(content)
                
                # Copia para WAMP
                shutil.copy2(original_file, wamp_file)
                
                print(f"✅ {page}: {file_translations} traduções aplicadas")
                total_translated += file_translations
            else:
                print(f"ℹ️ {page}: Já em PT-BR")
            
            processed_files += 1
            
        except Exception as e:
            print(f"❌ Erro ao processar {page}: {e}")
    
    # Relatório final
    print("\n" + "=" * 50)
    print(f"📊 RELATÓRIO DE TRADUÇÃO EM LOTE:")
    print(f"Arquivos processados: {processed_files}/{len(critical_pages)}")
    print(f"Total de traduções aplicadas: {total_translated}")
    print(f"Status: {'✅ CONCLUÍDO' if processed_files == len(critical_pages) else '⚠️ PARCIAL'}")
    
    # URLs para teste
    print(f"\n🌐 TESTE AS PÁGINAS TRADUZIDAS:")
    for page in critical_pages[:5]:  # Mostra apenas as 5 primeiras
        print(f"   • http://localhost/duralux/duralux-admin/{page}")

if __name__ == "__main__":
    batch_translate_critical_pages()