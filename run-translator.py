#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Execução direta do tradutor automático - Top 10 páginas
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
    print("🚀 EXECUTANDO TRADUÇÃO AUTOMÁTICA - TOP 10 PÁGINAS")
    print("=" * 60)
    
    translator = DuraluxTranslator()
    
    # Executar tradução limitada (top 10)
    results = translator.translate_all_files(file_limit=10)
    
    if results:
        print("\n✅ TRADUÇÃO CONCLUÍDA COM SUCESSO!")
        print(f"📊 Total de traduções realizadas: {sum(r['translations_count'] for r in results)}")
    else:
        print("\n❌ Erro durante a tradução")
    
    return results

if __name__ == "__main__":
    main()