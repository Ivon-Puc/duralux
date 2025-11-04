#!/usr/bin/env python3
"""
Script para atualizar URLs da API em todos os arquivos HTML para WAMPSERVER
"""

import os
import re
from pathlib import Path

def update_api_urls():
    """Atualiza URLs da API em todos os arquivos HTML"""
    
    print("🔧 ATUALIZANDO URLs DA API PARA WAMPSERVER")
    print("=" * 50)
    
    base_path = Path(r"c:\Users\ivonm\OneDrive - sga.pucminas.br\Github\duralux\duralux")
    html_files = list((base_path / "duralux-admin").glob("*.html"))
    
    # Padrões para substituir
    patterns = [
        (r"this\.apiUrl = 'backend/api/api-notifications\.php'", 
         "this.apiUrl = 'http://localhost/duralux/backend/api/api-notifications.php'"),
        
        (r"this\.apiUrl = '\.\./backend/api/api-notifications\.php'", 
         "this.apiUrl = 'http://localhost/duralux/backend/api/api-notifications.php'"),
        
        (r'fetch\(["\']\.\.\/backend\/api\/api-notifications\.php', 
         'fetch("http://localhost/duralux/backend/api/api-notifications.php'),
        
        (r'fetch\(["\']backend\/api\/api-notifications\.php', 
         'fetch("http://localhost/duralux/backend/api/api-notifications.php'),
    ]
    
    updated_count = 0
    
    for html_file in html_files:
        try:
            content = html_file.read_text(encoding='utf-8')
            original_content = content
            
            # Aplicar todas as substituições
            for pattern, replacement in patterns:
                content = re.sub(pattern, replacement, content)
            
            # Se o conteúdo mudou, salvar
            if content != original_content:
                html_file.write_text(content, encoding='utf-8')
                updated_count += 1
                print(f"✅ Atualizado: {html_file.name}")
            
        except Exception as e:
            print(f"❌ Erro ao processar {html_file.name}: {e}")
    
    print("=" * 50)
    print(f"🎉 Atualização concluída!")
    print(f"📊 Arquivos atualizados: {updated_count}/{len(html_files)}")
    
    # Também atualizar no WAMP
    wamp_path = Path(r"C:\wamp64\www\duralux\duralux-admin")
    if wamp_path.exists():
        print(f"\n🔄 Sincronizando com WAMPSERVER...")
        wamp_html_files = list(wamp_path.glob("*.html"))
        wamp_updated = 0
        
        for wamp_file in wamp_html_files:
            try:
                content = wamp_file.read_text(encoding='utf-8')
                original_content = content
                
                # Aplicar todas as substituições
                for pattern, replacement in patterns:
                    content = re.sub(pattern, replacement, content)
                
                # Se o conteúdo mudou, salvar
                if content != original_content:
                    wamp_file.write_text(content, encoding='utf-8')
                    wamp_updated += 1
                
            except Exception as e:
                print(f"❌ Erro ao processar WAMP {wamp_file.name}: {e}")
        
        print(f"✅ Arquivos WAMP atualizados: {wamp_updated}/{len(wamp_html_files)}")
    
    return updated_count > 0

if __name__ == "__main__":
    success = update_api_urls()
    
    print(f"\n🌐 URLs FINAIS PARA TESTE:")
    print("1. http://localhost/duralux/duralux-admin/index.html")
    print("2. http://localhost/duralux/duralux-admin/notification-center.html")
    print("3. http://localhost/duralux/backend/api/api-notifications.php?path=stats")
    
    exit(0 if success else 1)