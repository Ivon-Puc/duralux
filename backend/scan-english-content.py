#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
🔍 SCANNER DE CONTEÚDO EM INGLÊS - DURALUX CRM
Script para identificar páginas que ainda têm conteúdo em inglês
"""

import os
import re
from collections import defaultdict

def scan_html_files_for_english():
    """Escaneia todos os arquivos HTML em busca de conteúdo em inglês"""
    
    print("🔍 ESCANEANDO ARQUIVOS HTML PARA CONTEÚDO EM INGLÊS")
    print("=" * 60)
    
    # Caminho dos arquivos HTML
    admin_path = r"c:\Users\ivonm\OneDrive - sga.pucminas.br\Github\duralux\duralux\duralux-admin"
    
    # Termos comuns em inglês para detectar
    english_patterns = [
        # Títulos e navegação
        r'<title>[^<]*[A-Z][a-z]+ [A-Z][a-z]+[^<]*</title>',
        r'<li class="breadcrumb-item">[A-Z][a-z]+</li>',
        
        # Textos de interface
        r'>(Create|Edit|Delete|View|Save|Cancel|Update|New|Add)\s*([A-Z][a-z]+)?<',
        r'>([A-Z][a-z]+ ){1,3}[A-Z][a-z]+<',
        r'placeholder="[A-Z][a-z]+[^"]*"',
        r'value="[a-z_]+">[A-Z][a-z]+( [A-Z][a-z]+)*</option>',
        
        # Botões e labels
        r'class="[^"]*">[A-Z][a-z]+( [A-Z][a-z]+)*</button>',
        r'class="form-label">[A-Z][a-z]+( [A-Z][a-z]+)*</label>',
        r'aria-label="[A-Z][a-z]+[^"]*"',
        
        # Mensagens comuns
        r'(Loading|Processing|Success|Error|Warning|Info|Confirm|Alert)',
        r'(Total|Active|Completed|Pending|Overdue|All|Filter|Search|Sort)',
        
        # Status e prioridades não traduzidos
        r'>(Planning|Review|Progress|Hold|Started|Finished|Cancelled)<',
        r'>(Low|Medium|High|Urgent|Normal)<',
    ]
    
    # Lista arquivos HTML
    html_files = [f for f in os.listdir(admin_path) if f.endswith('.html')]
    
    results = defaultdict(list)
    total_issues = 0
    
    print(f"📁 Analisando {len(html_files)} arquivos HTML...\n")
    
    for html_file in html_files[:20]:  # Limitando para análise inicial
        try:
            file_path = os.path.join(admin_path, html_file)
            
            with open(file_path, 'r', encoding='utf-8') as f:
                content = f.read()
            
            file_issues = []
            
            # Verifica cada padrão
            for pattern in english_patterns:
                matches = re.finditer(pattern, content, re.IGNORECASE)
                for match in matches:
                    # Filtra falsos positivos conhecidos
                    matched_text = match.group(0)
                    if not is_false_positive(matched_text):
                        file_issues.append({
                            'pattern': pattern[:50] + '...',
                            'match': matched_text[:100] + ('...' if len(matched_text) > 100 else ''),
                            'line': content[:match.start()].count('\n') + 1
                        })
            
            if file_issues:
                results[html_file] = file_issues
                total_issues += len(file_issues)
                print(f"⚠️ {html_file}: {len(file_issues)} possíveis problemas")
                
                # Mostra até 3 exemplos por arquivo
                for issue in file_issues[:3]:
                    print(f"   Linha {issue['line']}: {issue['match']}")
                if len(file_issues) > 3:
                    print(f"   ... e mais {len(file_issues) - 3} problemas")
                print()
            else:
                print(f"✅ {html_file}: OK")
        
        except Exception as e:
            print(f"❌ Erro ao analisar {html_file}: {e}")
    
    # Relatório final
    print("\n" + "=" * 60)
    print(f"📊 RELATÓRIO DE ANÁLISE:")
    print(f"Arquivos analisados: {len(html_files)}")
    print(f"Arquivos com problemas: {len(results)}")
    print(f"Total de problemas encontrados: {total_issues}")
    
    # Lista os arquivos mais problemáticos
    if results:
        print(f"\n🎯 ARQUIVOS QUE PRECISAM DE TRADUÇÃO:")
        sorted_files = sorted(results.items(), key=lambda x: len(x[1]), reverse=True)
        for filename, issues in sorted_files[:10]:
            print(f"   • {filename}: {len(issues)} problemas")
    else:
        print(f"\n🎉 TODOS OS ARQUIVOS PARECEM ESTAR EM PT-BR!")
    
    return results

def is_false_positive(text):
    """Filtra falsos positivos conhecidos"""
    false_positives = [
        'CSS', 'HTML', 'JS', 'PHP', 'HTTP', 'URL', 'API', 'JSON', 'XML',
        'PDF', 'CSV', 'Excel', 'ZIP', 'PNG', 'JPG', 'SVG', 'ICO',
        'UTF-8', 'ISO', 'GMT', 'UTC', 'AJAX', 'REST', 'CORS',
        'Bootstrap', 'jQuery', 'Feather', 'Font Awesome',
        'GitHub', 'Google', 'Microsoft', 'Apple', 'Facebook',
        'localhost', 'duralux', 'wamp', 'assets', 'vendors'
    ]
    
    # Remove tags HTML para análise
    clean_text = re.sub(r'<[^>]+>', '', text).strip()
    
    # Verifica se é um falso positivo
    for fp in false_positives:
        if fp.lower() in clean_text.lower():
            return True
    
    # Verifica se é apenas código/atributos
    if len(clean_text) < 3 or clean_text.isdigit():
        return True
    
    return False

def suggest_priority_files():
    """Sugere quais arquivos traduzir primeiro"""
    
    priority_files = [
        'index.html',           # Dashboard principal
        'leads.html',           # Gestão de leads
        'customers.html',       # Gestão de clientes
        'projects.html',        # Gestão de projetos (já traduzido)
        'reports.html',         # Relatórios
        'settings-general.html', # Configurações
        'notification-center.html', # Central de notificações
    ]
    
    print(f"\n📋 ARQUIVOS PRIORITÁRIOS PARA TRADUÇÃO:")
    for i, filename in enumerate(priority_files, 1):
        print(f"   {i}. {filename}")

if __name__ == "__main__":
    results = scan_html_files_for_english()
    suggest_priority_files()