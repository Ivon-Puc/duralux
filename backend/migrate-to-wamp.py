#!/usr/bin/env python3
"""
Script para atualizar todas as URLs do projeto Duralux para WAMPSERVER
Atualiza de localhost/duralux para o novo caminho do WAMP
"""

import os
import re
from pathlib import Path

def update_urls_to_wamp():
    """Atualiza todas as URLs para o ambiente WAMPSERVER"""
    
    print("🔧 ATUALIZANDO URLs PARA WAMPSERVER")
    print("=" * 50)
    
    # Caminhos
    current_project = Path(r"c:\Users\ivonm\OneDrive - sga.pucminas.br\Github\duralux\duralux")
    wamp_project = Path(r"C:\wamp64\www\duralux")
    
    # URLs antigas para novas
    url_mappings = {
        # URLs do backend
        '../backend/api/api-notifications.php': 'http://localhost/duralux/backend/api/api-notifications.php',
        '../backend/assets/css/style.css': 'http://localhost/duralux/backend/assets/css/style.css',
        'backend/api/api-notifications.php': 'http://localhost/duralux/backend/api/api-notifications.php',
        'backend/assets/css/style.css': 'http://localhost/duralux/backend/assets/css/style.css',
        
        # URLs dos assets
        'assets/': 'http://localhost/duralux/duralux-admin/assets/',
        './assets/': 'http://localhost/duralux/duralux-admin/assets/',
        
        # URLs das páginas
        'index.html': 'http://localhost/duralux/duralux-admin/index.html',
        'notification-center.html': 'http://localhost/duralux/duralux-admin/notification-center.html',
        'proposal-edit.html': 'http://localhost/duralux/duralux-admin/proposal-edit.html',
    }
    
    # Primeiro, garantir que existe o diretório WAMP
    if not wamp_project.exists():
        wamp_project.mkdir(parents=True, exist_ok=True)
        print(f"✅ Criado diretório: {wamp_project}")
    
    # Copiar arquivos essenciais (sem .git para evitar problemas)
    essential_files = []
    
    # Copiar duralux-admin
    admin_source = current_project / "duralux-admin"
    admin_dest = wamp_project / "duralux-admin"
    
    if admin_source.exists():
        copy_directory_contents(admin_source, admin_dest, url_mappings)
        print(f"✅ Copiado duralux-admin")
    
    # Copiar backend
    backend_source = current_project / "backend"
    backend_dest = wamp_project / "backend"
    
    if backend_source.exists():
        copy_directory_contents(backend_source, backend_dest, url_mappings)
        print(f"✅ Copiado backend")
    
    # Copiar docs
    docs_source = current_project / "docs"
    docs_dest = wamp_project / "docs"
    
    if docs_source.exists():
        copy_directory_contents(docs_source, docs_dest, url_mappings)
        print(f"✅ Copiado docs")
    
    # Copiar arquivos da raiz (exceto .git)
    root_files = [f for f in current_project.iterdir() if f.is_file() and not f.name.startswith('.')]
    
    for file_path in root_files:
        dest_path = wamp_project / file_path.name
        try:
            content = file_path.read_text(encoding='utf-8')
            content = update_content_urls(content, url_mappings)
            dest_path.write_text(content, encoding='utf-8')
            print(f"✅ Copiado e atualizado: {file_path.name}")
        except Exception as e:
            print(f"⚠️  Erro ao copiar {file_path.name}: {e}")
    
    print("\n" + "=" * 50)
    print("🎉 MIGRAÇÃO PARA WAMPSERVER CONCLUÍDA!")
    print(f"📁 Projeto disponível em: {wamp_project}")
    print(f"🌐 URL principal: http://localhost/duralux/duralux-admin/index.html")
    print(f"🔔 Notification Center: http://localhost/duralux/duralux-admin/notification-center.html")
    print(f"📊 API: http://localhost/duralux/backend/api/api-notifications.php")
    
    return str(wamp_project)

def copy_directory_contents(source_dir, dest_dir, url_mappings):
    """Copia conteúdo do diretório atualizando URLs"""
    
    if not dest_dir.exists():
        dest_dir.mkdir(parents=True, exist_ok=True)
    
    for item in source_dir.rglob('*'):
        if item.is_file():
            # Calcular caminho relativo
            rel_path = item.relative_to(source_dir)
            dest_file = dest_dir / rel_path
            
            # Criar diretório pai se necessário
            dest_file.parent.mkdir(parents=True, exist_ok=True)
            
            try:
                if item.suffix in ['.html', '.css', '.js', '.php', '.py', '.md', '.json']:
                    # Arquivos de texto - atualizar URLs
                    content = item.read_text(encoding='utf-8')
                    content = update_content_urls(content, url_mappings)
                    dest_file.write_text(content, encoding='utf-8')
                else:
                    # Arquivos binários - copiar diretamente
                    dest_file.write_bytes(item.read_bytes())
                    
            except Exception as e:
                print(f"⚠️  Erro ao processar {rel_path}: {e}")

def update_content_urls(content, url_mappings):
    """Atualiza URLs no conteúdo"""
    
    # Mapeamentos específicos para o ambiente WAMP
    wamp_updates = {
        # JavaScript API calls
        r"this\.apiUrl = '[^']*'": "this.apiUrl = 'http://localhost/duralux/backend/api/api-notifications.php'",
        r'apiUrl: "[^"]*"': 'apiUrl: "http://localhost/duralux/backend/api/api-notifications.php"',
        
        # CSS imports
        r'href="\.\.\/backend\/assets\/css\/style\.css"': 'href="http://localhost/duralux/backend/assets/css/style.css"',
        
        # Relative paths em PHP
        r"__DIR__ \. '/\.\./": r"'C:/wamp64/www/duralux/backend/'",
        r"require_once __DIR__ \. '/\.\./": r"require_once 'C:/wamp64/www/duralux/backend/'",
        
        # URLs de assets
        r'src="assets/': 'src="http://localhost/duralux/duralux-admin/assets/',
        r'href="assets/': 'href="http://localhost/duralux/duralux-admin/assets/',
        r'url\(assets/': 'url(http://localhost/duralux/duralux-admin/assets/',
        
        # Links internos
        r'href="([^"]*\.html)"': r'href="http://localhost/duralux/duralux-admin/\1"',
    }
    
    # Aplicar atualizações
    for pattern, replacement in wamp_updates.items():
        content = re.sub(pattern, replacement, content)
    
    # Atualizações diretas
    for old_url, new_url in url_mappings.items():
        content = content.replace(old_url, new_url)
    
    return content

def create_wamp_config():
    """Cria arquivo de configuração para WAMP"""
    
    config_content = '''<?php
/**
 * Configuração do Duralux CRM para WAMPSERVER
 * Ambiente de desenvolvimento local
 */

// URLs base
define('BASE_URL', 'http://localhost/duralux/');
define('ADMIN_URL', 'http://localhost/duralux/duralux-admin/');
define('API_URL', 'http://localhost/duralux/backend/api/');

// Caminhos físicos
define('BASE_PATH', 'C:/wamp64/www/duralux/');
define('ADMIN_PATH', 'C:/wamp64/www/duralux/duralux-admin/');
define('BACKEND_PATH', 'C:/wamp64/www/duralux/backend/');

// Configuração do banco
define('DB_PATH', BASE_PATH . 'backend/data/');

// Configuração de ambiente
define('ENVIRONMENT', 'development');
define('DEBUG', true);

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Headers para CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

echo "✅ Duralux CRM - Configuração WAMP carregada com sucesso!";
?>'''
    
    config_file = Path('C:/wamp64/www/duralux/config.php')
    config_file.parent.mkdir(parents=True, exist_ok=True)
    config_file.write_text(config_content, encoding='utf-8')
    
    return config_file

if __name__ == "__main__":
    try:
        wamp_path = update_urls_to_wamp()
        config_file = create_wamp_config()
        
        print(f"\n📋 PRÓXIMOS PASSOS:")
        print("1. Abra o WAMP e inicie os serviços")
        print("2. Acesse: http://localhost/duralux/config.php")
        print("3. Teste: http://localhost/duralux/duralux-admin/index.html")
        print("4. Notification Center: http://localhost/duralux/duralux-admin/notification-center.html")
        
    except Exception as e:
        print(f"❌ Erro durante a migração: {e}")
        exit(1)