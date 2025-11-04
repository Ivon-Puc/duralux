#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
🔧 CORRETOR DIRETO DE URLs - WAMPSERVER
Script para corrigir URLs duplicadas diretamente no WAMP
"""

import os
import re

def fix_wamp_urls():
    """Corrige URLs duplicadas diretamente nos arquivos WAMP"""
    
    print("🔧 CORRIGINDO URLs DUPLICADAS NO WAMPSERVER")
    print("=" * 50)
    
    # Caminho direto do WAMP
    wamp_admin_path = r"C:\wamp64\www\duralux\duralux-admin"
    
    # Padrões para correção (usando regex para maior precisão)
    patterns_to_fix = [
        # URLs duplicadas - padrão principal
        (r'http://localhost/duralux/duralux-admin/http://localhost/duralux/duralux-admin/', 
         'http://localhost/duralux/duralux-admin/'),
         
        # APIs duplicadas  
        (r'http://localhost/duralux/http://localhost/duralux/backend/',
         'http://localhost/duralux/backend/'),
    ]
    
    # Lista arquivos HTML no WAMP
    if not os.path.exists(wamp_admin_path):
        print(f"❌ Pasta não encontrada: {wamp_admin_path}")
        return
        
    html_files = [f for f in os.listdir(wamp_admin_path) if f.endswith('.html')]
    
    fixed_count = 0
    
    for html_file in html_files:
        try:
            file_path = os.path.join(wamp_admin_path, html_file)
            
            # Lê arquivo
            with open(file_path, 'r', encoding='utf-8') as f:
                content = f.read()
            
            original_content = content
            
            # Aplica correções
            for pattern, replacement in patterns_to_fix:
                content = content.replace(pattern, replacement)
            
            # Se modificado, salva
            if content != original_content:
                with open(file_path, 'w', encoding='utf-8') as f:
                    f.write(content)
                
                print(f"✅ Corrigido: {html_file}")
                fixed_count += 1
            else:
                print(f"⏭️ OK: {html_file}")
                
        except Exception as e:
            print(f"❌ Erro em {html_file}: {e}")
    
    print("=" * 50)
    print(f"🎉 Correção concluída!")
    print(f"📊 Arquivos corrigidos: {fixed_count}/{len(html_files)}")
    
    # URLs para teste
    print("\n🌐 TESTE AS URLs CORRIGIDAS:")
    print("1. http://localhost/duralux/duralux-admin/index.html")
    print("2. http://localhost/duralux/duralux-admin/notification-center.html") 
    print("3. http://localhost/duralux/backend/api/api-notifications.php?path=stats")

if __name__ == "__main__":
    fix_wamp_urls()