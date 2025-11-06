#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script para corrigir tags HTML que foram traduzidas incorretamente
Fix HTML Tags - Duralux Translation Fixer
Version: 1.0
Author: Sistema de Tradução Duralux
Date: 2025-11-06
"""

import os
import re
import glob
from datetime import datetime

class HtmlTagFixer:
    def __init__(self, base_dir="duralux-admin"):
        self.base_dir = base_dir
        self.fixed_files = 0
        self.total_fixes = 0
        
        # Tags HTML que foram traduzidas incorretamente
        self.tag_corrections = {
            # Tags principais
            '<Título>': '<title>',
            '</Título>': '</title>',
            '<Cabeça>': '<head>',
            '</Cabeça>': '</head>',
            '<Corpo>': '<body>',
            '</Corpo>': '</body>',
            '<Div>': '<div>',
            '</Div>': '</div>',
            '<Botão>': '<button>',
            '</Botão>': '</button>',
            '<Entrada>': '<input>',
            '</Entrada>': '</input>',
            '<Formulário>': '<form>',
            '</Formulário>': '</form>',
            '<Tabela>': '<table>',
            '</Tabela>': '</table>',
            '<Linha>': '<tr>',
            '</Linha>': '</tr>',
            '<Célula>': '<td>',
            '</Célula>': '</td>',
            '<Cabeçalho>': '<th>',
            '</Cabeçalho>': '</th>',
            '<Lista>': '<ul>',
            '</Lista>': '</ul>',
            '<Item>': '<li>',
            '</Item>': '</li>',
            '<Link>': '<a>',
            '</Link>': '</a>',
            '<Imagem>': '<img>',
            '</Imagem>': '</img>',
            '<Script>': '<script>',
            '</Script>': '</script>',
            '<Estilo>': '<style>',
            '</Estilo>': '</style>',
            '<Meta>': '<meta>',
            '</Meta>': '</meta>',
            '<Seção>': '<section>',
            '</Seção>': '</section>',
            '<Artigo>': '<article>',
            '</Artigo>': '</article>',
            '<Navegação>': '<nav>',
            '</Navegação>': '</nav>',
            '<Rodapé>': '<footer>',
            '</Rodapé>': '</footer>',
            '<Cabeçalho>': '<header>',
            '</Cabeçalho>': '</header>',
            '<Principal>': '<main>',
            '</Principal>': '</main>',
            '<Parágrafo>': '<p>',
            '</Parágrafo>': '</p>',
            '<Span>': '<span>',
            '</Span>': '</span>',
            '<H1>': '<h1>',
            '</H1>': '</h1>',
            '<H2>': '<h2>',
            '</H2>': '</h2>',
            '<H3>': '<h3>',
            '</H3>': '</h3>',
            '<H4>': '<h4>',
            '</H4>': '</h4>',
            '<H5>': '<h5>',
            '</H5>': '</h5>',
            '<H6>': '<h6>',
            '</H6>': '</h6>',
        }
        
        # Atributos HTML que podem ter sido traduzidos
        self.attribute_corrections = {
            # Atributos comuns
            'título=': 'title=',
            'classe=': 'class=',
            'identificação=': 'id=',
            'estilo=': 'style=',
            'nome=': 'name=',
            'valor=': 'value=',
            'tipo=': 'type=',
            'href=': 'href=',
            'src=': 'src=',
            'alt=': 'alt=',
            'largura=': 'width=',
            'altura=': 'height=',
            'dados-': 'data-',
            'aria-': 'aria-',
        }

    def fix_file(self, file_path):
        """Corrige tags HTML em um arquivo específico"""
        try:
            with open(file_path, 'r', encoding='utf-8') as f:
                content = f.read()
            
            original_content = content
            file_fixes = 0
            
            # Corrigir tags HTML
            for wrong_tag, correct_tag in self.tag_corrections.items():
                if wrong_tag in content:
                    content = content.replace(wrong_tag, correct_tag)
                    file_fixes += 1
            
            # Corrigir atributos HTML (mais cuidadoso)
            for wrong_attr, correct_attr in self.attribute_corrections.items():
                # Use regex para corrigir apenas em contexto de atributo HTML
                pattern = rf'\b{re.escape(wrong_attr)}'
                if re.search(pattern, content):
                    content = re.sub(pattern, correct_attr, content)
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
        """Corrige todas as tags HTML em todos os arquivos HTML"""
        print("🔧 Iniciando correção de tags HTML traduzidas incorretamente...")
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
    fixer = HtmlTagFixer()
    fixer.fix_all_files()

if __name__ == "__main__":
    main()