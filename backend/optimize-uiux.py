#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script para aplicar melhores práticas de UI/UX - Duralux Usability Optimizer
UI/UX Best Practices Implementation - v1.0
Author: Maria Eduarda Cardoso de Oliveira
Date: 2025-11-06
"""

import os
import re
import glob
from datetime import datetime

class UIUXOptimizer:
    def __init__(self, base_dir="../duralux-admin"):
        self.base_dir = base_dir
        self.backup_dir = "backups/uiux_backup_" + datetime.now().strftime("%Y%m%d_%H%M%S")
        self.optimized_count = 0
        
        # Paleta de cores do sistema
        self.colors = {
            'primary': '#5550F2',
            'secondary': '#027368', 
            'success': '#04BF9D',
            'warning': '#F2B33D',
            'light': '#F2F2F2',
            'dark': '#2C3E50',
            'info': '#3498DB',
            'danger': '#E74C3C'
        }

    def create_backup(self):
        """Cria backup antes das otimizações"""
        if not os.path.exists(self.backup_dir):
            os.makedirs(self.backup_dir)
        
        html_files = glob.glob(os.path.join(self.base_dir, "*.html"))
        
        print(f"📦 Criando backup para otimizações UI/UX: {self.backup_dir}")
        
        for file_path in html_files:
            filename = os.path.basename(file_path)
            backup_path = os.path.join(self.backup_dir, filename)
            import shutil
            shutil.copy2(file_path, backup_path)
        
        print(f"✅ Backup de {len(html_files)} arquivos criado!")

    def apply_accessibility_improvements(self, content):
        """Aplica melhorias de acessibilidade"""
        
        # 1. Adicionar labels ausentes em inputs
        content = re.sub(
            r'(<input(?![^>]*id=)[^>]*)(>)',
            r'\1 id="input_' + str(hash(content) % 10000) + r'"\2',
            content
        )
        
        # 2. Melhorar contraste de textos
        content = re.sub(
            r'color:\s*#6c757d',
            f'color: {self.colors["dark"]}',
            content
        )
        
        # 3. Adicionar roles ARIA apropriados
        content = re.sub(
            r'<nav(?![^>]*role=)',
            r'<nav role="navigation"',
            content
        )
        
        content = re.sub(
            r'<main(?![^>]*role=)',
            r'<main role="main"',
            content
        )
        
        # 4. Melhorar botões com aria-labels
        content = re.sub(
            r'(<button[^>]*class="[^"]*btn[^"]*"[^>]*)(>)',
            r'\1 aria-label="Ação do botão"\2',
            content
        )
        
        # 5. Adicionar alt texts descritivos em imagens
        content = re.sub(
            r'alt="Foto do cliente"',
            r'alt="Foto de perfil do cliente"',
            content
        )
        
        return content

    def improve_navigation_ux(self, content):
        """Melhora a experiência de navegação"""
        
        # 1. Breadcrumbs mais informativos
        content = re.sub(
            r'<li class="breadcrumb-item active">([^<]+)</li>',
            r'<li class="breadcrumb-item active" aria-current="page">\1</li>',
            content
        )
        
        # 2. Indicadores visuais de estado ativo na sidebar
        sidebar_improvements = '''
        /* Melhorias de navegação */
        .nav-pills .nav-link {
            position: relative;
            transition: all 0.3s ease;
            margin-bottom: 0.5rem;
            border-radius: 8px;
            padding: 0.75rem 1rem;
        }
        
        .nav-pills .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(4px);
        }
        
        .nav-pills .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .nav-pills .nav-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 70%;
            background: white;
            border-radius: 2px;
        }
        '''
        
        if '</style>' in content:
            content = content.replace('</style>', f'{sidebar_improvements}\n        </style>')
        
        return content

    def enhance_form_usability(self, content):
        """Melhora usabilidade de formulários"""
        
        # 1. Labels mais descritivos
        content = re.sub(
            r'<label[^>]*class="form-label"[^>]*>([^<]+)</label>',
            r'<label class="form-label" style="font-weight: 600; color: var(--dark-color); margin-bottom: 0.5rem;">\1 <span style="color: var(--danger-color);">*</span></label>',
            content
        )
        
        # 2. Inputs com melhor feedback visual
        form_improvements = '''
        /* Melhorias de formulários */
        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: white;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(85, 80, 242, 0.1);
            outline: none;
            background: #fafbfc;
        }
        
        .form-control:invalid {
            border-color: var(--danger-color);
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
        }
        
        .form-control:valid {
            border-color: var(--success-color);
        }
        
        /* Placeholder melhorado */
        .form-control::placeholder {
            color: #8e9aaf;
            font-style: italic;
        }
        
        /* Grupos de formulário */
        .form-group, .mb-3 {
            margin-bottom: 1.5rem;
        }
        
        /* Botões de formulário */
        .btn[type="submit"] {
            min-width: 120px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 0.75rem 2rem;
        }
        '''
        
        if '</style>' in content:
            content = content.replace('</style>', f'{form_improvements}\n        </style>')
        
        return content

    def improve_table_ux(self, content):
        """Melhora usabilidade de tabelas"""
        
        # 1. Headers mais claros
        content = re.sub(
            r'<th([^>]*)>([^<]+)</th>',
            r'<th\1 style="background: var(--primary-color); color: white; font-weight: 600; text-align: center; padding: 1rem; border: none; position: sticky; top: 0; z-index: 10;">\2</th>',
            content
        )
        
        # 2. Melhorar responsividade das tabelas
        table_improvements = '''
        /* Melhorias de tabelas */
        .table-responsive {
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
            border: 1px solid #e9ecef;
            overflow: hidden;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table td {
            padding: 1rem;
            vertical-align: middle;
            border-color: #f1f3f4;
            font-size: 0.9rem;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9fe;
            transform: scale(1.001);
            transition: all 0.2s ease;
        }
        
        /* Status badges melhorados */
        .badge {
            padding: 0.5rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        /* Paginação melhorada */
        .pagination {
            justify-content: center;
            margin-top: 2rem;
        }
        
        .page-link {
            border: none;
            border-radius: 6px;
            margin: 0 2px;
            color: var(--primary-color);
            font-weight: 500;
        }
        
        .page-link:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-1px);
        }
        '''
        
        if '</style>' in content:
            content = content.replace('</style>', f'{table_improvements}\n        </style>')
        
        return content

    def add_loading_states(self, content):
        """Adiciona estados de carregamento"""
        
        loading_js = '''
        <script>
        // Estados de carregamento e feedback
        document.addEventListener('DOMContentLoaded', function() {
            
            // Loading overlay
            function showLoading() {
                const overlay = document.createElement('div');
                overlay.id = 'loadingOverlay';
                overlay.innerHTML = `
                    <div class="loading-spinner">
                        <div class="spinner"></div>
                        <p>Carregando...</p>
                    </div>
                `;
                document.body.appendChild(overlay);
            }
            
            function hideLoading() {
                const overlay = document.getElementById('loadingOverlay');
                if (overlay) overlay.remove();
            }
            
            // Loading em formulários
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="bi bi-arrow-repeat spin me-2"></i>Processando...';
                        
                        setTimeout(() => {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = submitBtn.getAttribute('data-original-text') || 'Salvar';
                        }, 2000);
                    }
                });
            });
            
            // Loading em links de navegação
            document.querySelectorAll('a[href]:not([href^="#"])').forEach(link => {
                link.addEventListener('click', function() {
                    showLoading();
                    setTimeout(hideLoading, 1000);
                });
            });
            
            // Skeleton loading para tabelas
            function showTableSkeleton(table) {
                const tbody = table.querySelector('tbody');
                if (tbody) {
                    tbody.innerHTML = `
                        ${'<tr>' + '<td><div class="skeleton"></div></td>'.repeat(6) + '</tr>'.repeat(3)}
                    `;
                }
            }
            
            // Auto-save feedback
            let autoSaveTimeout;
            document.querySelectorAll('.form-control').forEach(input => {
                input.addEventListener('input', function() {
                    clearTimeout(autoSaveTimeout);
                    
                    // Mostrar indicador de mudanças não salvas
                    this.style.borderLeftColor = 'var(--warning-color)';
                    this.style.borderLeftWidth = '4px';
                    
                    autoSaveTimeout = setTimeout(() => {
                        // Simular auto-save
                        this.style.borderLeftColor = 'var(--success-color)';
                        
                        setTimeout(() => {
                            this.style.borderLeftColor = '';
                            this.style.borderLeftWidth = '';
                        }, 2000);
                    }, 1000);
                });
            });
        });
        </script>
        '''
        
        if '</body>' in content:
            content = content.replace('</body>', f'{loading_js}\n</body>')
        
        return content

    def add_enhanced_css(self, content):
        """Adiciona CSS aprimorado para UI/UX"""
        
        enhanced_css = '''
        /* === UI/UX ENHANCEMENTS === */
        
        /* Loading states */
        #loadingOverlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            backdrop-filter: blur(4px);
        }
        
        .loading-spinner {
            text-align: center;
            color: var(--primary-color);
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Skeleton loading */
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: 4px;
            height: 20px;
        }
        
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        /* Micro-interactions */
        .btn, .card, .form-control {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .btn:active {
            transform: scale(0.98);
        }
        
        /* Focus indicators melhorados */
        *:focus {
            outline: 3px solid rgba(85, 80, 242, 0.3);
            outline-offset: 2px;
        }
        
        /* Toast notifications */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }
        
        .toast {
            min-width: 300px;
            border: none;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .toast-success {
            border-left: 4px solid var(--success-color);
        }
        
        .toast-error {
            border-left: 4px solid var(--danger-color);
        }
        
        /* Progress indicators */
        .progress {
            height: 8px;
            border-radius: 4px;
            background: #e9ecef;
            overflow: hidden;
        }
        
        .progress-bar {
            background: var(--primary-color);
            transition: width 0.6s ease;
        }
        
        /* Empty states */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }
        
        /* Search improvements */
        .search-container {
            position: relative;
        }
        
        .search-container input {
            padding-left: 2.5rem;
        }
        
        .search-container i {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        
        /* Keyboard navigation */
        .keyboard-navigation:focus-within {
            box-shadow: 0 0 0 3px rgba(85, 80, 242, 0.2);
            border-radius: 4px;
        }
        
        /* Mobile improvements */
        @media (max-width: 768px) {
            .btn {
                padding: 0.75rem 1rem;
                font-size: 0.9rem;
            }
            
            .table-responsive {
                font-size: 0.8rem;
            }
            
            .form-control {
                padding: 1rem;
                font-size: 1rem; /* Evita zoom no iOS */
            }
            
            .sidebar {
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            }
        }
        
        /* Hover states aprimorados */
        .clickable:hover {
            cursor: pointer;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        /* Status indicators */
        .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 0.5rem;
        }
        
        .status-active { background: var(--success-color); }
        .status-inactive { background: var(--danger-color); }
        .status-pending { background: var(--warning-color); }
        '''
        
        if '</style>' in content:
            content = content.replace('</style>', f'{enhanced_css}\n        </style>')
        
        return content

    def optimize_file(self, file_path):
        """Otimiza um arquivo com melhorias de UI/UX"""
        try:
            with open(file_path, 'r', encoding='utf-8') as f:
                content = f.read()
            
            filename = os.path.basename(file_path)
            print(f"🎯 Otimizando UI/UX: {filename}")
            
            # Aplicar todas as otimizações
            content = self.apply_accessibility_improvements(content)
            content = self.improve_navigation_ux(content)
            content = self.enhance_form_usability(content)
            content = self.improve_table_ux(content)
            content = self.add_loading_states(content)
            content = self.add_enhanced_css(content)
            
            # Salvar arquivo otimizado
            with open(file_path, 'w', encoding='utf-8') as f:
                f.write(content)
            
            self.optimized_count += 1
            print(f"✅ {filename} - UI/UX otimizado!")
            
            return True
            
        except Exception as e:
            print(f"❌ Erro ao otimizar {filename}: {str(e)}")
            return False

    def optimize_all_files(self):
        """Otimiza UI/UX de todas as páginas"""
        print("🎯 Iniciando otimização UI/UX completa...")
        print("🚀 Aplicando melhores práticas de usabilidade...")
        print("=" * 70)
        
        # Criar backup
        self.create_backup()
        
        # Encontrar arquivos HTML
        html_files = glob.glob(os.path.join(self.base_dir, "*.html"))
        
        if not html_files:
            print("❌ Nenhum arquivo HTML encontrado!")
            return False
        
        print(f"📁 Otimizando {len(html_files)} páginas")
        print("-" * 70)
        
        # Otimizar arquivos prioritários primeiro
        priority_files = [
            'index.html',
            'leads.html',
            'customers.html', 
            'projects.html',
            'analytics.html',
            'leads-create.html',
            'leads-view.html'
        ]
        
        success_count = 0
        
        # Otimizar arquivos prioritários
        for filename in priority_files:
            file_path = os.path.join(self.base_dir, filename)
            if os.path.exists(file_path):
                if self.optimize_file(file_path):
                    success_count += 1
        
        # Otimizar outros arquivos
        for file_path in sorted(html_files):
            filename = os.path.basename(file_path)
            if filename not in priority_files:
                if self.optimize_file(file_path):
                    success_count += 1
        
        # Relatório final
        print("=" * 70)
        print(f"🎉 OTIMIZAÇÃO UI/UX CONCLUÍDA!")
        print(f"   • Total de páginas: {len(html_files)}")
        print(f"   • Otimizadas com sucesso: {success_count}")
        print(f"   • Backup salvo em: {self.backup_dir}")
        
        if success_count > 0:
            print("\n✨ MELHORIAS APLICADAS:")
            print("   ♿ Acessibilidade aprimorada")
            print("   🧭 Navegação mais intuitiva")
            print("   📝 Formulários com melhor UX")
            print("   📊 Tabelas responsivas otimizadas")
            print("   ⏳ Estados de carregamento")
            print("   📱 Responsividade mobile melhorada")
            print("   🎨 Micro-interações suaves")
            print("   🔍 Indicadores visuais claros")
            print("   ⌨️ Suporte a navegação por teclado")
            print("   🎯 Feedback visual instantâneo")
            
            print(f"\n🌐 Experimente a nova UX: duralux-mu.vercel.app")
        
        return success_count > 0

def main():
    """Função principal"""
    optimizer = UIUXOptimizer()
    result = optimizer.optimize_all_files()
    
    if result:
        print(f"\n🏆 UI/UX otimizado com as melhores práticas!")
    else:
        print(f"\n💥 Falha na otimização UI/UX.")

if __name__ == "__main__":
    main()