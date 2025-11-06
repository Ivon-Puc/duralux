#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script para corrigir palavras-chave JavaScript que foram traduzidas incorretamente
Fix JavaScript Keywords - Duralux Translation Fixer
Version: 1.0
Author: Sistema de Tradução Duralux
Date: 2025-11-06
"""

import os
import re
import glob
from datetime import datetime

class JavaScriptFixer:
    def __init__(self, base_dir="duralux-admin"):
        self.base_dir = base_dir
        self.fixed_files = 0
        self.total_fixes = 0
        
        # Palavras-chave JavaScript que foram traduzidas incorretamente
        self.js_corrections = {
            # Palavras-chave básicas
            'Novo ': 'new ',
            'Novo(': 'new(',
            'novo ': 'new ',
            'novo(': 'new(',
            'verdadeiro': 'true',
            'falso': 'false',
            'nulo': 'null',
            'indefinido': 'undefined',
            'função ': 'function ',
            'função(': 'function(',
            'retornar ': 'return ',
            'retornar;': 'return;',
            'se (': 'if (',
            'se(': 'if(',
            'senão ': 'else ',
            'senão{': 'else{',
            'para (': 'for (',
            'para(': 'for(',
            'enquanto (': 'while (',
            'enquanto(': 'while(',
            'fazer ': 'do ',
            'quebrar;': 'break;',
            'quebrar ': 'break ',
            'continuar;': 'continue;',
            'continuar ': 'continue ',
            'tentar ': 'try ',
            'tentar{': 'try{',
            'pegar (': 'catch (',
            'pegar(': 'catch(',
            'finalmente ': 'finally ',
            'finalmente{': 'finally{',
            'lançar ': 'throw ',
            'lançar;': 'throw;',
            'var ': 'var ',
            'deixar ': 'let ',
            'const ': 'const ',
            'classe ': 'class ',
            'estender ': 'extends ',
            'super(': 'super(',
            'super.': 'super.',
            'este.': 'this.',
            'deste ': 'this ',
            'em ': 'in ',
            'de ': 'of ',
            'instância de ': 'instanceof ',
            'tipo de ': 'typeof ',
            'excluir ': 'delete ',
            'vazio ': 'void ',
            
            # Objetos globais comuns
            'Data(': 'Date(',
            'Data.': 'Date.',
            'Matriz(': 'Array(',
            'Matriz.': 'Array.',
            'Objeto(': 'Object(',
            'Objeto.': 'Object.',
            'String(': 'String(',
            'String.': 'String.',
            'Número(': 'Number(',
            'Número.': 'Number.',
            'Booleano(': 'Boolean(',
            'Booleano.': 'Boolean.',
            'RegExp(': 'RegExp(',
            'RegExp.': 'RegExp.',
            'Erro(': 'Error(',
            'Erro.': 'Error.',
            'JSON.': 'JSON.',
            'Math.': 'Math.',
            'console.': 'console.',
            'janela.': 'window.',
            'documento.': 'document.',
            'localStorage.': 'localStorage.',
            'sessionStorage.': 'sessionStorage.',
            
            # Métodos comuns
            '.toString()': '.toString()',
            '.valueOf()': '.valueOf()',
            '.length': '.length',
            '.push(': '.push(',
            '.pop()': '.pop()',
            '.shift()': '.shift()',
            '.unshift(': '.unshift(',
            '.splice(': '.splice(',
            '.slice(': '.slice(',
            '.indexOf(': '.indexOf(',
            '.lastIndexOf(': '.lastIndexOf(',
            '.find(': '.find(',
            '.filter(': '.filter(',
            '.map(': '.map(',
            '.reduce(': '.reduce(',
            '.forEach(': '.forEach(',
            '.some(': '.some(',
            '.every(': '.every(',
            '.sort(': '.sort(',
            '.reverse()': '.reverse()',
            '.join(': '.join(',
            '.split(': '.split(',
            '.replace(': '.replace(',
            '.substring(': '.substring(',
            '.substr(': '.substr(',
            '.toLowerCase()': '.toLowerCase()',
            '.toUpperCase()': '.toUpperCase()',
            '.trim()': '.trim()',
            '.match(': '.match(',
            '.search(': '.search(',
            '.test(': '.test(',
            '.exec(': '.exec(',
            
            # Event listeners
            '.addEventListener(': '.addEventListener(',
            '.removeEventListener(': '.removeEventListener(',
            '.preventDefault()': '.preventDefault()',
            '.stopPropagation()': '.stopPropagation()',
            
            # DOM
            '.getElementById(': '.getElementById(',
            '.getElementsByClassName(': '.getElementsByClassName(',
            '.getElementsByTagName(': '.getElementsByTagName(',
            '.querySelector(': '.querySelector(',
            '.querySelectorAll(': '.querySelectorAll(',
            '.createElement(': '.createElement(',
            '.appendChild(': '.appendChild(',
            '.removeChild(': '.removeChild(',
            '.insertBefore(': '.insertBefore(',
            '.replaceChild(': '.replaceChild(',
            '.setAttribute(': '.setAttribute(',
            '.getAttribute(': '.getAttribute(',
            '.removeAttribute(': '.removeAttribute(',
            '.classList.': '.classList.',
            '.style.': '.style.',
            '.innerHTML': '.innerHTML',
            '.textContent': '.textContent',
            '.value': '.value',
            '.checked': '.checked',
            '.disabled': '.disabled',
            '.hidden': '.hidden',
            
            # AJAX/Fetch
            'XMLHttpRequest()': 'XMLHttpRequest()',
            '.open(': '.open(',
            '.send(': '.send(',
            '.setRequestHeader(': '.setRequestHeader(',
            'fetch(': 'fetch(',
            '.then(': '.then(',
            '.catch(': '.catch(',
            '.finally(': '.finally(',
            'Promise(': 'Promise(',
            'Promise.': 'Promise.',
            'async ': 'async ',
            'await ': 'await ',
            
            # Temporizadores
            'setTimeout(': 'setTimeout(',
            'setInterval(': 'setInterval(',
            'clearTimeout(': 'clearTimeout(',
            'clearInterval(': 'clearInterval(',
        }

    def fix_file(self, file_path):
        """Corrige JavaScript em um arquivo específico"""
        try:
            with open(file_path, 'r', encoding='utf-8') as f:
                content = f.read()
            
            original_content = content
            file_fixes = 0
            
            # Corrigir palavras-chave JavaScript
            for wrong_js, correct_js in self.js_corrections.items():
                if wrong_js in content:
                    content = content.replace(wrong_js, correct_js)
                    file_fixes += 1
            
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
        """Corrige JavaScript em todos os arquivos HTML"""
        print("🔧 Iniciando correção de JavaScript traduzido incorretamente...")
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
    fixer = JavaScriptFixer()
    fixer.fix_all_files()

if __name__ == "__main__":
    main()