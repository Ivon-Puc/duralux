#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
🌍 TRADUTOR COMPLETO PROJECTS.HTML - PT-BR
Script para traduzir todo conteúdo em inglês para português brasileiro
"""

import os
import shutil

def translate_projects_html():
    """Traduz completamente o arquivo projects.html para PT-BR"""
    
    print("🌍 TRADUZINDO PROJECTS.HTML PARA PT-BR")
    print("=" * 50)
    
    # Caminhos
    original_file = r"c:\Users\ivonm\OneDrive - sga.pucminas.br\Github\duralux\duralux\duralux-admin\projects.html"
    wamp_file = r"C:\wamp64\www\duralux\duralux-admin\projects.html"
    
    # Dicionário de traduções
    translations = {
        # Título e navegação
        'Duralux || Projects': 'Duralux || Projetos',
        '<li class="breadcrumb-item">Projects</li>': '<li class="breadcrumb-item">Projetos</li>',
        
        # Filtros e opções
        'Alls': 'Todos',
        'On Hold': 'Pausado',
        'Finished': 'Concluído',
        'Declined': 'Recusado',
        'Not Started': 'Não Iniciado',
        'My Projects': 'Meus Projetos',
        
        # Formato de arquivos
        'Text': 'Texto',
        'Excel': 'Excel',
        'Print': 'Imprimir',
        
        # Status do projeto - versões completas
        '"planning"': '"planning"',  # Mantém código, mas traduz label
        '"in_progress"': '"in_progress"',
        '"review"': '"review"',
        '"completed"': '"completed"',
        '"on_hold"': '"on_hold"',
        '"cancelled"': '"cancelled"',
        
        # Labels de status para exibição
        'Planning': 'Planejamento',
        'In Progress': 'Em Andamento',
        'Review': 'Em Revisão',
        'Completed': 'Concluído',
        'On Hold': 'Pausado',
        'Cancelled': 'Cancelado',
        
        # Prioridades - labels
        'Low': 'Baixa',
        'Medium': 'Média', 
        'High': 'Alta',
        'Urgent': 'Urgente',
        
        # Campos de formulário
        'Project Name': 'Nome do Projeto',
        'Customer': 'Cliente',
        'Description': 'Descrição',
        'Status': 'Status',
        'Priority': 'Prioridade',
        'Budget': 'Orçamento',
        'Start Date': 'Data de Início',
        'Due Date': 'Data de Entrega',
        'End Date': 'Data de Término',
        
        # Botões e ações
        'New Project': 'Novo Projeto',
        'Create Project': 'Criar Projeto',
        'Save Project': 'Salvar Projeto',
        'Edit Project': 'Editar Projeto',
        'Delete Project': 'Excluir Projeto',
        'View Details': 'Ver Detalhes',
        'Cancel': 'Cancelar',
        'Close': 'Fechar',
        'Save': 'Salvar',
        'Update': 'Atualizar',
        'Delete': 'Excluir',
        'Edit': 'Editar',
        'View': 'Visualizar',
        
        # Tabela e listagem
        'Actions': 'Ações',
        'Progress': 'Progresso',
        'Deadline': 'Prazo',
        'Created': 'Criado',
        'Updated': 'Atualizado',
        'Total Projects': 'Total de Projetos',
        'Active Projects': 'Projetos Ativos',
        'Completed Projects': 'Projetos Concluídos',
        'Overdue Projects': 'Projetos Atrasados',
        
        # Mensagens e placeholders
        'Search by name, description...': 'Buscar por nome, descrição...',
        'Select a customer': 'Selecione um cliente',
        'All Priorities': 'Todas as Prioridades',
        'All Customers': 'Todos os Clientes',
        'All Status': 'Todos os Status',
        'Loading...': 'Carregando...',
        'No projects found': 'Nenhum projeto encontrado',
        'Project Details': 'Detalhes do Projeto',
        'Project Information': 'Informações do Projeto',
        
        # Confirmações e alertas
        'Are you sure?': 'Tem certeza?',
        'This action cannot be undone': 'Esta ação não pode ser desfeita',
        'Project deleted successfully': 'Projeto excluído com sucesso',
        'Project saved successfully': 'Projeto salvo com sucesso',
        'Project updated successfully': 'Projeto atualizado com sucesso',
        'Error saving project': 'Erro ao salvar projeto',
        'Error loading projects': 'Erro ao carregar projetos',
        
        # Outros elementos
        'Previous': 'Anterior',
        'Next': 'Próximo',
        'First': 'Primeiro',
        'Last': 'Último',
        'of': 'de',
        'Showing': 'Mostrando',
        'entries': 'registros',
        'No data available': 'Nenhum dado disponível',
        'Processing...': 'Processando...',
    }
    
    try:
        # Lê o arquivo original
        with open(original_file, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Aplica todas as traduções
        modified = False
        for english_text, portuguese_text in translations.items():
            if english_text in content:
                content = content.replace(english_text, portuguese_text)
                modified = True
                print(f"✅ Traduzido: {english_text} → {portuguese_text}")
        
        if modified:
            # Salva no arquivo original
            with open(original_file, 'w', encoding='utf-8') as f:
                f.write(content)
            
            # Copia para WAMP
            shutil.copy2(original_file, wamp_file)
            
            print(f"\n✅ Arquivo projects.html traduzido com sucesso!")
            print(f"📁 Atualizado: {original_file}")
            print(f"📁 Sincronizado: {wamp_file}")
        else:
            print("ℹ️ Nenhuma tradução necessária - arquivo já em PT-BR")
            
    except Exception as e:
        print(f"❌ Erro na tradução: {e}")

def check_other_english_content():
    """Verifica se há mais conteúdo em inglês no arquivo"""
    
    print("\n🔍 VERIFICANDO CONTEÚDO RESTANTE EM INGLÊS")
    print("-" * 40)
    
    file_path = r"c:\Users\ivonm\OneDrive - sga.pucminas.br\Github\duralux\duralux\duralux-admin\projects.html"
    
    # Palavras em inglês comuns para verificar
    english_terms = [
        'project', 'status', 'priority', 'customer', 'description',
        'created', 'updated', 'deadline', 'progress', 'budget',
        'start', 'end', 'date', 'name', 'edit', 'delete', 'view',
        'save', 'cancel', 'close', 'new', 'create', 'update',
        'loading', 'search', 'filter', 'all', 'active', 'completed',
        'planning', 'review', 'hold', 'cancelled', 'low', 'medium',
        'high', 'urgent', 'actions', 'details', 'information'
    ]
    
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read().lower()
        
        found_terms = []
        for term in english_terms:
            if f'>{term}<' in content or f'"{term}"' in content or f"'{term}'" in content:
                found_terms.append(term)
        
        if found_terms:
            print(f"⚠️ Ainda há {len(found_terms)} termos em inglês encontrados:")
            for term in found_terms[:10]:  # Mostra apenas os primeiros 10
                print(f"   • {term}")
            if len(found_terms) > 10:
                print(f"   • ... e mais {len(found_terms) - 10} termos")
        else:
            print("✅ Nenhum termo comum em inglês encontrado!")
            
    except Exception as e:
        print(f"❌ Erro na verificação: {e}")

if __name__ == "__main__":
    translate_projects_html()
    check_other_english_content()
    
    print("\n🌐 TESTE A PÁGINA TRADUZIDA:")
    print("http://localhost/duralux/duralux-admin/projects.html")