#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Execução direta do tradutor automático - Páginas específicas mais problemáticas
"""

import sys
import os
import importlib.util

# Carregar o módulo diretamente  
spec = importlib.util.spec_from_file_location("mass_translator", "backend/mass-translator.py")
mass_translator = importlib.util.module_from_spec(spec)
spec.loader.exec_module(mass_translator)

DuraluxTranslator = mass_translator.DuraluxTranslator

def main():
    print("🚀 EXECUTANDO TRADUÇÃO AUTOMÁTICA - PÁGINAS MAIS PROBLEMÁTICAS")
    print("=" * 60)
    
    # Lista das páginas mais problemáticas baseada no relatório anterior
    target_files = [
        "widgets-tables.html",      # 752 ocorrências  
        "widgets-lists.html",       # 608 ocorrências
        "customers-create.html",    # 582 ocorrências
        "customers-view.html",      # 573 ocorrências
        "customers.html",           # 567 ocorrências
        "widgets-statistics.html",  # 388 ocorrências
        "widgets-miscellaneous.html", # 371 ocorrências
        "index.html",              # Dashboard principal
        "projects.html",           # Página de projetos 
        "leads.html"               # Página de leads
    ]
    
    translator = DuraluxTranslator()
    
    # Criar backup
    backup_path = translator.create_backup()
    if not backup_path:
        print("❌ Falha ao criar backup. Abortando tradução.")
        return
    
    print(f"🎯 Traduzindo {len(target_files)} páginas específicas...")
    print("=" * 60)
    
    results = []
    total_translations = 0
    
    for i, filename in enumerate(target_files, 1):
        file_path = os.path.join(translator.html_dir, filename)
        
        if os.path.exists(file_path):
            print(f"📄 [{i:2d}/{len(target_files)}] Traduzindo: {filename}")
            
            result = translator.translate_file(file_path)
            if result:
                results.append(result)
                total_translations += result['translations_count']
                
                if result['translations_count'] > 0:
                    print(f"    ✅ {result['translations_count']} traduções aplicadas")
                else:
                    print(f"    ℹ️  Nenhuma tradução necessária")
        else:
            print(f"📄 [{i:2d}/{len(target_files)}] ⚠️  {filename} - Arquivo não encontrado")
    
    # Salvar log
    import json
    from datetime import datetime
    
    log_data = {
        'timestamp': datetime.now().isoformat(),
        'backup_path': backup_path, 
        'target_files': target_files,
        'total_files': len([r for r in results if r]),
        'total_translations': total_translations,
        'results': results
    }
    
    with open("translation_log_targeted.json", 'w', encoding='utf-8') as f:
        json.dump(log_data, f, ensure_ascii=False, indent=2)
    
    # Resumo final
    print("=" * 60)
    print("🎉 TRADUÇÃO DIRECIONADA CONCLUÍDA!")
    print(f"📊 Arquivos processados: {len([r for r in results if r])}")
    print(f"🔧 Total de traduções: {total_translations}")
    print(f"📁 Backup salvo em: {backup_path}")
    print(f"📋 Log detalhado: translation_log_targeted.json")
    
    # Top arquivos com mais traduções
    successful_results = [r for r in results if r and r['translations_count'] > 0]
    if successful_results:
        sorted_results = sorted(successful_results, key=lambda x: x['translations_count'], reverse=True)
        print(f"\n🏆 ARQUIVOS COM MAIS TRADUÇÕES:")
        for i, result in enumerate(sorted_results[:5], 1):
            print(f"   {i}. {result['file']}: {result['translations_count']} traduções")
    
    return results

if __name__ == "__main__":
    main()