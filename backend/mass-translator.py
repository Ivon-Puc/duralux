#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script de Tradução Automática em Massa - Projeto Duralux
Traduz automaticamente textos em inglês para português em todas as páginas HTML
"""

import os
import re
import glob
import json
import shutil
from collections import defaultdict, Counter
from datetime import datetime

class DuraluxTranslator:
    def __init__(self):
        self.html_dir = "duralux-admin"
        self.backup_dir = "backup_html"
        self.log_file = "translation_log.json"
        
        # Dicionário de traduções por categoria
        self.translations = {
            # Interface/UI - Botões e Ações
            "ui_actions": {
                "Save": "Salvar",
                "Cancel": "Cancelar", 
                "Edit": "Editar",
                "Delete": "Excluir",
                "Create": "Criar",
                "Add": "Adicionar",
                "Remove": "Remover",
                "Update": "Atualizar",
                "Submit": "Enviar",
                "Search": "Buscar",
                "Filter": "Filtrar",
                "Sort": "Ordenar",
                "Select": "Selecionar",
                "Choose": "Escolher",
                "View": "Visualizar",
                "Show": "Mostrar",
                "Hide": "Ocultar",
                "Download": "Baixar",
                "Upload": "Carregar",
                "Import": "Importar",
                "Export": "Exportar",
                "Print": "Imprimir",
                "Share": "Compartilhar",
                "Copy": "Copiar",
                "Cut": "Recortar",
                "Paste": "Colar"
            },
            
            # Navegação e Interface
            "navigation": {
                "Home": "Início",
                "Dashboard": "Painel",
                "Overview": "Visão Geral",
                "Analytics": "Analíticos",
                "Reports": "Relatórios",
                "Settings": "Configurações",
                "Profile": "Perfil",
                "Account": "Conta",
                "Help": "Ajuda",
                "Support": "Suporte",
                "Contact": "Contato",
                "About": "Sobre",
                "Next": "Próximo",
                "Previous": "Anterior",
                "First": "Primeiro",
                "Last": "Último",
                "Page": "Página",
                "All": "Todos",
                "None": "Nenhum"
            },
            
            # Status e Estados
            "status": {
                "Active": "Ativo",
                "Inactive": "Inativo",
                "Pending": "Pendente",
                "Completed": "Concluído",
                "In Progress": "Em Andamento",
                "Draft": "Rascunho",
                "Published": "Publicado",
                "Archived": "Arquivado",
                "New": "Novo",
                "Updated": "Atualizado",
                "Success": "Sucesso",
                "Error": "Erro",
                "Warning": "Aviso",
                "Info": "Informação",
                "Loading": "Carregando",
                "Processing": "Processando"
            },
            
            # Dados e Campos
            "data_fields": {
                "Name": "Nome",
                "Description": "Descrição", 
                "Title": "Título",
                "Email": "E-mail",
                "Phone": "Telefone",
                "Address": "Endereço",
                "Date": "Data",
                "Time": "Hora",
                "Status": "Status",
                "Actions": "Ações",
                "Details": "Detalhes",
                "Total": "Total",
                "Count": "Quantidade",
                "Amount": "Valor",
                "Price": "Preço",
                "Category": "Categoria",
                "Type": "Tipo",
                "Priority": "Prioridade"
            },
            
            # Módulos do Sistema
            "modules": {
                "Users": "Usuários",
                "Customers": "Clientes", 
                "Projects": "Projetos",
                "Tasks": "Tarefas",
                "Leads": "Leads",
                "Sales": "Vendas",
                "Marketing": "Marketing",
                "Campaign": "Campanha",
                "Revenue": "Receita",
                "Finance": "Financeiro",
                "Invoice": "Fatura",
                "Proposal": "Proposta"
            },
            
            # Tempo e Datas
            "datetime": {
                "Today": "Hoje",
                "Yesterday": "Ontem", 
                "Tomorrow": "Amanhã",
                "Week": "Semana",
                "Month": "Mês",
                "Year": "Ano",
                "Minutes": "minutos",
                "Hours": "horas",
                "Days": "dias",
                "Weeks": "semanas",
                "Months": "meses",
                "Years": "anos"
            },
            
            # Autenticação
            "auth": {
                "Login": "Entrar",
                "Logout": "Sair",
                "Register": "Cadastrar",
                "Password": "Senha",
                "Username": "Usuário",
                "Remember Me": "Lembrar de mim",
                "Forgot Password": "Esqueci a senha",
                "Reset Password": "Redefinir senha"
            },
            
            # Mensagens e Notificações
            "messages": {
                "Welcome": "Bem-vindo",
                "Hello": "Olá",
                "Good morning": "Bom dia",
                "Good afternoon": "Boa tarde", 
                "Good evening": "Boa noite",
                "Thank you": "Obrigado",
                "Please wait": "Aguarde",
                "Try again": "Tente novamente",
                "Learn more": "Saiba mais",
                "Read more": "Leia mais",
                "Contact us": "Entre em contato",
                "Need help": "Precisa de ajuda"
            }
        }
        
        # Criar dicionário unificado para busca rápida
        self.all_translations = {}
        for category, translations in self.translations.items():
            self.all_translations.update(translations)
        
        # Padrões especiais que precisam de cuidado
        self.special_patterns = {
            # Frases comuns
            r'\bfrom last week\b': 'da semana passada',
            r'\bfrom last month\b': 'do mês passado',
            r'\bView all\b': 'Ver todos',
            r'\bSelect all\b': 'Selecionar todos',
            r'\bItems per page\b': 'Itens por página',
            r'\bNo results found\b': 'Nenhum resultado encontrado',
            r'\bSearch results\b': 'Resultados da busca',
            r'\bLoad more\b': 'Carregar mais',
            r'\bShow more\b': 'Mostrar mais',
            r'\bLess\b': 'Menos',
            r'\bMore\b': 'Mais',
            
            # Títulos e cabeçalhos comuns
            r'\bStore Overview\b': 'Visão Geral da Loja',
            r'\bSales Overview\b': 'Visão Geral de Vendas',
            r'\bUser Management\b': 'Gerenciamento de Usuários',
            r'\bCustomer Management\b': 'Gerenciamento de Clientes',
            r'\bProject Management\b': 'Gerenciamento de Projetos',
            r'\bTask Management\b': 'Gerenciamento de Tarefas',
            
            # Formulários
            r'\bRequired field\b': 'Campo obrigatório',
            r'\bOptional\b': 'Opcional',
            r'\bPlease select\b': 'Por favor selecione',
            r'\bChoose file\b': 'Escolher arquivo',
            r'\bBrowse\b': 'Navegar',
            
            # Ações específicas
            r'\bMark as read\b': 'Marcar como lido',
            r'\bMark as unread\b': 'Marcar como não lido',
            r'\bReply\b': 'Responder',
            r'\bForward\b': 'Encaminhar',
            r'\bArchive\b': 'Arquivar',
            r'\bRestore\b': 'Restaurar'
        }
        
    def create_backup(self):
        """Cria backup das páginas HTML antes da tradução"""
        if not os.path.exists(self.html_dir):
            print(f"❌ Diretório {self.html_dir} não encontrado!")
            return False
            
        # Criar diretório de backup com timestamp
        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        backup_path = f"{self.backup_dir}_{timestamp}"
        
        try:
            if os.path.exists(self.html_dir):
                shutil.copytree(self.html_dir, backup_path)
                print(f"✅ Backup criado em: {backup_path}")
                return backup_path
        except Exception as e:
            print(f"❌ Erro ao criar backup: {e}")
            return False
    
    def is_safe_to_translate(self, context_line, word):
        """Verifica se é seguro traduzir uma palavra baseado no contexto"""
        
        # Não traduzir dentro de:
        unsafe_contexts = [
            'href=', 'src=', 'id=', 'class=', 'data-', 'onclick=', 'onchange=',
            'console.', 'function(', 'var ', 'let ', 'const ', 'return ',
            '<!--', '-->', '<script', '</script>', '<style', '</style>',
            'javascript:', 'getElementById', 'querySelector', 'addEventListener',
            '.css', '.js', '.json', '.php', '.html', 'http://', 'https://',
            'placeholder=', 'value=', 'name=', 'type=', 'method=', 'action='
        ]
        
        context_lower = context_line.lower()
        
        # Verificar se está em contexto inseguro
        for unsafe in unsafe_contexts:
            if unsafe in context_lower:
                return False
        
        # Verificar se está dentro de tags de código
        if '<code>' in context_lower or '<pre>' in context_lower:
            return False
            
        # Verificar se está em URL ou caminho
        if '/' in context_line and ('http' in context_lower or '.com' in context_lower):
            return False
            
        return True
    
    def translate_file(self, file_path):
        """Traduz um arquivo HTML específico"""
        try:
            with open(file_path, 'r', encoding='utf-8') as f:
                content = f.read()
                
            original_content = content
            translations_made = []
            
            # 1. Aplicar padrões especiais primeiro
            for pattern, translation in self.special_patterns.items():
                matches = list(re.finditer(pattern, content, re.IGNORECASE))
                for match in matches:
                    # Verificar contexto
                    line_start = content.rfind('\n', 0, match.start()) + 1
                    line_end = content.find('\n', match.end())
                    if line_end == -1:
                        line_end = len(content)
                    
                    context_line = content[line_start:line_end]
                    
                    if self.is_safe_to_translate(context_line, match.group()):
                        content = content[:match.start()] + translation + content[match.end():]
                        translations_made.append({
                            'original': match.group(),
                            'translation': translation,
                            'context': context_line.strip()[:100] + '...' if len(context_line.strip()) > 100 else context_line.strip()
                        })
            
            # 2. Aplicar traduções de palavras individuais
            for english, portuguese in self.all_translations.items():
                # Criar padrão para palavra completa
                pattern = r'\b' + re.escape(english) + r'\b'
                matches = list(re.finditer(pattern, content, re.IGNORECASE))
                
                for match in reversed(matches):  # Reverso para não afetar posições
                    # Obter contexto da linha
                    line_start = content.rfind('\n', 0, match.start()) + 1
                    line_end = content.find('\n', match.end())
                    if line_end == -1:
                        line_end = len(content)
                    
                    context_line = content[line_start:line_end]
                    
                    if self.is_safe_to_translate(context_line, match.group()):
                        # Preservar capitalização original
                        original_word = match.group()
                        translated_word = portuguese
                        
                        # Se original está em maiúscula, manter maiúscula
                        if original_word.isupper():
                            translated_word = translated_word.upper()
                        elif original_word[0].isupper():
                            translated_word = translated_word.capitalize()
                            
                        content = content[:match.start()] + translated_word + content[match.end():]
                        translations_made.append({
                            'original': original_word,
                            'translation': translated_word,
                            'context': context_line.strip()[:100] + '...' if len(context_line.strip()) > 100 else context_line.strip()
                        })
            
            # Salvar apenas se houve mudanças
            if content != original_content:
                with open(file_path, 'w', encoding='utf-8') as f:
                    f.write(content)
                
                return {
                    'file': os.path.basename(file_path),
                    'translations_count': len(translations_made),
                    'translations': translations_made
                }
            else:
                return {
                    'file': os.path.basename(file_path),
                    'translations_count': 0,
                    'translations': []
                }
                
        except Exception as e:
            print(f"❌ Erro ao traduzir {file_path}: {e}")
            return None
    
    def translate_all_files(self, file_limit=None):
        """Traduz todos os arquivos HTML"""
        if not os.path.exists(self.html_dir):
            print(f"❌ Diretório {self.html_dir} não encontrado!")
            return
        
        # Criar backup
        backup_path = self.create_backup()
        if not backup_path:
            print("❌ Falha ao criar backup. Abortando tradução.")
            return
        
        # Buscar arquivos HTML
        html_files = glob.glob(os.path.join(self.html_dir, "*.html"))
        
        if file_limit:
            html_files = html_files[:file_limit]
        
        print(f"🔄 Iniciando tradução de {len(html_files)} arquivos HTML...")
        print("=" * 60)
        
        results = []
        total_translations = 0
        
        for i, html_file in enumerate(html_files, 1):
            print(f"📄 [{i:3d}/{len(html_files)}] Traduzindo: {os.path.basename(html_file)}")
            
            result = self.translate_file(html_file)
            if result:
                results.append(result)
                total_translations += result['translations_count']
                
                if result['translations_count'] > 0:
                    print(f"    ✅ {result['translations_count']} traduções aplicadas")
                else:
                    print(f"    ℹ️  Nenhuma tradução necessária")
        
        # Salvar log detalhado
        log_data = {
            'timestamp': datetime.now().isoformat(),
            'backup_path': backup_path,
            'total_files': len(html_files),
            'total_translations': total_translations,
            'results': results
        }
        
        with open(self.log_file, 'w', encoding='utf-8') as f:
            json.dump(log_data, f, ensure_ascii=False, indent=2)
        
        # Resumo final
        print("=" * 60)
        print("🎉 TRADUÇÃO CONCLUÍDA!")
        print(f"📊 Arquivos processados: {len(html_files)}")
        print(f"🔧 Total de traduções: {total_translations}")
        print(f"📁 Backup salvo em: {backup_path}")
        print(f"📋 Log detalhado: {self.log_file}")
        
        # Top 10 arquivos com mais traduções
        sorted_results = sorted([r for r in results if r['translations_count'] > 0], 
                               key=lambda x: x['translations_count'], reverse=True)
        
        if sorted_results:
            print("\n🏆 TOP 10 ARQUIVOS COM MAIS TRADUÇÕES:")
            for i, result in enumerate(sorted_results[:10], 1):
                print(f"   {i:2d}. {result['file']}: {result['translations_count']} traduções")
        
        return results
    
    def preview_translations(self, file_path, max_preview=10):
        """Mostra preview das traduções que serão feitas em um arquivo"""
        try:
            with open(file_path, 'r', encoding='utf-8') as f:
                content = f.read()
            
            print(f"\n🔍 PREVIEW DE TRADUÇÕES: {os.path.basename(file_path)}")
            print("-" * 50)
            
            preview_count = 0
            
            # Preview de padrões especiais
            for pattern, translation in self.special_patterns.items():
                matches = list(re.finditer(pattern, content, re.IGNORECASE))
                for match in matches:
                    if preview_count >= max_preview:
                        break
                        
                    line_start = content.rfind('\n', 0, match.start()) + 1
                    line_end = content.find('\n', match.end())
                    if line_end == -1:
                        line_end = len(content)
                    
                    context_line = content[line_start:line_end].strip()
                    
                    if self.is_safe_to_translate(context_line, match.group()):
                        print(f"  '{match.group()}' → '{translation}'")
                        print(f"    Contexto: {context_line[:80]}...")
                        preview_count += 1
                
                if preview_count >= max_preview:
                    break
            
            # Preview de palavras individuais
            for english, portuguese in list(self.all_translations.items())[:20]:  # Limitar para não poluir
                if preview_count >= max_preview:
                    break
                    
                pattern = r'\b' + re.escape(english) + r'\b'
                matches = list(re.finditer(pattern, content, re.IGNORECASE))
                
                for match in matches[:2]:  # Max 2 exemplos por palavra
                    if preview_count >= max_preview:
                        break
                        
                    line_start = content.rfind('\n', 0, match.start()) + 1
                    line_end = content.find('\n', match.end())
                    if line_end == -1:
                        line_end = len(content)
                    
                    context_line = content[line_start:line_end].strip()
                    
                    if self.is_safe_to_translate(context_line, match.group()):
                        print(f"  '{match.group()}' → '{portuguese}'")
                        print(f"    Contexto: {context_line[:80]}...")
                        preview_count += 1
            
            if preview_count == 0:
                print("  ✅ Nenhuma tradução necessária encontrada")
            elif preview_count >= max_preview:
                print(f"  ... e mais traduções (limitado a {max_preview} para preview)")
                
        except Exception as e:
            print(f"❌ Erro ao fazer preview de {file_path}: {e}")


def main():
    print("🌍 DURALUX - TRADUTOR AUTOMÁTICO EM MASSA")
    print("=" * 60)
    
    translator = DuraluxTranslator()
    
    while True:
        print("\n📋 OPÇÕES DISPONÍVEIS:")
        print("1. 🔍 Preview de traduções (testar em 1 arquivo)")
        print("2. 🤖 Tradução automática completa (todos os arquivos)")
        print("3. 🎯 Tradução limitada (apenas top 10 arquivos)")
        print("4. 📊 Mostrar estatísticas de palavras em inglês")
        print("5. ❌ Sair")
        
        choice = input("\n🎯 Escolha uma opção (1-5): ").strip()
        
        if choice == "1":
            # Preview em arquivo específico
            files = glob.glob(os.path.join(translator.html_dir, "*.html"))
            if files:
                print(f"\n📁 Arquivos disponíveis:")
                for i, f in enumerate(files[:10], 1):
                    print(f"  {i}. {os.path.basename(f)}")
                
                try:
                    file_idx = int(input("Escolha um arquivo (número): ")) - 1
                    if 0 <= file_idx < len(files):
                        translator.preview_translations(files[file_idx])
                    else:
                        print("❌ Número inválido")
                except ValueError:
                    print("❌ Por favor digite um número")
            else:
                print("❌ Nenhum arquivo HTML encontrado")
        
        elif choice == "2":
            confirm = input("⚠️  Isso irá traduzir TODOS os arquivos HTML. Continuar? (s/N): ")
            if confirm.lower() == 's':
                translator.translate_all_files()
            else:
                print("❌ Operação cancelada")
        
        elif choice == "3":
            confirm = input("🎯 Traduzir apenas os top 10 arquivos mais problemáticos? (s/N): ")
            if confirm.lower() == 's':
                translator.translate_all_files(file_limit=10)
            else:
                print("❌ Operação cancelada")
        
        elif choice == "4":
            print("📊 Esta opção executará o script de análise...")
            os.system("python backend/check-english-pages.py")
        
        elif choice == "5":
            print("👋 Saindo do tradutor automático...")
            break
        
        else:
            print("❌ Opção inválida. Tente novamente.")


if __name__ == "__main__":
    main()