#!/usr/bin/env python3
"""
Script para corrigir erros de tradução em arquivos JavaScript
Remove traduções incorretas que quebram a sintaxe do código
"""

import os
import re
import glob

def fix_javascript_translations():
    """Corrige traduções problemáticas em arquivos JavaScript"""
    
    # Mapeamento de correções
    fixes = {
        'Painel de Controle': 'Dashboard',
        'DuraluxWorkflowPainel de Controle': 'DuraluxWorkflowDashboard',
        'loadPainel de ControleData': 'loadDashboardData',
        'renderPainel de ControleStats': 'renderDashboardStats',
        'Análises': 'Analytics',
        'Relatórios': 'Reports',
        'Configurações': 'Settings',
        'Nãotificações': 'Notifications',
        'Todoss': 'Todos',
        'Buscar': 'Search',
        'Nãovo': 'New',
        'Excluir': 'Delete',
        'Adicionar Nãovos': 'Add New',
        'Visualizar': 'View',
        'Prdeile': 'Profile',
        'Detalhes': 'Details',
        'Estadoments': 'Statements',
        'Horasheets': 'Timesheets',
        'Horars': 'Hours',
        'Navegarr': 'Browser',
        'Estados': 'Stats',
        'Nãotification': 'Notification',
        'Sair': 'Logout',
        'Editarar': 'Edit',
        'Visualizar': 'View',
        'Criar': 'Create',
        'Adicionar': 'Add'
    }
    
    # Padrões específicos que quebram JavaScript
    js_patterns = [
        (r'class\s+\w*Painel de Controle\w*', lambda m: m.group(0).replace('Painel de Controle', 'Dashboard')),
        (r'loadPainel de ControleData', 'loadDashboardData'),
        (r'renderPainel de ControleStats', 'renderDashboardStats'),
        (r'await this\.loadPainel de ControleData\(\)', 'await this.loadDashboardData()'),
        (r'this\.loadPainel de ControleData\(\)', 'this.loadDashboardData()'),
        (r'this\.renderPainel de ControleStats', 'this.renderDashboardStats')
    ]
    
    base_path = r"C:\Users\ivonm\OneDrive - sga.pucminas.br\Github\duralux\duralux\duralux-admin"
    
    # Encontrar todos os arquivos JavaScript
    js_files = []
    for root, dirs, files in os.walk(base_path):
        for file in files:
            if file.endswith('.js'):
                js_files.append(os.path.join(root, file))
    
    print(f"🔧 Encontrados {len(js_files)} arquivos JavaScript para corrigir...")
    
    fixed_files = 0
    total_fixes = 0
    
    for js_file in js_files:
        print(f"📝 Verificando: {os.path.basename(js_file)}")
        
        try:
            # Ler arquivo
            with open(js_file, 'r', encoding='utf-8') as f:
                content = f.read()
            
            original_content = content
            file_fixes = 0
            
            # Aplicar correções de padrões específicos
            for pattern, replacement in js_patterns:
                if callable(replacement):
                    # Para padrões com função de reposição
                    matches = re.findall(pattern, content)
                    if matches:
                        content = re.sub(pattern, replacement, content)
                        file_fixes += len(matches)
                        print(f"  ✅ Corrigido padrão: {pattern} ({len(matches)} ocorrências)")
                else:
                    # Para substituições simples
                    if pattern in content:
                        content = content.replace(pattern, replacement)
                        file_fixes += 1
                        print(f"  ✅ Corrigido: {pattern} -> {replacement}")
            
            # Aplicar correções gerais (apenas em comentários e strings)
            for wrong, correct in fixes.items():
                # Corrigir apenas em comentários (// e /* */)
                comment_pattern = r'(//.*?)' + re.escape(wrong) + r'(.*?)$'
                content = re.sub(comment_pattern, r'\1' + correct + r'\2', content, flags=re.MULTILINE)
                
                # Corrigir apenas em strings (entre aspas)
                string_pattern = r'(["\'])([^"\']*?)' + re.escape(wrong) + r'([^"\']*?)\1'
                content = re.sub(string_pattern, r'\1\2' + correct + r'\3\1', content)
            
            # Se houve alterações, salvar arquivo
            if content != original_content:
                with open(js_file, 'w', encoding='utf-8') as f:
                    f.write(content)
                fixed_files += 1
                total_fixes += file_fixes
                print(f"  ✅ Arquivo corrigido com {file_fixes} alterações")
            else:
                print(f"  ➡️  Nenhuma correção necessária")
                
        except Exception as e:
            print(f"  ❌ Erro ao processar {js_file}: {str(e)}")
    
    print(f"\n🎉 Correção concluída!")
    print(f"📊 Arquivos corrigidos: {fixed_files}")
    print(f"🔧 Total de correções: {total_fixes}")
    
    return fixed_files > 0

def fix_html_translations():
    """Corrige traduções problemáticas em arquivos HTML que podem afetar JavaScript"""
    
    base_path = r"C:\Users\ivonm\OneDrive - sga.pucminas.br\Github\duralux\duralux\duralux-admin"
    
    # Padrões HTML problemáticos
    html_fixes = {
        'Nãotificações': 'Notificações',
        'Nãovo': 'Novo', 
        'Todoss': 'Todos',
        'Visualizar': 'Ver',
        'Prdeile': 'Perfil',
        'Estadoments': 'Extratos',
        'Horasheets': 'Planilhas de Horas',
        'Navegarr': 'Navegador',
        'Editarar': 'Editar'
    }
    
    # Encontrar arquivos HTML
    html_files = []
    for root, dirs, files in os.walk(base_path):
        for file in files:
            if file.endswith('.html'):
                html_files.append(os.path.join(root, file))
    
    print(f"\n🔧 Encontrados {len(html_files)} arquivos HTML para revisar...")
    
    fixed_html = 0
    
    for html_file in html_files:
        try:
            with open(html_file, 'r', encoding='utf-8') as f:
                content = f.read()
            
            original_content = content
            
            # Aplicar correções
            for wrong, correct in html_fixes.items():
                if wrong in content:
                    content = content.replace(wrong, correct)
            
            # Salvar se houve mudanças
            if content != original_content:
                with open(html_file, 'w', encoding='utf-8') as f:
                    f.write(content)
                fixed_html += 1
                print(f"  ✅ Corrigido: {os.path.basename(html_file)}")
                
        except Exception as e:
            print(f"  ❌ Erro ao processar {html_file}: {str(e)}")
    
    print(f"📊 Arquivos HTML corrigidos: {fixed_html}")
    
    return fixed_html > 0

def main():
    """Função principal"""
    print("🚀 Iniciando correção de problemas de tradução...")
    
    # Corrigir arquivos JavaScript
    js_fixed = fix_javascript_translations()
    
    # Corrigir arquivos HTML
    html_fixed = fix_html_translations()
    
    if js_fixed or html_fixed:
        print("\n✅ Correções aplicadas com sucesso!")
        print("🔄 Os erros de sintaxe JavaScript devem estar resolvidos.")
        return True
    else:
        print("\n➡️  Nenhuma correção foi necessária.")
        return False

if __name__ == "__main__":
    success = main()
    if success:
        print("\n🎯 Execute novamente a verificação de erros para confirmar as correções.")
    else:
        print("\n🔍 Verifique se há outros tipos de problemas.")