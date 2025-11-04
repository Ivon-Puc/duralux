#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
🌍 TRADUTOR LEADS.HTML - PT-BR
Script para traduzir completamente a página de leads
"""

import os
import shutil

def translate_leads_html():
    """Traduz completamente o arquivo leads.html para PT-BR"""
    
    print("🌍 TRADUZINDO LEADS.HTML PARA PT-BR")
    print("=" * 45)
    
    # Caminhos
    original_file = r"c:\Users\ivonm\OneDrive - sga.pucminas.br\Github\duralux\duralux\duralux-admin\leads.html"
    wamp_file = r"C:\wamp64\www\duralux\duralux-admin\leads.html"
    
    # Dicionário completo de traduções para leads
    translations = {
        # Título e navegação  
        'Duralux || Leads': 'Duralux || Leads',  # Mantém pois "Leads" é termo técnico CRM
        '<li class="breadcrumb-item">Leads</li>': '<li class="breadcrumb-item">Leads</li>',
        
        # Botões principais
        'Create Lead': 'Criar Lead',
        'New Lead': 'Novo Lead',
        'Add Lead': 'Adicionar Lead',
        'Edit Lead': 'Editar Lead',
        'Delete Lead': 'Excluir Lead',
        'Save Lead': 'Salvar Lead',
        'Update Lead': 'Atualizar Lead',
        'View Lead': 'Visualizar Lead',
        'Lead Details': 'Detalhes do Lead',
        
        # Status de leads
        'New Leads': 'Novos Leads',
        'Hot Leads': 'Leads Quentes',
        'Warm Leads': 'Leads Mornos', 
        'Cold Leads': 'Leads Frios',
        'Qualified Leads': 'Leads Qualificados',
        'Converted Leads': 'Leads Convertidos',
        'Lost Leads': 'Leads Perdidos',
        
        # Valores de status
        'value="new">New': 'value="new">Novo',
        'value="contacted">Contacted': 'value="contacted">Contatado',
        'value="qualified">Qualified': 'value="qualified">Qualificado',
        'value="converted">Converted': 'value="converted">Convertido',
        'value="lost">Lost': 'value="lost">Perdido',
        'value="hot">Hot': 'value="hot">Quente',
        'value="warm">Warm': 'value="warm">Morno',
        'value="cold">Cold': 'value="cold">Frio',
        
        # Campos de formulário
        'Lead Name': 'Nome do Lead',
        'First Name': 'Primeiro Nome',
        'Last Name': 'Último Nome',
        'Company Name': 'Nome da Empresa',
        'Email Address': 'Endereço de Email',
        'Phone Number': 'Número de Telefone',
        'Lead Source': 'Origem do Lead',
        'Lead Score': 'Pontuação do Lead',
        'Lead Status': 'Status do Lead',
        'Lead Owner': 'Responsável pelo Lead',
        'Contact Person': 'Pessoa de Contato',
        'Job Title': 'Cargo',
        'Department': 'Departamento',
        'Industry': 'Setor',
        'Annual Revenue': 'Receita Anual',
        'Number of Employees': 'Número de Funcionários',
        'Website': 'Website',
        'Address': 'Endereço',
        'City': 'Cidade',
        'State': 'Estado',
        'Country': 'País',
        'Postal Code': 'CEP',
        'Notes': 'Observações',
        'Description': 'Descrição',
        'Comments': 'Comentários',
        
        # Origens do lead
        'Website': 'Website',
        'Social Media': 'Redes Sociais',
        'Email Campaign': 'Campanha de Email',
        'Cold Call': 'Ligação Fria',
        'Referral': 'Indicação',
        'Advertisement': 'Publicidade',
        'Trade Show': 'Feira Comercial',
        'Webinar': 'Webinar',
        'Content Marketing': 'Marketing de Conteúdo',
        'SEO': 'SEO',
        'PPC': 'PPC',
        'Direct Mail': 'Mala Direta',
        'Partner': 'Parceiro',
        'Other': 'Outro',
        
        # Ações da tabela
        'Edit': 'Editar',
        'View': 'Visualizar', 
        'Delete': 'Excluir',
        'Convert': 'Converter',
        'Assign': 'Atribuir',
        'Contact': 'Contatar',
        'Follow Up': 'Acompanhar',
        'Mark as Lost': 'Marcar como Perdido',
        'Mark as Won': 'Marcar como Ganho',
        
        # Headers da tabela
        'Lead Name': 'Nome do Lead',
        'Company': 'Empresa',
        'Email': 'Email',
        'Phone': 'Telefone',
        'Source': 'Origem',
        'Status': 'Status',
        'Score': 'Pontuação',
        'Owner': 'Responsável',
        'Created': 'Criado',
        'Last Contact': 'Último Contato',
        'Actions': 'Ações',
        
        # Filtros e busca
        'Search leads': 'Buscar leads',
        'Filter by status': 'Filtrar por status',
        'Filter by source': 'Filtrar por origem',
        'Filter by owner': 'Filtrar por responsável',
        'All Leads': 'Todos os Leads',
        'All Sources': 'Todas as Origens',
        'All Status': 'Todos os Status',
        'All Owners': 'Todos os Responsáveis',
        'Date Range': 'Período',
        'From Date': 'Data Inicial',
        'To Date': 'Data Final',
        
        # Estatísticas
        'Total Leads': 'Total de Leads',
        'New Leads': 'Novos Leads',
        'Qualified Leads': 'Leads Qualificados',
        'Converted Leads': 'Leads Convertidos',
        'Conversion Rate': 'Taxa de Conversão',
        'Average Score': 'Pontuação Média',
        
        # Modal e formulários
        'Lead Form': 'Formulário de Lead',
        'Lead Information': 'Informações do Lead',
        'Personal Information': 'Informações Pessoais',
        'Company Information': 'Informações da Empresa',
        'Contact Information': 'Informações de Contato',
        'Additional Information': 'Informações Adicionais',
        'Lead Qualification': 'Qualificação do Lead',
        
        # Botões do modal
        'Save': 'Salvar',
        'Cancel': 'Cancelar',
        'Close': 'Fechar',
        'Update': 'Atualizar',
        'Submit': 'Enviar',
        'Reset': 'Limpar',
        
        # Mensagens
        'Lead saved successfully': 'Lead salvo com sucesso',
        'Lead updated successfully': 'Lead atualizado com sucesso',
        'Lead deleted successfully': 'Lead excluído com sucesso',
        'Lead converted successfully': 'Lead convertido com sucesso',
        'Error saving lead': 'Erro ao salvar lead',
        'Error loading lead': 'Erro ao carregar lead',
        'Error deleting lead': 'Erro ao excluir lead',
        'No leads found': 'Nenhum lead encontrado',
        'Loading leads...': 'Carregando leads...',
        'Processing...': 'Processando...',
        
        # Validações
        'This field is required': 'Este campo é obrigatório',
        'Please enter a valid email': 'Por favor, insira um email válido',
        'Please enter a valid phone': 'Por favor, insira um telefone válido',
        'Please select a source': 'Por favor, selecione uma origem',
        'Please select a status': 'Por favor, selecione um status',
        
        # Placeholders
        'Enter lead name': 'Digite o nome do lead',
        'Enter company name': 'Digite o nome da empresa',
        'Enter email address': 'Digite o endereço de email',
        'Enter phone number': 'Digite o número de telefone',
        'Select lead source': 'Selecione a origem do lead',
        'Select lead status': 'Selecione o status do lead',
        'Select owner': 'Selecione o responsável',
        'Enter notes': 'Digite as observações',
        
        # Paginação
        'Previous': 'Anterior',
        'Next': 'Próximo',
        'First': 'Primeiro',
        'Last': 'Último',
        'Showing': 'Mostrando',
        'of': 'de',
        'entries': 'registros',
        'No data available': 'Nenhum dado disponível',
        
        # Ações em massa
        'Bulk Actions': 'Ações em Massa',
        'Select All': 'Selecionar Todos',
        'Deselect All': 'Desmarcar Todos',
        'Delete Selected': 'Excluir Selecionados',
        'Convert Selected': 'Converter Selecionados',
        'Assign Selected': 'Atribuir Selecionados',
        'Change Status': 'Alterar Status',
        
        # Exportação
        'Export': 'Exportar',
        'Export Leads': 'Exportar Leads',
        'Import': 'Importar',
        'Import Leads': 'Importar Leads',
        
        # Outros
        'Lead Pipeline': 'Pipeline de Leads',
        'Lead Tracking': 'Rastreamento de Leads',
        'Lead Management': 'Gestão de Leads',
        'Lead Generation': 'Geração de Leads',
        'Lead Nurturing': 'Nutrição de Leads',
        'Lead Qualification': 'Qualificação de Leads',
        'Lead Assignment': 'Atribuição de Leads',
        'Lead Follow-up': 'Acompanhamento de Leads',
    }
    
    try:
        # Lê o arquivo original
        with open(original_file, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Aplica todas as traduções
        modified = False
        translated_count = 0
        
        for english_text, portuguese_text in translations.items():
            if english_text in content and english_text != portuguese_text:
                content = content.replace(english_text, portuguese_text)
                modified = True
                translated_count += 1
                print(f"✅ Traduzido: {english_text} → {portuguese_text}")
        
        if modified:
            # Salva no arquivo original
            with open(original_file, 'w', encoding='utf-8') as f:
                f.write(content)
            
            # Copia para WAMP
            shutil.copy2(original_file, wamp_file)
            
            print(f"\n✅ Arquivo leads.html traduzido com sucesso!")
            print(f"📊 Traduções aplicadas: {translated_count}")
            print(f"📁 Atualizado: {original_file}")
            print(f"📁 Sincronizado: {wamp_file}")
        else:
            print("ℹ️ Nenhuma tradução necessária - arquivo já em PT-BR")
            
    except Exception as e:
        print(f"❌ Erro na tradução: {e}")

if __name__ == "__main__":
    translate_leads_html()
    
    print("\n🌐 TESTE A PÁGINA TRADUZIDA:")
    print("http://localhost/duralux/duralux-admin/leads.html")