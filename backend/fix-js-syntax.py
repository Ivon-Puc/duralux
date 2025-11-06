#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script para corrigir problemas críticos de sintaxe JavaScript
Fix Critical JavaScript Syntax Issues
Version: 1.0
Author: Sistema de Tradução Duralux
Date: 2025-11-06
"""

import os
import re
import glob
from datetime import datetime

class JavaScriptSyntaxFixer:
    def __init__(self, base_dir="duralux-admin"):
        self.base_dir = base_dir
        self.fixed_files = 0
        self.total_fixes = 0
        
        # Correções críticas de sintaxe JavaScript
        self.syntax_fixes = {
            # Problemas de loop for...of
            'for (const notification de ': 'for (const notification of ',
            'for (let notification de ': 'for (let notification of ',
            'for (var notification de ': 'for (var notification of ',
            'for (const item de ': 'for (const item of ',
            'for (let item de ': 'for (let item of ',
            'for (var item de ': 'for (var item of ',
            'for (const element de ': 'for (const element of ',
            'for (let element de ': 'for (let element of ',
            'for (var element de ': 'for (var element of ',
            
            # Problemas de parâmetros de função
            'addLocalNotification(Título,': 'addLocalNotification(title,',
            'addLocalNotification(Título ': 'addLocalNotification(title ',
            'function(Título,': 'function(title,',
            'function(Título ': 'function(title ',
            '(Título,': '(title,',
            '(Título ': '(title ',
            
            # Problemas de nomes de função
            'cloif()': 'close()',
            'cloif(': 'close(',
            '.cloif()': '.close()',
            '.cloif(': '.close(',
            
            # Outros problemas comuns
            'função ': 'function ',
            'função(': 'function(',
            'retornar ': 'return ',
            'se (': 'if (',
            'senão ': 'else ',
            'para (': 'for (',
            'enquanto (': 'while (',
            'tentar {': 'try {',
            'pegar (': 'catch (',
            'finalmente {': 'finally {',
            
            # Variáveis comuns traduzidas incorretamente
            'const resultado = ': 'const result = ',
            'let resultado = ': 'let result = ',
            'var resultado = ': 'var result = ',
            'const dados = ': 'const data = ',
            'let dados = ': 'let data = ',
            'var dados = ': 'var data = ',
            'const erro = ': 'const error = ',
            'let erro = ': 'let error = ',
            'var erro = ': 'var error = ',
            
            # Métodos traduzidos incorretamente
            '.comprimento': '.length',
            '.empurrar(': '.push(',
            '.estourar()': '.pop()',
            '.fatiar(': '.slice(',
            '.juntar(': '.join(',
            '.dividir(': '.split(',
            '.substituir(': '.replace(',
            '.encontrar(': '.find(',
            '.filtrar(': '.filter(',
            '.mapear(': '.map(',
            '.reduzir(': '.reduce(',
            '.paraCada(': '.forEach(',
        }

    def fix_file(self, file_path):
        """Corrige sintaxe JavaScript em um arquivo específico"""
        try:
            with open(file_path, 'r', encoding='utf-8') as f:
                content = f.read()
            
            original_content = content
            file_fixes = 0
            
            # Aplicar todas as correções
            for wrong_syntax, correct_syntax in self.syntax_fixes.items():
                if wrong_syntax in content:
                    count = content.count(wrong_syntax)
                    content = content.replace(wrong_syntax, correct_syntax)
                    file_fixes += count
            
            # Se houve mudanças, salvar o arquivo
            if content != original_content:
                with open(file_path, 'w', encoding='utf-8') as f:
                    f.write(content)
                
                self.fixed_files += 1
                self.total_fixes += file_fixes
                print(f"✅ Corrigido: {file_path} ({file_fixes} correções)")
                return True
            else:
                print(f"⚪ Sem correções: {file_path}")
                return False
                
        except Exception as e:
            print(f"❌ Erro ao processar {file_path}: {str(e)}")
            return False

    def fix_all_files(self):
        """Corrige sintaxe JavaScript em todos os arquivos HTML"""
        print("🔧 Iniciando correção de sintaxe JavaScript crítica...")
        print("=" * 60)
        
        # Encontrar todos os arquivos HTML
        html_files = glob.glob(os.path.join(self.base_dir, "*.html"))
        
        if not html_files:
            print("❌ Nenhum arquivo HTML encontrado!")
            return False
        
        print(f"📁 Encontrados {len(html_files)} arquivos HTML")
        print("-" * 60)
        
        # Processar cada arquivo
        for file_path in sorted(html_files):
            self.fix_file(file_path)
        
        # Relatório final
        print("=" * 60)
        print(f"✅ Correção concluída!")
        print(f"📊 Arquivos corrigidos: {self.fixed_files}/{len(html_files)}")
        print(f"🔧 Total de correções: {self.total_fixes}")
        
        return True

def main():
    """Função principal"""
    fixer = JavaScriptSyntaxFixer()
    fixer.fix_all_files()

if __name__ == "__main__":
    main()