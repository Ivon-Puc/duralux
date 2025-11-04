#!/usr/bin/env python3
"""
DURALUX CRM - Análise Completa do Sistema v5.0
Verificação abrangente de todos os componentes
"""

import os
import re
from pathlib import Path

class DuraluxSystemAnalyzer:
    def __init__(self, base_path):
        self.base_path = Path(base_path)
        self.issues = []
        self.missing_files = []
        self.broken_links = []
        self.missing_functions = []
        
    def analyze_complete_system(self):
        print("🔍 DURALUX CRM - Análise Completa do Sistema")
        print("=" * 60)
        print()
        
        # 1. Verificar estrutura de arquivos
        self.check_file_structure()
        
        # 2. Verificar páginas HTML
        self.check_html_pages()
        
        # 3. Verificar backend PHP
        self.check_backend_files()
        
        # 4. Verificar JavaScript
        self.check_javascript_files()
        
        # 5. Verificar conectividade
        self.check_connectivity()
        
        # 6. Gerar relatório
        self.generate_comprehensive_report()
        
    def check_file_structure(self):
        print("📁 Verificando Estrutura de Arquivos...")
        
        required_files = [
            # Backend core
            'backend/init.php',
            'backend/api/router.php',
            'backend/classes/WorkflowEngine.php',
            'backend/classes/WorkflowController.php',
            
            # Frontend admin
            'duralux-admin/index.html',
            'duralux-admin/leads.html',
            'duralux-admin/leads-create.html',
            'duralux-admin/leads-view.html',
            'duralux-admin/workflow-dashboard.html',
            
            # JavaScript
            'duralux-admin/assets/js/duralux-workflow-dashboard-v5.js',
            
            # CSS
            'duralux-admin/assets/css/style.css',
        ]
        
        for file_path in required_files:
            full_path = self.base_path / file_path
            if not full_path.exists():
                self.missing_files.append(file_path)
                print(f"   ❌ {file_path}")
            else:
                print(f"   ✅ {file_path}")
    
    def check_html_pages(self):
        print("\n🌐 Verificando Páginas HTML...")
        
        html_files = list(self.base_path.glob('duralux-admin/*.html'))
        
        for html_file in html_files:
            try:
                content = html_file.read_text(encoding='utf-8')
                
                # Verificar links CSS/JS
                css_links = re.findall(r'href="([^"]*\.css)"', content)
                js_links = re.findall(r'src="([^"]*\.js)"', content)
                
                for css_link in css_links:
                    if css_link.startswith('http'):
                        continue
                    css_path = html_file.parent / css_link
                    if not css_path.exists():
                        self.broken_links.append(f"{html_file.name} -> {css_link}")
                
                for js_link in js_links:
                    if js_link.startswith('http'):
                        continue
                    js_path = html_file.parent / js_link
                    if not js_path.exists():
                        self.broken_links.append(f"{html_file.name} -> {js_link}")
                
                print(f"   ✅ {html_file.name}")
                
            except Exception as e:
                print(f"   ❌ {html_file.name}: {e}")
                
    def check_backend_files(self):
        print("\n🔧 Verificando Backend PHP...")
        
        php_files = [
            'backend/init.php',
            'backend/api/router.php',
            'backend/classes/WorkflowEngine.php',
            'backend/classes/WorkflowController.php'
        ]
        
        for php_file in php_files:
            file_path = self.base_path / php_file
            if file_path.exists():
                try:
                    content = file_path.read_text(encoding='utf-8')
                    
                    # Verificar sintaxe básica
                    if not content.strip().startswith('<?php'):
                        self.issues.append(f"{php_file}: Não inicia com <?php")
                    
                    # Verificar classes
                    if 'class ' in content:
                        class_matches = re.findall(r'class\s+(\w+)', content)
                        print(f"   ✅ {php_file} (Classes: {', '.join(class_matches)})")
                    else:
                        print(f"   ⚠️ {php_file} (Sem classes)")
                        
                except Exception as e:
                    print(f"   ❌ {php_file}: {e}")
            else:
                print(f"   ❌ {php_file}: Arquivo não encontrado")
    
    def check_javascript_files(self):
        print("\n📜 Verificando JavaScript...")
        
        js_files = list(self.base_path.glob('duralux-admin/assets/js/*.js'))
        
        for js_file in js_files:
            try:
                content = js_file.read_text(encoding='utf-8')
                
                # Verificar classes/funções principais
                if 'class ' in content:
                    class_matches = re.findall(r'class\s+(\w+)', content)
                    print(f"   ✅ {js_file.name} (Classes: {', '.join(class_matches)})")
                elif 'function ' in content:
                    func_matches = re.findall(r'function\s+(\w+)', content)
                    print(f"   ✅ {js_file.name} (Funções: {len(func_matches)})")
                else:
                    print(f"   ⚠️ {js_file.name} (Estrutura indefinida)")
                    
            except Exception as e:
                print(f"   ❌ {js_file.name}: {e}")
    
    def check_connectivity(self):
        print("\n🔗 Verificando Conectividade...")
        
        # Verificar se existe configuração de banco
        config_files = [
            'backend/config/database.php',
            'backend/config/config.php',
            'backend/init.php'
        ]
        
        for config_file in config_files:
            file_path = self.base_path / config_file
            if file_path.exists():
                try:
                    content = file_path.read_text(encoding='utf-8')
                    if 'mysql' in content.lower() or 'pdo' in content.lower():
                        print(f"   ✅ {config_file} (Configuração DB encontrada)")
                    else:
                        print(f"   ⚠️ {config_file} (Sem configuração DB aparente)")
                except Exception as e:
                    print(f"   ❌ {config_file}: {e}")
            else:
                print(f"   ❌ {config_file}: Não encontrado")
    
    def generate_comprehensive_report(self):
        print("\n" + "=" * 60)
        print("📋 RELATÓRIO COMPLETO DE ANÁLISE")
        print("=" * 60)
        
        total_issues = len(self.missing_files) + len(self.broken_links) + len(self.issues)
        
        if self.missing_files:
            print(f"\n❌ ARQUIVOS AUSENTES ({len(self.missing_files)}):")
            for file in self.missing_files:
                print(f"   • {file}")
        
        if self.broken_links:
            print(f"\n🔗 LINKS QUEBRADOS ({len(self.broken_links)}):")
            for link in self.broken_links:
                print(f"   • {link}")
        
        if self.issues:
            print(f"\n⚠️ PROBLEMAS ENCONTRADOS ({len(self.issues)}):")
            for issue in self.issues:
                print(f"   • {issue}")
        
        print(f"\n📊 RESUMO:")
        print(f"   • Total de problemas: {total_issues}")
        
        if total_issues == 0:
            print("   🎉 Sistema está 100% estruturado!")
        elif total_issues <= 5:
            print("   ⚠️ Poucos problemas - fácil de corrigir")
        else:
            print("   🔧 Vários problemas - necessita correção")
        
        print(f"\n🔧 PRÓXIMOS PASSOS PARA 100% FUNCIONAL:")
        print("   1. Corrigir arquivos ausentes")
        print("   2. Fixar links quebrados") 
        print("   3. Configurar banco de dados")
        print("   4. Testar todas as funcionalidades")
        print("   5. Validar formulários e APIs")

if __name__ == "__main__":
    base_path = r"c:\Users\ivonm\OneDrive - sga.pucminas.br\Github\duralux\duralux"
    analyzer = DuraluxSystemAnalyzer(base_path)
    analyzer.analyze_complete_system()