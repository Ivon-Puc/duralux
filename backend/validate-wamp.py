#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
✅ VALIDADOR FINAL - MIGRAÇÃO WAMPSERVER
Script para validar se a migração foi concluída com sucesso
"""

import os
import requests
import json
from urllib.parse import urljoin

def validate_wamp_migration():
    """Valida se a migração para WAMPSERVER foi bem-sucedida"""
    
    print("✅ VALIDANDO MIGRAÇÃO PARA WAMPSERVER")
    print("=" * 50)
    
    base_url = "http://localhost/duralux/"
    
    # URLs importantes para testar
    test_urls = [
        # Páginas principais
        "duralux-admin/index.html",
        "duralux-admin/notification-center.html",
        "duralux-admin/leads.html",
        "duralux-admin/customers.html", 
        "duralux-admin/projects.html",
        "duralux-admin/reports.html",
        
        # APIs
        "backend/api/api-notifications.php?path=stats",
        "config.php",
    ]
    
    # Assets críticos
    asset_urls = [
        "duralux-admin/assets/css/bootstrap.min.css",
        "duralux-admin/assets/css/theme.min.css",
        "duralux-admin/assets/js/common-init.min.js",
    ]
    
    success_count = 0
    total_tests = len(test_urls) + len(asset_urls)
    
    print("🌐 TESTANDO PÁGINAS PRINCIPAIS:")
    for url_path in test_urls:
        full_url = urljoin(base_url, url_path)
        try:
            response = requests.get(full_url, timeout=5)
            if response.status_code == 200:
                print(f"✅ {url_path} - OK")
                success_count += 1
            else:
                print(f"⚠️ {url_path} - Status: {response.status_code}")
        except Exception as e:
            print(f"❌ {url_path} - Erro: {e}")
    
    print("\n📦 TESTANDO ASSETS:")
    for url_path in asset_urls:
        full_url = urljoin(base_url, url_path)
        try:
            response = requests.head(full_url, timeout=5)
            if response.status_code == 200:
                print(f"✅ {url_path} - OK")
                success_count += 1
            else:
                print(f"⚠️ {url_path} - Status: {response.status_code}")
        except Exception as e:
            print(f"❌ {url_path} - Erro: {e}")
    
    # Verifica estrutura de arquivos
    print("\n📁 VERIFICANDO ESTRUTURA:")
    wamp_path = r"C:\wamp64\www\duralux"
    
    required_dirs = ["duralux-admin", "backend", "docs"]
    dir_check = 0
    
    for dir_name in required_dirs:
        dir_path = os.path.join(wamp_path, dir_name)
        if os.path.exists(dir_path):
            print(f"✅ {dir_name}/ - OK")
            dir_check += 1
        else:
            print(f"❌ {dir_name}/ - MISSING")
    
    # Relatório final
    print("\n" + "=" * 50)
    print(f"📊 RELATÓRIO FINAL:")
    print(f"URLs testadas: {success_count}/{total_tests}")
    print(f"Diretórios: {dir_check}/{len(required_dirs)}")
    
    # Status geral
    if success_count >= total_tests * 0.8 and dir_check == len(required_dirs):
        print("🎉 MIGRAÇÃO BEM-SUCEDIDA!")
        status = "SUCCESS"
    elif success_count >= total_tests * 0.6:
        print("⚠️ MIGRAÇÃO PARCIAL - Alguns problemas encontrados")
        status = "PARTIAL"
    else:
        print("❌ MIGRAÇÃO COM PROBLEMAS - Verificação necessária")
        status = "FAILED"
    
    # URLs de acesso
    print(f"\n🌐 URLS DE ACESSO:")
    print(f"Dashboard: http://localhost/duralux/duralux-admin/index.html")
    print(f"Central de Notificações: http://localhost/duralux/duralux-admin/notification-center.html")
    print(f"Leads: http://localhost/duralux/duralux-admin/leads.html")
    print(f"API: http://localhost/duralux/backend/api/api-notifications.php")
    
    return status

if __name__ == "__main__":
    try:
        status = validate_wamp_migration()
        print(f"\n✅ Status: {status}")
    except Exception as e:
        print(f"❌ Erro na validação: {e}")