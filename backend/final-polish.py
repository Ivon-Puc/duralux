#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script de polimento final - Ajustes específicos para a nova identidade
Duralux Final Polish - v1.0
Author: Maria Eduarda Cardoso de Oliveira
Date: 2025-11-06
"""

import os
import re
import glob
from datetime import datetime

class FinalPolisher:
    def __init__(self, base_dir="../duralux-admin"):
        self.base_dir = base_dir
        self.polished_count = 0
        
        # Paleta de cores para referência
        self.colors = {
            'primary': '#5550F2',
            'secondary': '#027368', 
            'success': '#04BF9D',
            'warning': '#F2B33D',
            'light': '#F2F2F2'
        }

    def fix_specific_elements(self, content, filename):
        """Aplica correções específicas baseadas no tipo de página"""
        
        # Correções específicas para a página de leads
        if 'leads' in filename.lower():
            # Corrigir cabeçalhos da tabela de leads
            content = re.sub(
                r'<th[^>]*>Lead</th>',
                f'<th style="background: var(--primary-color); color: white; font-weight: 600;">Lead</th>',
                content
            )
            content = re.sub(
                r'<th[^>]*>E-mail</th>',
                f'<th style="background: var(--primary-color); color: white; font-weight: 600;">E-mail</th>',
                content
            )
            content = re.sub(
                r'<th[^>]*>Origem</th>',
                f'<th style="background: var(--primary-color); color: white; font-weight: 600;">Origem</th>',
                content
            )
            content = re.sub(
                r'<th[^>]*>Telefone</th>',
                f'<th style="background: var(--primary-color); color: white; font-weight: 600;">Telefone</th>',
                content
            )
            content = re.sub(
                r'<th[^>]*>Data</th>',
                f'<th style="background: var(--primary-color); color: white; font-weight: 600;">Data</th>',
                content
            )
            content = re.sub(
                r'<th[^>]*>Status</th>',
                f'<th style="background: var(--primary-color); color: white; font-weight: 600;">Status</th>',
                content
            )
            
            # Melhorar botão "Criar Lead"
            content = re.sub(
                r'<button[^>]*class="[^"]*btn[^"]*"[^>]*>Criar Lead</button>',
                f'<button class="btn btn-primary" style="background: var(--primary-gradient); border: none; border-radius: 8px; font-weight: 600; padding: 0.75rem 1.5rem; box-shadow: 0 2px 4px rgba(85, 80, 242, 0.2);"><i class="bi bi-plus-circle me-2"></i>Criar Lead</button>',
                content
            )
            
            # Status badges coloridos
            content = re.sub(
                r'>Contacted<',
                f' style="background-color: var(--info-color); color: white; padding: 0.25rem 0.5rem; border-radius: 0.375rem; font-size: 0.8rem; font-weight: 500;">Contatado<',
                content
            )
            content = re.sub(
                r'>Customer<',
                f' style="background-color: var(--success-color); color: white; padding: 0.25rem 0.5rem; border-radius: 0.375rem; font-size: 0.8rem; font-weight: 500;">Cliente<',
                content
            )
            content = re.sub(
                r'>Qualified<',
                f' style="background-color: var(--warning-color); color: var(--dark-color); padding: 0.25rem 0.5rem; border-radius: 0.375rem; font-size: 0.8rem; font-weight: 500;">Qualificado<',
                content
            )
            content = re.sub(
                r'>Declined<',
                f' style="background-color: var(--danger-color); color: white; padding: 0.25rem 0.5rem; border-radius: 0.375rem; font-size: 0.8rem; font-weight: 500;">Recusado<',
                content
            )
        
        # Correções para dashboard (index)
        if filename.lower() == 'index.html':
            # Melhorar cards de estatísticas
            content = re.sub(
                r'class="stats-card"',
                f'class="stats-card" style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07); border-left: 4px solid var(--primary-color); margin-bottom: 1rem;"',
                content
            )
            
            # Títulos de seções com nova cor
            content = re.sub(
                r'<h1[^>]*class="h3"[^>]*>([^<]+)</h1>',
                f'<h1 class="h3" style="color: var(--primary-color); font-weight: 700; margin-bottom: 1rem;">\\1</h1>',
                content
            )
        
        # Correções para analytics
        if 'analytics' in filename.lower():
            # Melhorar títulos de gráficos
            content = re.sub(
                r'<h2[^>]*>([^<]*Analytics?[^<]*)</h2>',
                f'<h2 style="color: var(--primary-color); font-weight: 700; margin-bottom: 1.5rem; text-align: center;">\\1</h2>',
                content
            )
        
        # Melhorias gerais para todos os arquivos
        
        # Melhorar sidebar
        content = re.sub(
            r'class="nav-link active"',
            f'class="nav-link active" style="background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(10px); border-radius: 8px; margin-bottom: 0.25rem;"',
            content
        )
        
        content = re.sub(
            r'class="nav-link"(?![^>]*active)',
            f'class="nav-link" style="margin-bottom: 0.25rem; border-radius: 8px;" onmouseover="this.style.background=\'rgba(255, 255, 255, 0.1)\'" onmouseout="this.style.background=\'transparent\'"',
            content
        )
        
        # Melhorar breadcrumbs
        content = re.sub(
            r'<nav aria-label="breadcrumb">',
            f'<nav aria-label="breadcrumb" style="margin-bottom: 1.5rem;">',
            content
        )
        
        # Adicionar animações suaves aos cards
        content = re.sub(
            r'class="card"',
            f'class="card" style="transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);" onmouseover="this.style.transform=\'translateY(-2px)\'; this.style.boxShadow=\'0 8px 15px rgba(0, 0, 0, 0.1)\'" onmouseout="this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 4px 6px rgba(0, 0, 0, 0.07)\'"',
            content
        )
        
        return content

    def add_modern_interactions(self, content):
        """Adiciona interações modernas com JavaScript"""
        
        # Script para melhorar interações
        modern_js = '''
        <script>
        // Melhorias de UX da identidade Duralux 2025
        document.addEventListener('DOMContentLoaded', function() {
            
            // Efeito ripple nos botões
            document.querySelectorAll('.btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    const ripple = document.createElement('span');
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;
                    
                    ripple.style.width = ripple.style.height = size + 'px';
                    ripple.style.left = x + 'px';
                    ripple.style.top = y + 'px';
                    ripple.style.position = 'absolute';
                    ripple.style.borderRadius = '50%';
                    ripple.style.background = 'rgba(255, 255, 255, 0.5)';
                    ripple.style.transform = 'scale(0)';
                    ripple.style.animation = 'ripple 0.6s linear';
                    ripple.style.pointerEvents = 'none';
                    
                    this.style.position = 'relative';
                    this.style.overflow = 'hidden';
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });
            
            // Smooth scroll para links internos
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
            
            // Feedback visual para formulários
            document.querySelectorAll('.form-control, .form-select').forEach(input => {
                input.addEventListener('focus', function() {
                    this.style.borderColor = 'var(--primary-color)';
                    this.style.boxShadow = '0 0 0 0.2rem rgba(85, 80, 242, 0.25)';
                });
                
                input.addEventListener('blur', function() {
                    if (!this.value) {
                        this.style.borderColor = '#dee2e6';
                        this.style.boxShadow = 'none';
                    }
                });
            });
            
            // Animação de carregamento para botões
            document.querySelectorAll('.btn[type="submit"]').forEach(button => {
                button.addEventListener('click', function() {
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="bi bi-arrow-repeat spin me-2"></i>Processando...';
                    this.disabled = true;
                    
                    // Simular carregamento (remover em produção real)
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.disabled = false;
                    }, 2000);
                });
            });
            
        });
        
        // CSS para animação de ripple
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
            
            .spin {
                animation: spin 1s linear infinite;
            }
            
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
        </script>
        '''
        
        # Inserir script antes do fechamento do body
        if '</body>' in content:
            content = content.replace('</body>', f'{modern_js}\n</body>')
        
        return content

    def polish_file(self, file_path):
        """Aplica polimento final em um arquivo"""
        try:
            with open(file_path, 'r', encoding='utf-8') as f:
                content = f.read()
            
            filename = os.path.basename(file_path)
            print(f"✨ Polindo: {filename}")
            
            # Aplicar correções específicas
            content = self.fix_specific_elements(content, filename)
            
            # Adicionar interações modernas
            content = self.add_modern_interactions(content)
            
            # Salvar arquivo polido
            with open(file_path, 'w', encoding='utf-8') as f:
                f.write(content)
            
            self.polished_count += 1
            print(f"💎 {filename} - polimento concluído!")
            
            return True
            
        except Exception as e:
            print(f"❌ Erro no polimento de {filename}: {str(e)}")
            return False

    def polish_all_files(self):
        """Aplica polimento final em todos os arquivos"""
        print("✨ Iniciando polimento final da identidade visual...")
        print("💎 Aplicando acabamentos profissionais...")
        print("=" * 70)
        
        # Focar nas páginas principais
        priority_files = [
            'index.html',
            'leads.html', 
            'leads-view.html',
            'customers.html',
            'projects.html',
            'analytics.html',
            'analytics-advanced.html'
        ]
        
        polished_files = []
        
        # Polir arquivos prioritários primeiro
        for filename in priority_files:
            file_path = os.path.join(self.base_dir, filename)
            if os.path.exists(file_path):
                if self.polish_file(file_path):
                    polished_files.append(filename)
        
        # Depois polir outros arquivos importantes
        other_files = glob.glob(os.path.join(self.base_dir, "*.html"))
        for file_path in sorted(other_files):
            filename = os.path.basename(file_path)
            if filename not in priority_files:
                if filename.startswith(('auth-', 'settings-', 'widgets-')):
                    continue  # Pular arquivos menos críticos
                if self.polish_file(file_path):
                    polished_files.append(filename)
        
        # Relatório final
        print("=" * 70)
        print(f"💎 POLIMENTO FINAL CONCLUÍDO!")
        print(f"   • Arquivos polidos: {len(polished_files)}")
        print(f"   • Páginas prioritárias: {len([f for f in polished_files if f in priority_files])}")
        
        if polished_files:
            print("\n✨ MELHORIAS APLICADAS:")
            print("   🎨 Status badges coloridos nos leads")
            print("   💫 Efeitos ripple nos botões")
            print("   🌊 Smooth scroll para navegação")
            print("   🔄 Animações de carregamento")
            print("   🎯 Feedback visual em formulários")
            print("   🃏 Hover effects nos cards")
            print("   🎭 Interações modernas com JavaScript")
            
            print(f"\n🌐 Sistema com acabamento premium em: duralux-mu.vercel.app")
        
        return len(polished_files) > 0

def main():
    """Função principal"""
    polisher = FinalPolisher()
    result = polisher.polish_all_files()
    
    if result:
        print(f"\n🏆 Identidade visual com acabamento premium aplicado!")
    else:
        print(f"\n💥 Falha no polimento final.")

if __name__ == "__main__":
    main()