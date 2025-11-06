#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script para aplicar nova identidade visual - Paleta Duralux 2025
Duralux Visual Identity Updater - v1.0
Author: Maria Eduarda Cardoso de Oliveira
Date: 2025-11-06
"""

import os
import re
import glob
import shutil
from datetime import datetime

class VisualIdentityUpdater:
    def __init__(self, base_dir="../duralux-admin"):
        self.base_dir = base_dir
        self.backup_dir = "backups/identity_backup_" + datetime.now().strftime("%Y%m%d_%H%M%S")
        
        # Nova paleta de cores Duralux
        self.color_palette = {
            'primary': '#5550F2',      # Roxo moderno - principal
            'secondary': '#027368',    # Verde escuro - secundário  
            'success': '#04BF9D',      # Verde claro - success/positivo
            'warning': '#F2B33D',      # Amarelo - alertas/destaque
            'light': '#F2F2F2',       # Cinza claro - backgrounds
            'dark': '#2C3E50',        # Escuro para textos
            'info': '#3498DB',        # Azul para informações
            'danger': '#E74C3C'       # Vermelho para erros
        }
        
        # Mapeamento de cores antigas para novas
        self.color_mapping = {
            # Cores antigas do Bootstrap/sistema anterior
            '#667eea': self.color_palette['primary'],
            '#764ba2': self.color_palette['secondary'], 
            '#6c757d': self.color_palette['dark'],
            '#495057': self.color_palette['dark'],
            '#f8f9fa': self.color_palette['light'],
            '#e9ecef': self.color_palette['light'],
            '#28a745': self.color_palette['success'],
            '#17a2b8': self.color_palette['info'],
            '#ffc107': self.color_palette['warning'],
            '#dc3545': self.color_palette['danger'],
            
            # Gradientes antigos
            'linear-gradient(135deg, #667eea 0%, #764ba2 100%)': f'linear-gradient(135deg, {self.color_palette["primary"]} 0%, {self.color_palette["secondary"]} 100%)',
            'linear-gradient(45deg, #667eea, #764ba2)': f'linear-gradient(45deg, {self.color_palette["primary"]}, {self.color_palette["secondary"]})',
        }
        
        # Imagens reais para substituir placeholders
        self.real_images = [
            'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=200&h=200&fit=crop&crop=face',  # Homem profissional
            'https://images.unsplash.com/photo-1494790108755-2616b60b57f6?w=200&h=200&fit=crop&crop=face',  # Mulher profissional 
            'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=200&h=200&fit=crop&crop=face',  # Homem executivo
            'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=200&h=200&fit=crop&crop=face',  # Mulher executiva
            'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=200&h=200&fit=crop&crop=face',  # Homem jovem
            'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=200&h=200&fit=crop&crop=face',  # Mulher jovem
            'https://images.unsplash.com/photo-1560250097-0b93528c311a?w=200&h=200&fit=crop&crop=face',  # Homem maduro
            'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=200&h=200&fit=crop&crop=face',  # Mulher madura
        ]
        
        self.updated_count = 0

    def create_backup(self):
        """Cria backup das páginas atuais"""
        if not os.path.exists(self.backup_dir):
            os.makedirs(self.backup_dir)
        
        html_files = glob.glob(os.path.join(self.base_dir, "*.html"))
        
        print(f"📦 Criando backup da identidade atual: {self.backup_dir}")
        
        for file_path in html_files:
            filename = os.path.basename(file_path)
            backup_path = os.path.join(self.backup_dir, filename)
            shutil.copy2(file_path, backup_path)
        
        print(f"✅ Backup de {len(html_files)} arquivos criado!")

    def update_colors_in_content(self, content):
        """Atualiza cores no conteúdo HTML/CSS"""
        updated_content = content
        
        # Substituir cores específicas
        for old_color, new_color in self.color_mapping.items():
            updated_content = updated_content.replace(old_color, new_color)
        
        # Atualizar variáveis CSS customizadas
        css_variables = f'''
        :root {{
            --primary-color: {self.color_palette['primary']};
            --secondary-color: {self.color_palette['secondary']};
            --success-color: {self.color_palette['success']};
            --warning-color: {self.color_palette['warning']};
            --light-color: {self.color_palette['light']};
            --dark-color: {self.color_palette['dark']};
            --info-color: {self.color_palette['info']};
            --danger-color: {self.color_palette['danger']};
            
            /* Gradientes modernos */
            --primary-gradient: linear-gradient(135deg, {self.color_palette['primary']} 0%, {self.color_palette['secondary']} 100%);
            --success-gradient: linear-gradient(135deg, {self.color_palette['success']} 0%, {self.color_palette['secondary']} 100%);
            --warning-gradient: linear-gradient(135deg, {self.color_palette['warning']} 0%, #F39C12 100%);
        }}
        '''
        
        # Inserir variáveis CSS após <style>
        if '<style>' in updated_content:
            updated_content = updated_content.replace(
                '<style>',
                f'<style>\n        {css_variables}'
            )
        
        # Atualizar estilos específicos com nova identidade
        style_updates = {
            # Sidebar
            'background: linear-gradient(135deg, #667eea 0%, #764ba2 100%)': f'background: var(--primary-gradient)',
            'background-color: #667eea': f'background-color: var(--primary-color)',
            
            # Botões primários
            'background: linear-gradient(135deg, #667eea 0%, #764ba2 100%)': f'background: var(--primary-gradient)',
            'border-color: #667eea': f'border-color: var(--primary-color)',
            
            # Headers de cards
            'background: linear-gradient(135deg, #667eea 0%, #764ba2 100%)': f'background: var(--primary-gradient)',
            
            # Cores de texto
            'color: #667eea': f'color: var(--primary-color)',
            'color: #495057': f'color: var(--dark-color)',
            
            # Estados de hover
            '.btn-primary:hover': f'''
            .btn-primary:hover {{
                background: linear-gradient(135deg, {self.color_palette['secondary']} 0%, {self.color_palette['primary']} 100%);
                transform: translateY(-1px);
                box-shadow: 0 4px 8px rgba({int(self.color_palette['primary'][1:3], 16)}, {int(self.color_palette['primary'][3:5], 16)}, {int(self.color_palette['primary'][5:7], 16)}, 0.3);
            }}''',
        }
        
        for old_style, new_style in style_updates.items():
            updated_content = updated_content.replace(old_style, new_style)
        
        return updated_content

    def replace_placeholder_images(self, content):
        """Substitui imagens placeholder por imagens reais"""
        # Encontrar todas as imagens 200x200 placeholder
        placeholder_pattern = r'<img[^>]*src="[^"]*200x200[^"]*"[^>]*>'
        placeholders = re.findall(placeholder_pattern, content)
        
        updated_content = content
        image_index = 0
        
        for placeholder in placeholders:
            if image_index < len(self.real_images):
                # Extrair atributos existentes
                alt_match = re.search(r'alt="([^"]*)"', placeholder)
                class_match = re.search(r'class="([^"]*)"', placeholder)
                
                alt_text = alt_match.group(1) if alt_match else "Foto do cliente"
                css_class = class_match.group(1) if class_match else "img-fluid rounded"
                
                # Criar nova tag img com imagem real
                new_img = f'<img src="{self.real_images[image_index]}" alt="{alt_text}" class="{css_class}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 50%;">'
                
                # Substituir primeira ocorrência
                updated_content = updated_content.replace(placeholder, new_img, 1)
                image_index = (image_index + 1) % len(self.real_images)
        
        return updated_content

    def fix_broken_elements(self, content):
        """Corrige elementos quebrados identificados"""
        fixes = {
            # Corrigir tabelas quebradas
            '<table class="table table-hover">': '<div class="table-responsive"><table class="table table-hover">',
            '</table>': '</table></div>',
            
            # Corrigir breadcrumbs quebrados
            '<ol class="breadcrumb">': '<nav aria-label="breadcrumb"><ol class="breadcrumb">',
            '</ol>': '</ol></nav>',
            
            # Corrigir botões quebrados
            'class="btn btn-primary"': f'class="btn btn-primary" style="background: var(--primary-gradient); border: none;"',
            'class="btn btn-success"': f'class="btn btn-success" style="background-color: var(--success-color); border: none;"',
            'class="btn btn-warning"': f'class="btn btn-warning" style="background-color: var(--warning-color); border: none;"',
            
            # Corrigir cards
            'class="card-header"': f'class="card-header" style="background: var(--primary-gradient); color: white;"',
            
            # Corrigir alertas
            'class="alert alert-info"': f'class="alert alert-info" style="background-color: var(--info-color); color: white; border: none;"',
            'class="alert alert-success"': f'class="alert alert-success" style="background-color: var(--success-color); color: white; border: none;"',
            'class="alert alert-warning"': f'class="alert alert-warning" style="background-color: var(--warning-color); color: white; border: none;"',
        }
        
        updated_content = content
        for old_element, new_element in fixes.items():
            updated_content = updated_content.replace(old_element, new_element)
        
        return updated_content

    def add_enhanced_css(self, content):
        """Adiciona CSS aprimorado com nova identidade"""
        enhanced_css = '''
        /* === DURALUX IDENTITY 2025 === */
        
        /* Animações suaves */
        * {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* Botões com nova identidade */
        .btn-primary {
            background: var(--primary-gradient) !important;
            border: none !important;
            border-radius: 8px !important;
            font-weight: 600 !important;
            padding: 0.75rem 1.5rem !important;
            box-shadow: 0 2px 4px rgba(85, 80, 242, 0.2) !important;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--primary-color) 100%) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 12px rgba(85, 80, 242, 0.3) !important;
        }
        
        /* Sidebar nova identidade */
        .sidebar {
            background: var(--primary-gradient) !important;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1) !important;
        }
        
        .nav-pills .nav-link.active {
            background: rgba(255, 255, 255, 0.2) !important;
            backdrop-filter: blur(10px) !important;
            border-radius: 8px !important;
        }
        
        .nav-pills .nav-link:hover {
            background: rgba(255, 255, 255, 0.1) !important;
            border-radius: 8px !important;
        }
        
        /* Cards modernos */
        .card {
            border: none !important;
            border-radius: 12px !important;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07) !important;
            overflow: hidden !important;
        }
        
        .card-header {
            background: var(--primary-gradient) !important;
            color: white !important;
            border: none !important;
            padding: 1.25rem !important;
            font-weight: 600 !important;
        }
        
        /* Tabelas aprimoradas */
        .table th {
            background: var(--light-color) !important;
            color: var(--dark-color) !important;
            font-weight: 600 !important;
            border: none !important;
            padding: 1rem !important;
        }
        
        .table td {
            padding: 1rem !important;
            vertical-align: middle !important;
            border-color: #E9ECEF !important;
        }
        
        .table-responsive {
            border-radius: 12px !important;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07) !important;
        }
        
        /* Estatísticas coloridas */
        .stats-card {
            background: white !important;
            border-radius: 12px !important;
            padding: 1.5rem !important;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07) !important;
            border-left: 4px solid var(--primary-color) !important;
        }
        
        .stats-number {
            color: var(--primary-color) !important;
            font-size: 2.5rem !important;
            font-weight: 700 !important;
            line-height: 1 !important;
        }
        
        .stats-label {
            color: var(--dark-color) !important;
            font-size: 0.9rem !important;
            font-weight: 500 !important;
            margin-top: 0.5rem !important;
        }
        
        /* Badges com nova identidade */
        .badge-primary {
            background-color: var(--primary-color) !important;
        }
        
        .badge-success {
            background-color: var(--success-color) !important;
        }
        
        .badge-warning {
            background-color: var(--warning-color) !important;
            color: var(--dark-color) !important;
        }
        
        /* Formulários aprimorados */
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color) !important;
            box-shadow: 0 0 0 0.2rem rgba(85, 80, 242, 0.25) !important;
        }
        
        .form-label {
            color: var(--dark-color) !important;
            font-weight: 600 !important;
            margin-bottom: 0.75rem !important;
        }
        
        /* Breadcrumbs */
        .breadcrumb {
            background: transparent !important;
            padding: 0 !important;
        }
        
        .breadcrumb-item a {
            color: var(--primary-color) !important;
            text-decoration: none !important;
        }
        
        .breadcrumb-item a:hover {
            color: var(--secondary-color) !important;
        }
        
        /* Alertas personalizados */
        .alert {
            border: none !important;
            border-radius: 8px !important;
            font-weight: 500 !important;
        }
        
        .alert-success {
            background-color: var(--success-color) !important;
            color: white !important;
        }
        
        .alert-warning {
            background-color: var(--warning-color) !important;
            color: var(--dark-color) !important;
        }
        
        .alert-info {
            background-color: var(--info-color) !important;
            color: white !important;
        }
        
        /* Responsividade aprimorada */
        @media (max-width: 768px) {
            .sidebar {
                border-radius: 0 0 12px 12px !important;
            }
            
            .stats-number {
                font-size: 2rem !important;
            }
            
            .btn {
                padding: 0.6rem 1.2rem !important;
                font-size: 0.9rem !important;
            }
        }
        
        /* Animações de carregamento */
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .loading {
            animation: pulse 2s infinite;
        }
        
        /* Efeitos hover para cards */
        .card:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1) !important;
        }
        '''
        
        # Inserir CSS aprimorado antes do </style>
        if '</style>' in content:
            content = content.replace('</style>', f'{enhanced_css}\n        </style>')
        
        return content

    def update_file(self, file_path):
        """Atualiza um arquivo HTML com nova identidade"""
        try:
            with open(file_path, 'r', encoding='utf-8') as f:
                content = f.read()
            
            filename = os.path.basename(file_path)
            print(f"🎨 Atualizando identidade: {filename}")
            
            # Aplicar todas as atualizações
            content = self.update_colors_in_content(content)
            content = self.replace_placeholder_images(content)
            content = self.fix_broken_elements(content)
            content = self.add_enhanced_css(content)
            
            # Salvar arquivo atualizado
            with open(file_path, 'w', encoding='utf-8') as f:
                f.write(content)
            
            self.updated_count += 1
            print(f"✅ {filename} - nova identidade aplicada!")
            
            return True
            
        except Exception as e:
            print(f"❌ Erro ao atualizar {filename}: {str(e)}")
            return False

    def update_all_files(self):
        """Atualiza todos os arquivos HTML com nova identidade"""
        print("🎨 Iniciando atualização da identidade visual...")
        print("🎯 Nova paleta Duralux 2025:")
        for name, color in self.color_palette.items():
            print(f"   • {name.upper()}: {color}")
        print("=" * 70)
        
        # Criar backup
        self.create_backup()
        
        # Encontrar arquivos HTML
        html_files = glob.glob(os.path.join(self.base_dir, "*.html"))
        
        if not html_files:
            print("❌ Nenhum arquivo HTML encontrado!")
            return False
        
        print(f"📁 Encontrados {len(html_files)} arquivos para atualizar")
        print("-" * 70)
        
        # Atualizar cada arquivo
        success_count = 0
        for file_path in sorted(html_files):
            if self.update_file(file_path):
                success_count += 1
        
        # Relatório final
        print("=" * 70)
        print(f"🎉 IDENTIDADE VISUAL ATUALIZADA!")
        print(f"   • Total de arquivos: {len(html_files)}")
        print(f"   • Atualizados com sucesso: {success_count}")
        print(f"   • Falharam: {len(html_files) - success_count}")
        print(f"   • Backup salvo em: {self.backup_dir}")
        
        if success_count > 0:
            print("\n✨ MELHORIAS IMPLEMENTADAS:")
            print(f"   🎨 Nova paleta de cores aplicada")
            print(f"   🖼️ Imagens placeholder substituídas por reais")
            print(f"   🔧 Elementos quebrados corrigidos")
            print(f"   💎 CSS aprimorado com animações")
            print(f"   📱 Responsividade otimizada")
            print(f"   🌟 Efeitos visuais modernos")
            
            print(f"\n🌐 Visualize em: duralux-mu.vercel.app")
        
        return success_count > 0

def main():
    """Função principal"""
    updater = VisualIdentityUpdater()
    result = updater.update_all_files()
    
    if result:
        print(f"\n🎊 Identidade visual Duralux 2025 aplicada com sucesso!")
    else:
        print(f"\n💥 Falha na atualização da identidade visual.")

if __name__ == "__main__":
    main()