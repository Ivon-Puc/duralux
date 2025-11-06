#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script para analisar e modernizar páginas do formato antigo para o novo layout responsivo
Duralux Layout Modernizer - v1.0
Author: Maria Eduarda Cardoso de Oliveira
Date: 2025-11-06
"""

import os
import re
import glob
from datetime import datetime
import json

class LayoutAnalyzer:
    def __init__(self, base_dir="../duralux-admin"):
        self.base_dir = base_dir
        self.analysis_results = []
        
        # Padrões do layout antigo (indicadores de que precisa modernização)
        self.old_patterns = {
            'bootstrap_old': r'bootstrap@[34]\.',  # Bootstrap 3 ou 4
            'jquery_old': r'jquery-[12]\.',  # jQuery 1 ou 2
            'no_responsive_meta': r'(?!.*viewport.*width=device-width)',
            'fixed_width_layout': r'width:\s*\d+px(?!\s*max-width)',
            'table_layout': r'<table[^>]*class="[^"]*layout',
            'inline_styles': r'style="[^"]*(?:width|height):\s*\d+px',
            'old_css_classes': r'class="[^"]*(?:col-xs|col-sm-\d+\s|pull-left|pull-right)',
            'no_flexbox': r'(?!.*display:\s*flex)',
            'old_grid': r'class="[^"]*(?:span\d+|grid-\d+)',
            'deprecated_tags': r'<(?:center|font|marquee|blink)',
        }
        
        # Padrões do layout moderno (indicadores de que já está modernizado)
        self.modern_patterns = {
            'bootstrap5': r'bootstrap@5\.',
            'responsive_meta': r'<meta[^>]*viewport[^>]*width=device-width',
            'css_grid': r'(?:display:\s*grid|grid-template)',
            'flexbox': r'display:\s*(?:flex|-webkit-flex)',
            'responsive_classes': r'class="[^"]*(?:col-|row|container-fluid|d-flex)',
            'media_queries': r'@media\s*\([^)]*(?:max-width|min-width)',
            'rem_units': r'(?:font-size|margin|padding):\s*[\d.]+rem',
            'css_variables': r'var\(--[^)]+\)',
        }
        
        # Estrutura do novo layout responsivo
        self.modern_structure = {
            'doctype': '<!DOCTYPE html>',
            'html_lang': '<html lang="pt-BR">',
            'meta_charset': '<meta charset="utf-8" />',
            'meta_viewport': '<meta name="viewport" content="width=device-width, initial-scale=1" />',
            'bootstrap_css': 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
            'bootstrap_js': 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
            'responsive_container': 'container-fluid',
            'responsive_row': 'row g-3',
            'responsive_cols': ['col-12', 'col-md-6', 'col-lg-4', 'col-xl-3']
        }

    def analyze_file(self, file_path):
        """Analisa um arquivo HTML para determinar se precisa modernização"""
        try:
            with open(file_path, 'r', encoding='utf-8') as f:
                content = f.read()
            
            filename = os.path.basename(file_path)
            analysis = {
                'file': filename,
                'path': file_path,
                'size': len(content),
                'needs_modernization': False,
                'modernization_score': 0,
                'old_patterns_found': [],
                'modern_patterns_found': [],
                'issues': [],
                'recommendations': []
            }
            
            # Verificar padrões antigos
            old_score = 0
            for pattern_name, pattern in self.old_patterns.items():
                matches = re.findall(pattern, content, re.IGNORECASE)
                if matches:
                    old_score += len(matches)
                    analysis['old_patterns_found'].append({
                        'pattern': pattern_name,
                        'count': len(matches),
                        'examples': matches[:3]  # Primeiros 3 exemplos
                    })
            
            # Verificar padrões modernos
            modern_score = 0
            for pattern_name, pattern in self.modern_patterns.items():
                matches = re.findall(pattern, content, re.IGNORECASE)
                if matches:
                    modern_score += len(matches)
                    analysis['modern_patterns_found'].append({
                        'pattern': pattern_name,
                        'count': len(matches)
                    })
            
            # Calcular score de modernização (0-100, onde 0 = muito antigo, 100 = muito moderno)
            total_patterns = old_score + modern_score
            if total_patterns > 0:
                analysis['modernization_score'] = int((modern_score / total_patterns) * 100)
            else:
                analysis['modernization_score'] = 50  # Neutro se não encontrar padrões
            
            # Determinar se precisa modernização
            analysis['needs_modernization'] = analysis['modernization_score'] < 60
            
            # Análises específicas
            self._analyze_responsiveness(content, analysis)
            self._analyze_accessibility(content, analysis)
            self._analyze_performance(content, analysis)
            self._generate_recommendations(analysis)
            
            return analysis
            
        except Exception as e:
            return {
                'file': os.path.basename(file_path),
                'path': file_path,
                'error': str(e),
                'needs_modernization': True,
                'modernization_score': 0
            }

    def _analyze_responsiveness(self, content, analysis):
        """Analisa responsividade específica"""
        issues = []
        
        # Verificar viewport meta tag
        if not re.search(r'<meta[^>]*viewport', content, re.IGNORECASE):
            issues.append("Falta meta viewport tag para responsividade")
        
        # Verificar larguras fixas
        fixed_widths = re.findall(r'width:\s*(\d+)px', content)
        if len(fixed_widths) > 5:
            issues.append(f"Muitas larguras fixas encontradas ({len(fixed_widths)})")
        
        # Verificar media queries
        media_queries = re.findall(r'@media[^{]*{', content)
        if len(media_queries) < 2:
            issues.append("Poucos ou nenhum media query para diferentes telas")
        
        # Verificar Bootstrap responsivo
        responsive_classes = re.findall(r'col-(?:xs|sm|md|lg|xl)-\d+', content)
        if len(responsive_classes) < 3:
            issues.append("Poucas classes responsivas do Bootstrap encontradas")
        
        analysis['responsiveness_issues'] = issues

    def _analyze_accessibility(self, content, analysis):
        """Analisa acessibilidade"""
        issues = []
        
        # Verificar alt em imagens
        img_tags = re.findall(r'<img[^>]*>', content)
        img_without_alt = [img for img in img_tags if 'alt=' not in img]
        if img_without_alt:
            issues.append(f"{len(img_without_alt)} imagens sem atributo alt")
        
        # Verificar labels em inputs
        input_tags = re.findall(r'<input[^>]*>', content)
        inputs_without_labels = len(input_tags) - len(re.findall(r'<label[^>]*for=', content))
        if inputs_without_labels > 0:
            issues.append(f"{inputs_without_labels} inputs podem estar sem labels")
        
        # Verificar headings hierarchy
        headings = re.findall(r'<h([1-6])', content)
        if headings and headings[0] != '1':
            issues.append("Hierarquia de headings pode estar incorreta (não começa com h1)")
        
        analysis['accessibility_issues'] = issues

    def _analyze_performance(self, content, analysis):
        """Analisa performance"""
        issues = []
        
        # Verificar scripts inline
        inline_scripts = re.findall(r'<script[^>]*>(?![\s]*</script>)', content)
        if len(inline_scripts) > 5:
            issues.append(f"Muitos scripts inline ({len(inline_scripts)}) - considere arquivos externos")
        
        # Verificar CSS inline
        inline_styles = re.findall(r'style="[^"]*(?:background-image|background)', content)
        if len(inline_styles) > 3:
            issues.append(f"Muitos estilos inline com imagens ({len(inline_styles)})")
        
        # Verificar tamanho do arquivo
        if len(content) > 100000:  # 100KB
            issues.append(f"Arquivo muito grande ({len(content)} bytes) - considere dividir")
        
        analysis['performance_issues'] = issues

    def _generate_recommendations(self, analysis):
        """Gera recomendações baseadas na análise"""
        recommendations = []
        
        if analysis['modernization_score'] < 30:
            recommendations.append("🔴 CRÍTICO: Requer modernização completa do layout")
        elif analysis['modernization_score'] < 60:
            recommendations.append("🟡 MODERADO: Precisa de algumas modernizações")
        else:
            recommendations.append("🟢 BOM: Layout já está relativamente moderno")
        
        # Recomendações específicas baseadas nos padrões encontrados
        old_patterns = [p['pattern'] for p in analysis['old_patterns_found']]
        
        if 'bootstrap_old' in old_patterns:
            recommendations.append("Atualizar Bootstrap para versão 5.x")
        
        if 'no_responsive_meta' in old_patterns:
            recommendations.append("Adicionar meta viewport tag")
        
        if 'fixed_width_layout' in old_patterns:
            recommendations.append("Substituir larguras fixas por responsivas")
        
        if 'table_layout' in old_patterns:
            recommendations.append("Migrar de layout baseado em tabelas para CSS Grid/Flexbox")
        
        if analysis.get('responsiveness_issues'):
            recommendations.append("Implementar design responsivo completo")
        
        if analysis.get('accessibility_issues'):
            recommendations.append("Melhorar acessibilidade (alt, labels, headings)")
        
        if analysis.get('performance_issues'):
            recommendations.append("Otimizar performance (reduzir inline styles/scripts)")
        
        analysis['recommendations'] = recommendations

    def analyze_all_pages(self):
        """Analisa todas as páginas HTML"""
        print("🔍 Analisando estrutura e modernização de páginas HTML...")
        print("=" * 70)
        
        html_files = glob.glob(os.path.join(self.base_dir, "*.html"))
        
        if not html_files:
            print("❌ Nenhum arquivo HTML encontrado!")
            return False
        
        print(f"📁 Encontrados {len(html_files)} arquivos HTML")
        print("-" * 70)
        
        results = []
        needs_modernization = []
        
        for file_path in sorted(html_files):
            analysis = self.analyze_file(file_path)
            results.append(analysis)
            
            # Status visual
            if 'error' in analysis:
                status = "❌ ERRO"
                score = "N/A"
            else:
                score = f"{analysis['modernization_score']}%"
                if analysis['modernization_score'] >= 70:
                    status = "🟢 MODERNO"
                elif analysis['modernization_score'] >= 40:
                    status = "🟡 PARCIAL"
                else:
                    status = "🔴 ANTIGO"
                    needs_modernization.append(analysis)
            
            print(f"{status:<12} {analysis['file']:<35} Score: {score}")
        
        # Salvar análise detalhada
        self._save_analysis_report(results)
        
        # Relatório final
        print("=" * 70)
        print(f"📊 RESUMO DA ANÁLISE:")
        print(f"   • Total de páginas: {len(results)}")
        print(f"   • Precisam modernização: {len(needs_modernization)}")
        print(f"   • Já modernizadas: {len(results) - len(needs_modernization)}")
        
        if needs_modernization:
            print(f"\n🔴 PÁGINAS QUE PRECISAM MODERNIZAÇÃO:")
            for page in sorted(needs_modernization, key=lambda x: x['modernization_score']):
                print(f"   • {page['file']:<35} Score: {page['modernization_score']}%")
                for rec in page['recommendations'][:2]:  # Primeiras 2 recomendações
                    print(f"     - {rec}")
        
        return results

    def _save_analysis_report(self, results):
        """Salva relatório detalhado da análise"""
        report = {
            'timestamp': datetime.now().isoformat(),
            'total_pages': len(results),
            'pages_needing_modernization': len([r for r in results if r.get('needs_modernization', False)]),
            'analysis': results
        }
        
        report_file = 'layout_analysis_report.json'
        with open(report_file, 'w', encoding='utf-8') as f:
            json.dump(report, f, indent=2, ensure_ascii=False)
        
        print(f"\n📄 Relatório detalhado salvo em: {report_file}")

def main():
    """Função principal"""
    analyzer = LayoutAnalyzer()
    results = analyzer.analyze_all_pages()
    
    if results:
        print(f"\n✅ Análise concluída! Use o relatório para planejar as modernizações.")

if __name__ == "__main__":
    main()