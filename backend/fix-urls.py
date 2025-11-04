#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
🔧 CORRETOR DE URLs DUPLICADAS - WAMPSERVER
Script para corrigir URLs malformadas após migração
"""

import os
import re
import shutil

def fix_duplicate_urls():
    """Corrige URLs duplicadas nos arquivos HTML"""
    
    print("🔧 CORRIGINDO URLs DUPLICADAS NO WAMPSERVER")
    print("=" * 50)
    
    # Caminhos
    original_path = r"c:\Users\ivonm\OneDrive - sga.pucminas.br\Github\duralux\duralux"
    wamp_path = r"C:\wamp64\www\duralux"
    admin_path = os.path.join(original_path, "duralux-admin")
    wamp_admin_path = os.path.join(wamp_path, "duralux-admin")
    
    # Padrões para correção
    patterns_to_fix = [
        # URLs duplicadas
        (r'http://localhost/duralux/duralux-admin/http://localhost/duralux/duralux-admin/', 
         'http://localhost/duralux/duralux-admin/'),
         
        # APIs duplicadas  
        (r'http://localhost/duralux/http://localhost/duralux/backend/',
         'http://localhost/duralux/backend/'),
         
        # Assets duplicados
        (r'href="http://localhost/duralux/duralux-admin/http://localhost/duralux/duralux-admin/assets/',
         'href="http://localhost/duralux/duralux-admin/assets/'),
         
        (r'src="http://localhost/duralux/duralux-admin/http://localhost/duralux/duralux-admin/assets/',
         'src="http://localhost/duralux/duralux-admin/assets/'),
    ]
    
    # Lista arquivos HTML
    html_files = [f for f in os.listdir(admin_path) if f.endswith('.html')]
    
    fixed_count = 0
    
    for html_file in html_files:
        try:
            original_file = os.path.join(admin_path, html_file)
            wamp_file = os.path.join(wamp_admin_path, html_file)
            
            # Lê arquivo original
            with open(original_file, 'r', encoding='utf-8') as f:
                content = f.read()
            
            # Aplica correções
            modified = False
            for pattern, replacement in patterns_to_fix:
                if pattern in content:
                    content = content.replace(pattern, replacement)
                    modified = True
            
            # Se modificado, salva nos dois locais
            if modified:
                # Salva no original
                with open(original_file, 'w', encoding='utf-8') as f:
                    f.write(content)
                
                # Copia para WAMP
                shutil.copy2(original_file, wamp_file)
                
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
    fix_duplicate_urls()