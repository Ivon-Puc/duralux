#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script para corrigir problemas específicos de propriedades JavaScript
Fix JavaScript Property Issues
Version: 1.0
"""

import os
import glob

def fix_js_properties():
    """Corrige propriedades JavaScript problemáticas"""
    
    property_fixes = {
        # Propriedades com hífens (inválidas em JS)
        'E-mail:': 'email:',
        'E-mail :': 'email:',
        'data-nascimento:': 'data_nascimento:',
        'data-criacao:': 'data_criacao:',
        'data-atualizacao:': 'data_atualizacao:',
        'fuso-horario:': 'fuso_horario:',
        'cpf-cnpj:': 'cpf_cnpj:',
        
        # Parâmetros de função problemáticos
        "Tipo = 'Informação'": "type = 'info'",
        "Tipo = 'Erro'": "type = 'error'",
        "Tipo = 'Sucesso'": "type = 'success'",
        "Tipo = 'Aviso'": "type = 'warning'",
        "tipo = 'Informação'": "type = 'info'",
        "tipo = 'Erro'": "type = 'error'", 
        "tipo = 'Sucesso'": "type = 'success'",
        "tipo = 'Aviso'": "type = 'warning'",
    }
    
    html_files = glob.glob("duralux-admin/*.html")
    fixed_files = 0
    total_fixes = 0
    
    print("🔧 Corrigindo propriedades JavaScript problemáticas...")
    print("=" * 60)
    
    for file_path in html_files:
        try:
            with open(file_path, 'r', encoding='utf-8') as f:
                content = f.read()
            
            original_content = content
            file_fixes = 0
            
            # Aplicar correções
            for wrong_prop, correct_prop in property_fixes.items():
                if wrong_prop in content:
                    count = content.count(wrong_prop)
                    content = content.replace(wrong_prop, correct_prop)
                    file_fixes += count
            
            # Se houve mudanças, salvar
            if content != original_content:
                with open(file_path, 'w', encoding='utf-8') as f:
                    f.write(content)
                
                fixed_files += 1
                total_fixes += file_fixes
                print(f"✅ Corrigido: {file_path} ({file_fixes} correções)")
        
        except Exception as e:
            print(f"❌ Erro em {file_path}: {str(e)}")
    
    print("=" * 60)
    print(f"✅ Arquivos corrigidos: {fixed_files}")
    print(f"🔧 Total de correções: {total_fixes}")

if __name__ == "__main__":
    fix_js_properties()