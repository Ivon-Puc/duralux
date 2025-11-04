#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
🌍 TRADUTOR AVANÇADO PROJECTS.HTML - SEGUNDA PASSADA
Script para traduzir termos específicos restantes
"""

import os
import shutil
import re

def advanced_translate_projects():
    """Segunda passada de tradução mais específica"""
    
    print("🌍 TRADUÇÃO AVANÇADA PROJECTS.HTML - SEGUNDA PASSADA")
    print("=" * 55)
    
    # Caminhos
    original_file = r"c:\Users\ivonm\OneDrive - sga.pucminas.br\Github\duralux\duralux\duralux-admin\projects.html"
    wamp_file = r"C:\wamp64\www\duralux\duralux-admin\projects.html"
    
    # Traduções mais específicas usando regex para contexto
    specific_translations = [
        # Atributos e IDs específicos em contexto
        (r'placeholder="([^"]*)"', lambda m: f'placeholder="{translate_placeholder(m.group(1))}"'),
        
        # Labels específicos em formulários  
        (r'>([A-Z][a-z]+ [A-Z][a-z]+)<', lambda m: f'>{translate_label(m.group(1))}<'),
        
        # Textos em JavaScript/JSON
        (r'"([a-z_]+)":\s*"([A-Za-z\s]+)"', lambda m: f'"{m.group(1)}": "{translate_value(m.group(2))}"'),
    ]
    
    # Traduções diretas adicionais
    direct_translations = {
        # Campos específicos de formulário
        'name="status"': 'name="status"',  # Manter atributo
        'name="priority"': 'name="priority"',
        'name="description"': 'name="description"',
        'name="budget"': 'name="budget"',
        'name="start_date"': 'name="start_date"',
        'name="due_date"': 'name="due_date"',
        'name="end_date"': 'name="end_date"',
        
        # Labels em contexto específico
        '>Status<': '>Status<',
        '>Priority<': '>Prioridade<',
        '>Description<': '>Descrição<',
        '>Budget<': '>Orçamento<', 
        '>Customer<': '>Cliente<',
        '>Progress<': '>Progresso<',
        '>Actions<': '>Ações<',
        
        # Valores de opções
        'value="planning">Planning': 'value="planning">Planejamento',
        'value="in_progress">In Progress': 'value="in_progress">Em Andamento',
        'value="review">Review': 'value="review">Em Revisão',
        'value="completed">Completed': 'value="completed">Concluído',
        'value="on_hold">On Hold': 'value="on_hold">Pausado',
        'value="cancelled">Cancelled': 'value="cancelled">Cancelado',
        
        'value="low">Low': 'value="low">Baixa',
        'value="medium">Medium': 'value="medium">Média',
        'value="high">High': 'value="high">Alta',
        'value="urgent">Urgent': 'value="urgent">Urgente',
        
        # Textos específicos de interface
        'Project Name *': 'Nome do Projeto *',
        'Due Date': 'Data de Entrega',
        'Start Date': 'Data de Início', 
        'End Date': 'Data de Término',
        'Created At': 'Criado em',
        'Updated At': 'Atualizado em',
        
        # Botões e links específicos
        'New Project': 'Novo Projeto',
        'Create Project': 'Criar Projeto',
        'Edit Project': 'Editar Projeto',
        'Delete Project': 'Excluir Projeto',
        'Save Project': 'Salvar Projeto',
        'Project Details': 'Detalhes do Projeto',
        'View Project': 'Visualizar Projeto',
        
        # Mensagens de status
        'Project saved successfully': 'Projeto salvo com sucesso',
        'Project updated successfully': 'Projeto atualizado com sucesso',
        'Project deleted successfully': 'Projeto excluído com sucesso',
        'Error saving project': 'Erro ao salvar projeto',
        'Error loading project': 'Erro ao carregar projeto',
        
        # Filtros e busca
        'Search projects': 'Buscar projetos',
        'Filter by status': 'Filtrar por status',
        'Filter by priority': 'Filtrar por prioridade',
        'All Projects': 'Todos os Projetos',
        'Active Projects': 'Projetos Ativos',
        'Overdue Projects': 'Projetos Atrasados',
        
        # Tabela de projetos
        'Project Name': 'Nome do Projeto',
        'Customer Name': 'Nome do Cliente',
        'Start Date': 'Data de Início',
        'Due Date': 'Prazo de Entrega',
        'Completion': 'Conclusão',
        
        # Modal e formulários
        'Project Form': 'Formulário de Projeto',
        'Project Information': 'Informações do Projeto',
        'Basic Information': 'Informações Básicas',
        'Additional Information': 'Informações Adicionais',
        
        # Validações
        'This field is required': 'Este campo é obrigatório',
        'Please enter a valid date': 'Por favor, insira uma data válida',
        'Please select a customer': 'Por favor, selecione um cliente',
        'Please enter project name': 'Por favor, insira o nome do projeto',
    }
    
    try:
        # Lê o arquivo
        with open(original_file, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Aplica traduções diretas
        modified = False
        for english_text, portuguese_text in direct_translations.items():
            if english_text in content and english_text != portuguese_text:
                content = content.replace(english_text, portuguese_text)
                modified = True
                print(f"✅ Traduzido: {english_text} → {portuguese_text}")
        
        if modified:
            # Salva arquivo
            with open(original_file, 'w', encoding='utf-8') as f:
                f.write(content)
            
            # Copia para WAMP
            shutil.copy2(original_file, wamp_file)
            
            print(f"\n✅ Segunda passada de tradução concluída!")
        else:
            print("ℹ️ Nenhuma tradução adicional necessária")
            
    except Exception as e:
        print(f"❌ Erro na tradução: {e}")

def translate_placeholder(text):
    """Traduz placeholders específicos"""
    placeholders = {
        'Search by name, description...': 'Buscar por nome, descrição...',
        'Enter project name': 'Digite o nome do projeto',
        'Select customer': 'Selecionar cliente',
        'Project description': 'Descrição do projeto',
        'Budget amount': 'Valor do orçamento',
    }
    return placeholders.get(text, text)

def translate_label(text):
    """Traduz labels específicos"""
    labels = {
        'Project Name': 'Nome do Projeto',
        'Customer Name': 'Nome do Cliente', 
        'Start Date': 'Data de Início',
        'Due Date': 'Data de Entrega',
        'End Date': 'Data de Término',
        'Project Status': 'Status do Projeto',
        'Project Priority': 'Prioridade do Projeto',
    }
    return labels.get(text, text)

def translate_value(text):
    """Traduz valores específicos"""
    values = {
        'Planning': 'Planejamento',
        'In Progress': 'Em Andamento',
        'Review': 'Em Revisão',
        'Completed': 'Concluído',
        'On Hold': 'Pausado',
        'Cancelled': 'Cancelado',
        'Low': 'Baixa',
        'Medium': 'Média',
        'High': 'Alta',
        'Urgent': 'Urgente',
    }
    return values.get(text, text)

if __name__ == "__main__":
    advanced_translate_projects()
    
    print("\n🌐 TESTE A PÁGINA COMPLETAMENTE TRADUZIDA:")
    print("http://localhost/duralux/duralux-admin/projects.html")