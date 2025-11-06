#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script para verificar páginas HTML que ainda contêm textos em inglês
Busca por palavras-chave comuns em inglês que podem ter sido esquecidas na tradução
"""

import os
import re
import glob
from collections import defaultdict

def check_english_content():
    """Verifica conteúdo em inglês nas páginas HTML"""
    
    # Palavras comuns em inglês que podem ter sido esquecidas
    english_keywords = [
        'Overview', 'Total', 'Active', 'Completed', 'Pending', 'Save', 'Cancel', 'Edit', 'Delete',
        'Create', 'New', 'Add', 'Remove', 'Update', 'Submit', 'Search', 'Filter', 'Sort',
        'Name', 'Description', 'Status', 'Actions', 'Details', 'Settings', 'Profile',
        'Dashboard', 'Analytics', 'Reports', 'Users', 'Customers', 'Projects', 'Tasks',
        'Revenue', 'Sales', 'Marketing', 'Campaign', 'Email', 'Phone', 'Address',
        'Date', 'Time', 'Yesterday', 'Today', 'Tomorrow', 'Week', 'Month', 'Year',
        'Login', 'Logout', 'Register', 'Password', 'Username', 'Account',
        'Home', 'About', 'Contact', 'Help', 'Support', 'FAQ',
        'All', 'None', 'Select', 'Choose', 'View', 'Show', 'Hide',
        'Loading', 'Please wait', 'Error', 'Success', 'Warning', 'Info',
        'Download', 'Upload', 'Import', 'Export', 'Print', 'Share',
        'Next', 'Previous', 'First', 'Last', 'Page', 'Items per page',
        'Welcome', 'Hello', 'Good morning', 'Good afternoon', 'Good evening'
    ]
    
    # Diretório das páginas HTML
    html_dir = "duralux-admin"
    
    if not os.path.exists(html_dir):
        print(f"❌ Diretório {html_dir} não encontrado!")
        return
    
    # Buscar todos os arquivos HTML
    html_files = glob.glob(os.path.join(html_dir, "*.html"))
    
    if not html_files:
        print(f"❌ Nenhum arquivo HTML encontrado em {html_dir}")
        return
    
    print(f"🔍 Verificando {len(html_files)} arquivos HTML...\n")
    
    results = defaultdict(list)
    
    for html_file in html_files:
        try:
            with open(html_file, 'r', encoding='utf-8') as f:
                content = f.read()
                
            filename = os.path.basename(html_file)
            
            # Verificar cada palavra-chave
            for keyword in english_keywords:
                # Buscar por palavra inteira (não parte de outra palavra)
                pattern = r'\b' + re.escape(keyword) + r'\b'
                matches = re.finditer(pattern, content, re.IGNORECASE)
                
                for match in matches:
                    # Obter linha do match
                    line_num = content[:match.start()].count('\n') + 1
                    
                    # Obter contexto (linha completa)
                    lines = content.split('\n')
                    if line_num <= len(lines):
                        context = lines[line_num - 1].strip()
                        
                        # Filtrar matches em comentários ou meta tags
                        if not (context.startswith('<!--') or 
                               '<meta' in context or 
                               'content=' in context or
                               'placeholder=' in context):
                            results[filename].append({
                                'keyword': keyword,
                                'line': line_num,
                                'context': context[:100] + '...' if len(context) > 100 else context
                            })
        
        except Exception as e:
            print(f"❌ Erro ao processar {html_file}: {e}")
    
    # Mostrar resultados
    if results:
        print("📋 PÁGINAS COM POSSÍVEL CONTEÚDO EM INGLÊS:\n")
        
        for filename, matches in sorted(results.items()):
            print(f"🔸 {filename} ({len(matches)} ocorrências)")
            
            # Agrupar por palavra-chave
            by_keyword = defaultdict(list)
            for match in matches:
                by_keyword[match['keyword']].append(match)
            
            for keyword, keyword_matches in by_keyword.items():
                print(f"   • {keyword}: {len(keyword_matches)} vez(es)")
                # Mostrar apenas os primeiros 2 exemplos para não poluir
                for match in keyword_matches[:2]:
                    print(f"     Linha {match['line']}: {match['context']}")
                if len(keyword_matches) > 2:
                    print(f"     ... e mais {len(keyword_matches) - 2} ocorrência(s)")
            print()
    else:
        print("✅ Nenhum conteúdo em inglês encontrado nas páginas HTML!")
    
    return results

def generate_summary():
    """Gera resumo da análise"""
    results = check_english_content()
    
    if results:
        total_files = len(results)
        total_matches = sum(len(matches) for matches in results.values())
        
        print("=" * 60)
        print("📊 RESUMO DA ANÁLISE:")
        print(f"   • Arquivos com conteúdo em inglês: {total_files}")
        print(f"   • Total de ocorrências encontradas: {total_matches}")
        print("=" * 60)
        
        # Top 5 páginas com mais problemas
        top_pages = sorted(results.items(), key=lambda x: len(x[1]), reverse=True)[:5]
        print("\n🏆 TOP 5 PÁGINAS QUE MAIS PRECISAM DE ATENÇÃO:")
        for i, (filename, matches) in enumerate(top_pages, 1):
            print(f"   {i}. {filename}: {len(matches)} ocorrências")
        
        return top_pages
    else:
        print("✅ PROJETO 100% TRADUZIDO PARA PORTUGUÊS!")
        return []

if __name__ == "__main__":
    print("🌍 VERIFICADOR DE CONTEÚDO EM INGLÊS - PROJETO DURALUX")
    print("=" * 60)
    generate_summary()