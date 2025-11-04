#!/usr/bin/env python3
"""
DURALUX CRM - Script de Tradução PT-BR v1.0
Converte automaticamente valores USD para R$ e traduz termos em inglês para português
"""

import os
import re
import json
from pathlib import Path

class DuraluxTranslator:
    def __init__(self, project_root):
        self.project_root = Path(project_root)
        self.translations = {
            # Termos financeiros
            'USD': '',
            r'\$(\d+(?:,\d{3})*(?:\.\d{2})?)': r'R$ \1',
            r'\$(\d+(?:\.\d{3})*(?:,\d{2})?)': r'R$ \1',
            
            # Status
            'Active Deals': 'Negócios Ativos',
            'Revenue Deals': 'Receita de Vendas', 
            'Deals Created': 'Negócios Criados',
            'Deals Closing': 'Negócios Fechados',
            'Sales Pipeline': 'Funil de Vendas',
            
            # Textos gerais
            'Generate Report': 'Gerar Relatório',
            'Sales': 'Vendas',
            'Revenue': 'Receita',
            'Awaiting': 'Aguardando',
            'Completed': 'Concluído',
            'Rejected': 'Rejeitado',
            'vs last month': 'vs mês anterior',
            'Monthly': 'Mensal',
            'Weekly': 'Semanal',
            'Daily': 'Diário',
            'Total': 'Total',
            'Dashboard': 'Painel de Controle',
            
            # Interface
            'Search': 'Buscar',
            'Filter': 'Filtrar',
            'Export': 'Exportar',
            'Import': 'Importar',
            'Settings': 'Configurações',
            'Profile': 'Perfil',
            'Logout': 'Sair',
            'Login': 'Entrar',
            'Register': 'Registrar',
            'Reset Password': 'Redefinir Senha',
            'Forgot Password': 'Esqueci a Senha',
            
            # Status de negócio
            'New': 'Novo',
            'In Progress': 'Em Andamento',
            'Pending': 'Pendente',
            'Approved': 'Aprovado',
            'Cancelled': 'Cancelado',
            'Finished': 'Finalizado',
        }
        
        # Arquivos a serem processados
        self.file_extensions = ['.html', '.js', '.php', '.css']
        self.exclude_dirs = ['node_modules', '.git', 'vendor', 'assets/vendors']
        
    def should_process_file(self, file_path):
        """Verifica se o arquivo deve ser processado"""
        if not any(file_path.suffix == ext for ext in self.file_extensions):
            return False
            
        # Verificar se está em diretório excluído
        for exclude_dir in self.exclude_dirs:
            if exclude_dir in str(file_path):
                return False
                
        return True
    
    def convert_currency_format(self, text):
        """Converte formato monetário americano para brasileiro"""
        # $1,234.56 -> R$ 1.234,56
        pattern = r'\$(\d{1,3}(?:,\d{3})*(?:\.\d{2})?)'
        
        def replace_currency(match):
            amount = match.group(1)
            # Trocar ponto por vírgula para decimal
            if '.' in amount and len(amount.split('.')[-1]) == 2:
                # É um valor decimal (ex: 1,234.56)
                parts = amount.split('.')
                integer_part = parts[0]
                decimal_part = parts[1]
                return f'R$ {integer_part.replace(",", ".")},{decimal_part}'
            else:
                # É um valor inteiro com separadores de milhares
                return f'R$ {amount.replace(",", ".")}'
        
        return re.sub(pattern, replace_currency, text)
    
    def translate_text(self, text):
        """Aplica traduções ao texto"""
        result = text
        
        # Primeiro converter moedas
        result = self.convert_currency_format(result)
        
        # Depois aplicar traduções de termos
        for english, portuguese in self.translations.items():
            if english.startswith(r'\$'):  # Regex patterns
                result = re.sub(english, portuguese, result)
            else:  # Text replacements
                result = result.replace(english, portuguese)
                
        return result
    
    def process_file(self, file_path):
        """Processa um arquivo individual"""
        try:
            with open(file_path, 'r', encoding='utf-8') as f:
                content = f.read()
                
            original_content = content
            translated_content = self.translate_text(content)
            
            # Só sobrescrever se houve mudanças
            if translated_content != original_content:
                with open(file_path, 'w', encoding='utf-8') as f:
                    f.write(translated_content)
                return True
                
        except Exception as e:
            print(f"❌ Erro ao processar {file_path}: {e}")
            
        return False
    
    def scan_project(self):
        """Escaneia todo o projeto e aplica traduções"""
        processed_files = []
        changed_files = []
        
        print("🔍 Escaneando projeto para traduções...")
        
        for file_path in self.project_root.rglob('*'):
            if file_path.is_file() and self.should_process_file(file_path):
                processed_files.append(str(file_path))
                
                if self.process_file(file_path):
                    changed_files.append(str(file_path))
                    print(f"✅ Traduzido: {file_path.relative_to(self.project_root)}")
        
        return {
            'total_files': len(processed_files),
            'changed_files': len(changed_files),
            'processed_files': processed_files,
            'changed_file_list': changed_files
        }
    
    def generate_report(self, results):
        """Gera relatório das traduções aplicadas"""
        report = {
            'timestamp': '2025-11-04T10:00:00Z',
            'project_root': str(self.project_root),
            'summary': {
                'total_files_scanned': results['total_files'],
                'files_modified': results['changed_files'],
                'success_rate': f"{(results['changed_files'] / results['total_files'] * 100):.1f}%" if results['total_files'] > 0 else "0%"
            },
            'translations_applied': len(self.translations),
            'modified_files': results['changed_file_list']
        }
        
        # Salvar relatório
        report_path = self.project_root / 'translation_report_complete.json'
        with open(report_path, 'w', encoding='utf-8') as f:
            json.dump(report, f, indent=2, ensure_ascii=False)
            
        print(f"\n📊 Relatório salvo em: {report_path}")
        return report

def main():
    # Diretório do projeto
    project_root = r"C:\Users\ivonm\OneDrive - sga.pucminas.br\Github\duralux\duralux"
    
    print("🚀 DURALUX CRM - Tradutor Automático PT-BR v1.0")
    print("=" * 50)
    
    translator = DuraluxTranslator(project_root)
    results = translator.scan_project()
    
    print(f"\n📈 Resultados:")
    print(f"   📁 Arquivos escaneados: {results['total_files']}")
    print(f"   ✏️ Arquivos modificados: {results['changed_files']}")
    
    # Gerar relatório
    report = translator.generate_report(results)
    
    print(f"\n✅ Tradução completa!")
    print(f"🎯 Taxa de sucesso: {report['summary']['success_rate']}")

if __name__ == "__main__":
    main()