#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script para remover todos os gradientes do projeto Duralux
Remove Gradients - v1.0
Author: Maria Eduarda Cardoso de Oliveira
Date: 2025-11-06
"""

import os
import re
import glob
from datetime import datetime

class GradientRemover:
    def __init__(self, base_dir="../duralux-admin"):
        self.base_dir = base_dir
        self.backup_dir = "backups/no_gradients_backup_" + datetime.now().strftime("%Y%m%d_%H%M%S")
        self.processed_count = 0
        
        # Paleta de cores sólidas para substituir gradientes
        self.solid_colors = {
            'primary': '#5550F2',
            'secondary': '#027368', 
            'success': '#04BF9D',
            'warning': '#F2B33D',
            'light': '#F2F2F2',
            'dark': '#2C3E50',
            'info': '#3498DB',
            'danger': '#E74C3C'
        }
        
        # Mapeamento de gradientes para cores sólidas
        self.gradient_to_solid = {
            # Gradientes principais para cor primária
            'linear-gradient(135deg, #5550F2 0%, #027368 100%)': self.solid_colors['primary'],
            'linear-gradient(45deg, #5550F2, #027368)': self.solid_colors['primary'],
            'linear-gradient(135deg, #667eea 0%, #764ba2 100%)': self.solid_colors['primary'],
            '--primary-gradient': self.solid_colors['primary'],
            'var(--primary-gradient)': self.solid_colors['primary'],
            
            # Gradientes de sucesso para cor success
            'linear-gradient(135deg, #04BF9D 0%, #027368 100%)': self.solid_colors['success'],
            '--success-gradient': self.solid_colors['success'],
            'var(--success-gradient)': self.solid_colors['success'],
            
            # Gradientes de warning para cor warning
            'linear-gradient(135deg, #F2B33D 0%, #F39C12 100%)': self.solid_colors['warning'],
            '--warning-gradient': self.solid_colors['warning'],
            'var(--warning-gradient)': self.solid_colors['warning'],
        }

    def create_backup(self):
        """Cria backup antes de remover gradientes"""
        if not os.path.exists(self.backup_dir):
            os.makedirs(self.backup_dir)
        
        html_files = glob.glob(os.path.join(self.base_dir, "*.html"))
        
        print(f"📦 Criando backup antes de remover gradientes: {self.backup_dir}")
        
        for file_path in html_files:
            filename = os.path.basename(file_path)
            backup_path = os.path.join(self.backup_dir, filename)
            import shutil
            shutil.copy2(file_path, backup_path)
        
        print(f"✅ Backup de {len(html_files)} arquivos criado!")

    def remove_gradients_from_content(self, content):
        """Remove todos os gradientes do conteúdo"""
        updated_content = content
        gradients_found = 0
        
        # 1. Remover definições de variáveis CSS de gradiente
        css_gradient_vars = [
            r'--primary-gradient:\s*linear-gradient[^;]+;',
            r'--success-gradient:\s*linear-gradient[^;]+;',
            r'--warning-gradient:\s*linear-gradient[^;]+;',
        ]
        
        for pattern in css_gradient_vars:
            matches = re.findall(pattern, updated_content, re.IGNORECASE)
            if matches:
                gradients_found += len(matches)
                updated_content = re.sub(pattern, '', updated_content, flags=re.IGNORECASE)
        
        # 2. Substituir uso de variáveis de gradiente por cores sólidas
        for gradient_var, solid_color in self.gradient_to_solid.items():
            if gradient_var in updated_content:
                gradients_found += updated_content.count(gradient_var)
                updated_content = updated_content.replace(gradient_var, solid_color)
        
        # 3. Remover gradientes inline diretamente
        gradient_patterns = [
            r'background:\s*linear-gradient\([^)]+\)[^;]*;',
            r'background-image:\s*linear-gradient\([^)]+\)[^;]*;',
            r'background:\s*radial-gradient\([^)]+\)[^;]*;',
            r'background-image:\s*radial-gradient\([^)]+\)[^;]*;',
        ]
        
        for pattern in gradient_patterns:
            matches = re.findall(pattern, updated_content, re.IGNORECASE)
            if matches:
                gradients_found += len(matches)
                # Substituir por cor primária sólida
                updated_content = re.sub(
                    pattern, 
                    f'background-color: {self.solid_colors["primary"]};',
                    updated_content, 
                    flags=re.IGNORECASE
                )
        
        # 4. Remover gradientes em atributos style
        style_gradient_patterns = [
            r'style="[^"]*background:\s*linear-gradient\([^)]+\)[^"]*"',
            r'style="[^"]*background-image:\s*linear-gradient\([^)]+\)[^"]*"',
        ]
        
        for pattern in style_gradient_patterns:
            def replace_gradient_in_style(match):
                style_attr = match.group(0)
                # Remover gradientes e substituir por cor sólida
                new_style = re.sub(
                    r'background(?:-image)?:\s*linear-gradient\([^)]+\)\s*[^;]*;?',
                    f'background-color: {self.solid_colors["primary"]};',
                    style_attr
                )
                return new_style
            
            matches = re.findall(pattern, updated_content, re.IGNORECASE)
            if matches:
                gradients_found += len(matches)
                updated_content = re.sub(pattern, replace_gradient_in_style, updated_content, flags=re.IGNORECASE)
        
        # 5. Limpar seções CSS de gradientes órfãs
        updated_content = re.sub(r'/\*\s*Gradientes[^*]*\*/', '', updated_content, flags=re.IGNORECASE)
        
        return updated_content, gradients_found

    def update_css_classes(self, content):
        """Atualiza classes CSS que dependiam de gradientes"""
        updates = {
            # Botões primários
            '.btn-primary': f'''
            .btn-primary {{
                background-color: {self.solid_colors['primary']} !important;
                border-color: {self.solid_colors['primary']} !important;
                color: white !important;
            }}
            
            .btn-primary:hover {{
                background-color: {self.solid_colors['secondary']} !important;
                border-color: {self.solid_colors['secondary']} !important;
                transform: translateY(-1px);
                box-shadow: 0 4px 8px rgba(85, 80, 242, 0.3);
            }}''',
            
            # Sidebar
            '.sidebar': f'''
            .sidebar {{
                background-color: {self.solid_colors['primary']} !important;
                color: white;
            }}''',
            
            # Headers de cards
            '.card-header': f'''
            .card-header {{
                background-color: {self.solid_colors['primary']} !important;
                color: white !important;
                border: none !important;
            }}''',
            
            # Estatísticas
            '.stats-card': f'''
            .stats-card {{
                background: white !important;
                border-left: 4px solid {self.solid_colors['primary']} !important;
            }}''',
        }
        
        # Aplicar atualizações no CSS
        for selector, css_rule in updates.items():
            # Procurar e substituir regras CSS existentes
            pattern = f'{re.escape(selector)}\\s*{{[^}}]*}}'
            if re.search(pattern, content, re.IGNORECASE | re.DOTALL):
                content = re.sub(pattern, css_rule, content, flags=re.IGNORECASE | re.DOTALL)
            else:
                # Se não encontrar, adicionar no final do CSS
                if '</style>' in content:
                    content = content.replace('</style>', f'\n        {css_rule}\n        </style>')
        
        return content

    def process_file(self, file_path):
        """Processa um arquivo removendo gradientes"""
        try:
            with open(file_path, 'r', encoding='utf-8') as f:
                content = f.read()
            
            filename = os.path.basename(file_path)
            
            # Remover gradientes
            updated_content, gradients_found = self.remove_gradients_from_content(content)
            
            if gradients_found > 0:
                print(f"🎨 Processando: {filename} - {gradients_found} gradientes encontrados")
                
                # Atualizar classes CSS
                updated_content = self.update_css_classes(updated_content)
                
                # Salvar arquivo atualizado
                with open(file_path, 'w', encoding='utf-8') as f:
                    f.write(updated_content)
                
                print(f"✅ {filename} - {gradients_found} gradientes removidos!")
                self.processed_count += 1
                return gradients_found
            else:
                print(f"⚪ {filename} - nenhum gradiente encontrado")
                return 0
                
        except Exception as e:
            print(f"❌ Erro ao processar {filename}: {str(e)}")
            return 0

    def remove_all_gradients(self):
        """Remove todos os gradientes do projeto"""
        print("🎨 Iniciando remoção de gradientes do projeto...")
        print("🔍 Convertendo para cores sólidas da paleta Duralux...")
        print("=" * 70)
        
        # Criar backup
        self.create_backup()
        
        # Encontrar arquivos HTML
        html_files = glob.glob(os.path.join(self.base_dir, "*.html"))
        
        if not html_files:
            print("❌ Nenhum arquivo HTML encontrado!")
            return False
        
        print(f"📁 Processando {len(html_files)} arquivos HTML")
        print("-" * 70)
        
        total_gradients = 0
        
        # Processar cada arquivo
        for file_path in sorted(html_files):
            gradients_in_file = self.process_file(file_path)
            total_gradients += gradients_in_file
        
        # Relatório final
        print("=" * 70)
        print(f"🎉 REMOÇÃO DE GRADIENTES CONCLUÍDA!")
        print(f"   • Total de arquivos processados: {len(html_files)}")
        print(f"   • Arquivos com gradientes: {self.processed_count}")
        print(f"   • Total de gradientes removidos: {total_gradients}")
        print(f"   • Backup salvo em: {self.backup_dir}")
        
        if total_gradients > 0:
            print("\n✨ CONVERSÕES REALIZADAS:")
            print(f"   🎨 Gradientes → Cores sólidas da paleta")
            print(f"   💙 Primário: {self.solid_colors['primary']}")
            print(f"   💚 Secundário: {self.solid_colors['secondary']}")
            print(f"   💛 Success: {self.solid_colors['success']}")
            print(f"   🧡 Warning: {self.solid_colors['warning']}")
            print(f"   ⚪ Light: {self.solid_colors['light']}")
            
            print(f"\n🌐 Visualize o resultado: duralux-mu.vercel.app")
            print(f"💡 Interface agora com design sólido e clean!")
        
        return total_gradients > 0

def main():
    """Função principal"""
    remover = GradientRemover()
    result = remover.remove_all_gradients()
    
    if result:
        print(f"\n🎊 Todos os gradientes foram removidos com sucesso!")
        print(f"🎨 Design agora totalmente baseado em cores sólidas!")
    else:
        print(f"\n⚪ Nenhum gradiente encontrado para remover.")

if __name__ == "__main__":
    main()