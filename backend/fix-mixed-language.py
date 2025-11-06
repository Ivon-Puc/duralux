#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script para corrigir traduções mistas e incompletas
Fix Mixed Language Issues - Duralux Translation Fixer
Version: 1.0
Author: Sistema de Tradução Duralux
Date: 2025-11-06
"""

import os
import re
import glob
from datetime import datetime

class MixedLanguageFixer:
    def __init__(self, base_dir="duralux-admin"):
        self.base_dir = base_dir
        self.fixed_files = 0
        self.total_fixes = 0
        
        # Correções de traduções mistas e incompletas
        self.mixed_corrections = {
            # Problemas específicos identificados
            'Total of Leads': 'Total de Leads',
            'TOTAL OF LEADS': 'TOTAL DE LEADS',
            'Taxa of Conversão': 'Taxa de Conversão',
            'TAXA OF CONVERSÃO': 'TAXA DE CONVERSÃO',
            'Funil of Conversão': 'Funil de Conversão',
            'FUNIL OF CONVERSÃO': 'FUNIL DE CONVERSÃO',
            'Evolução of Leads': 'Evolução de Leads',
            'EVOLUÇÃO OF LEADS': 'EVOLUÇÃO DE LEADS',
            'Período of Análise': 'Período de Análise',
            'PERÍODO OF ANÁLISE': 'PERÍODO DE ANÁLISE',
            'Métricas of Performance': 'Métricas de Performance',
            'MÉTRICAS OF PERFORMANCE': 'MÉTRICAS DE PERFORMANCE',
            'Gráfico of ': 'Gráfico de ',
            'GRÁFICO OF ': 'GRÁFICO DE ',
            'barras of progresso': 'barras de progresso',
            'BARRAS OF PROGRESSO': 'BARRAS DE PROGRESSO',
            'Funcionalidaof of exportação': 'Funcionalidade de exportação',
            'FUNCIONALIDAOF OF EXPORTAÇÃO': 'FUNCIONALIDADE DE EXPORTAÇÃO',
            ' of exportação in': ' de exportação em',
            ' OF EXPORTAÇÃO IN': ' DE EXPORTAÇÃO EM',
            
            # Outros padrões comuns
            ' of ': ' de ',
            ' OF ': ' DE ',
            'of desenvolvimento': 'em desenvolvimento',
            'OF DESENVOLVIMENTO': 'EM DESENVOLVIMENTO',
            'in desenvolvimento': 'em desenvolvimento',
            'IN DESENVOLVIMENTO': 'EM DESENVOLVIMENTO',
            
            # Correções de títulos
            'Analytics Avançado': 'Analytics Avançados',
            'ANALYTICS AVANÇADO': 'ANALYTICS AVANÇADOS',
            'Analíticos Avançado': 'Analytics Avançados',
            'ANALÍTICOS AVANÇADO': 'ANALYTICS AVANÇADOS',
            
            # Correções de CSS classes problemáticas
            '--duralux-Sucesso': '--duralux-success',
            '--duralux-Aviso': '--duralux-warning',
            '.Analíticos-': '.analytics-',
            '.ANALÍTICOS-': '.analytics-',
            
            # Outras correções
            'Status dos Projetos': 'Status dos Projetos',
            'STATUS DOS PROJETOS': 'STATUS DOS PROJETOS',
            'CLIENTES ATIVOS': 'CLIENTES ATIVOS',
            'PROJETOS ATIVOS': 'PROJETOS ATIVOS',
            'RECEITA MENSAL': 'RECEITA MENSAL',
            'TICKET MÉDIO': 'TICKET MÉDIO',
        }

    def fix_file(self, file_path):
        """Corrige traduções mistas em um arquivo específico"""
        try:
            with open(file_path, 'r', encoding='utf-8') as f:
                content = f.read()
            
            original_content = content
            file_fixes = 0
            
            # Corrigir traduções mistas
            for wrong_text, correct_text in self.mixed_corrections.items():
                if wrong_text in content:
                    content = content.replace(wrong_text, correct_text)
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
        """Corrige traduções mistas em todos os arquivos HTML"""
        print("🔧 Iniciando correção de traduções mistas...")
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
    fixer = MixedLanguageFixer()
    fixer.fix_all_files()

if __name__ == "__main__":
    main()