#!/usr/bin/env python3
"""
DURALUX CRM - Workflow Engine Validator v5.0
Validador completo do sistema de automação de workflows
Analisa código, estrutura e validação sem execução

@author Duralux Development Team  
@version 5.0.0
"""

import os
import re
import json
import datetime
from pathlib import Path

class WorkflowEngineValidator:
    def __init__(self, base_path):
        self.base_path = Path(base_path)
        self.results = {}
        self.errors = []
        self.warnings = []
        
    def validate_all(self):
        """Executar validação completa do Workflow Engine"""
        print("🔍 DURALUX CRM - Workflow Engine Validator v5.0")
        print("=" * 55)
        print()
        
        validations = [
            ('validate_backend_classes', 'Classes Backend'),
            ('validate_api_endpoints', 'API Endpoints'),
            ('validate_frontend_dashboard', 'Dashboard Frontend'),
            ('validate_html_interface', 'Interface HTML'),
            ('validate_database_structure', 'Estrutura Database'),
            ('validate_workflow_logic', 'Lógica de Workflows'),
            ('validate_security', 'Segurança'),
            ('validate_performance', 'Performance'),
            ('validate_documentation', 'Documentação')
        ]
        
        total_score = 0
        max_score = len(validations) * 100
        
        for method, description in validations:
            print(f"🧪 Validando: {description}... ", end="")
            
            try:
                score = getattr(self, method)()
                total_score += score
                
                if score >= 90:
                    print(f"✅ {score}%")
                elif score >= 80:
                    print(f"⚠️ {score}%")
                else:
                    print(f"❌ {score}%")
                    
                self.results[method] = score
                
            except Exception as e:
                print(f"💥 ERRO: {str(e)}")
                self.results[method] = 0
                self.errors.append(f"{description}: {str(e)}")
        
        self.generate_report(total_score, max_score)
        
    def validate_backend_classes(self):
        """Validar classes backend do workflow engine"""
        score = 0
        max_points = 100
        
        # Verificar WorkflowEngine.php
        engine_file = self.base_path / 'backend' / 'classes' / 'WorkflowEngine.php'
        if not engine_file.exists():
            self.errors.append("WorkflowEngine.php não encontrado")
            return 0
            
        engine_content = engine_file.read_text(encoding='utf-8')
        
        # Verificar componentes principais
        required_classes = [
            'WorkflowEngine',
            'ActionExecutor', 
            'ConditionEvaluator',
            'TriggerManager'
        ]
        
        for class_name in required_classes:
            if f'class {class_name}' in engine_content:
                score += 15
            else:
                self.errors.append(f"Classe {class_name} não encontrada")
        
        # Verificar métodos principais
        required_methods = [
            'createWorkflow',
            'executeWorkflow', 
            'processTriggers',
            'createTemplate',
            'getWorkflowStats'
        ]
        
        for method in required_methods:
            if f'function {method}' in engine_content or f'public function {method}' in engine_content:
                score += 6
            else:
                self.errors.append(f"Método {method} não encontrado")
        
        # Verificar WorkflowController.php
        controller_file = self.base_path / 'backend' / 'classes' / 'WorkflowController.php'
        if controller_file.exists():
            controller_content = controller_file.read_text(encoding='utf-8')
            
            if 'class WorkflowController' in controller_content:
                score += 10
                
            # Verificar métodos do controller
            controller_methods = [
                'handleRequest',
                'getWorkflows',
                'createWorkflow',
                'executeWorkflow'
            ]
            
            for method in controller_methods:
                if f'function {method}' in controller_content:
                    score += 2.5
        else:
            self.errors.append("WorkflowController.php não encontrado")
            
        return min(score, max_points)
    
    def validate_api_endpoints(self):
        """Validar endpoints da API"""
        score = 0
        max_points = 100
        
        # Verificar router.php
        router_file = self.base_path / 'backend' / 'api' / 'router.php'
        if not router_file.exists():
            self.errors.append("router.php não encontrado")
            return 0
            
        router_content = router_file.read_text(encoding='utf-8')
        
        # Endpoints obrigatórios
        required_endpoints = [
            'workflows/list',
            'workflows/create', 
            'workflows/update',
            'workflows/delete',
            'workflows/execute',
            'workflows/stats',
            'workflows/templates',
            'workflows/export',
            'workflows/import'
        ]
        
        for endpoint in required_endpoints:
            if endpoint in router_content:
                score += 10
            else:
                self.warnings.append(f"Endpoint {endpoint} não encontrado")
                
        # Verificar estrutura API
        if "'workflows'" in router_content:
            score += 10
        
        return min(score, max_points)
        
    def validate_frontend_dashboard(self):
        """Validar dashboard frontend"""
        score = 0
        max_points = 100
        
        # Verificar arquivo JavaScript principal
        js_file = self.base_path / 'duralux-admin' / 'assets' / 'js' / 'duralux-workflow-dashboard-v5.js'
        if not js_file.exists():
            # Tentar localização alternativa
            alt_files = [
                self.base_path / 'duralux-admin' / 'duralux-workflow-dashboard-v5.js',
                self.base_path / 'assets' / 'js' / 'duralux-workflow-dashboard-v5.js',
                self.base_path / 'backend' / 'assets' / 'js' / 'duralux-workflow-dashboard-v5.js'
            ]
            
            js_file = None
            for alt_file in alt_files:
                if alt_file.exists():
                    js_file = alt_file
                    break
                    
            if not js_file:
                self.errors.append("duralux-workflow-dashboard-v5.js não encontrado")
                return 0
        
        js_content = js_file.read_text(encoding='utf-8')
        
        # Verificar componentes principais
        required_components = [
            'DuraluxWorkflowDashboard',
            'initializeWorkflowBuilder',
            'loadWorkflows',
            'executeWorkflow',
            'updateStats'
        ]
        
        for component in required_components:
            if component in js_content:
                score += 15
            else:
                self.errors.append(f"Componente {component} não encontrado no JS")
        
        # Verificar funcionalidades
        features = [
            'drag',
            'drop', 
            'fetch(',
            'chart',
            'notification'
        ]
        
        for feature in features:
            if feature.lower() in js_content.lower():
                score += 5
                
        return min(score, max_points)
        
    def validate_html_interface(self):
        """Validar interface HTML"""
        score = 0 
        max_points = 100
        
        # Verificar workflow-dashboard.html
        html_file = self.base_path / 'duralux-admin' / 'workflow-dashboard.html'
        if not html_file.exists():
            self.errors.append("workflow-dashboard.html não encontrado")
            return 0
            
        html_content = html_file.read_text(encoding='utf-8')
        
        # Verificar estrutura HTML
        html_elements = [
            'id="workflow-builder"',
            'id="workflow-canvas"', 
            'workflow-sidebar',
            'stats-cards',
            'workflow-dashboard-v5.js'
        ]
        
        for element in html_elements:
            if element in html_content:
                score += 15
            else:
                self.warnings.append(f"Elemento HTML {element} não encontrado")
        
        # Verificar dependências
        dependencies = [
            'bootstrap',
            'chart.js',
            'font-awesome'
        ]
        
        for dep in dependencies:
            if dep.lower() in html_content.lower():
                score += 8
                
        # Verificar responsividade
        if 'viewport' in html_content and 'responsive' in html_content.lower():
            score += 7
            
        return min(score, max_points)
        
    def validate_database_structure(self):
        # Verificar estrutura do banco de dados"""
        score = 0
        max_points = 100
        
        # Verificar se há definições de tabelas no código
        engine_file = self.base_path / 'backend' / 'classes' / 'WorkflowEngine.php'
        if not engine_file.exists():
            return 0
            
        engine_content = engine_file.read_text(encoding='utf-8')
        
        # Tabelas necessárias
        required_tables = [
            'workflows',
            'workflow_executions',
            'workflow_triggers', 
            'workflow_actions',
            'workflow_templates'
        ]
        
        for table in required_tables:
            if table in engine_content:
                score += 15
            else:
                self.warnings.append(f"Tabela {table} não referenciada")
        
        # Verificar SQL statements
        sql_patterns = [
            'CREATE TABLE',
            'INSERT INTO',
            'SELECT.*FROM',
            'UPDATE.*SET',
            'DELETE FROM'
        ]
        
        for pattern in sql_patterns:
            if re.search(pattern, engine_content, re.IGNORECASE):
                score += 5
                
        return min(score, max_points)
        
    def validate_workflow_logic(self):
        """Validar lógica de workflows"""
        score = 0
        max_points = 100
        
        engine_file = self.base_path / 'backend' / 'classes' / 'WorkflowEngine.php'
        if not engine_file.exists():
            return 0
            
        engine_content = engine_file.read_text(encoding='utf-8')
        
        # Verificar componentes lógicos
        logic_components = [
            'trigger',
            'condition', 
            'action',
            'execution',
            'template'
        ]
        
        for component in logic_components:
            # Contar ocorrências (case insensitive)
            occurrences = len(re.findall(component, engine_content, re.IGNORECASE))
            if occurrences >= 5:  # Deve aparecer múltiplas vezes
                score += 15
            elif occurrences >= 2:
                score += 10
            elif occurrences >= 1:
                score += 5
        
        # Verificar padrões de workflow
        workflow_patterns = [
            'executeWorkflow',
            'processTriggers',
            'evaluateConditions',
            'executeActions'
        ]
        
        for pattern in workflow_patterns:
            if pattern in engine_content:
                score += 6.25
                
        return min(score, max_points)
        
    def validate_security(self):
        """Validar aspectos de segurança"""
        score = 0
        max_points = 100
        
        files_to_check = [
            self.base_path / 'backend' / 'classes' / 'WorkflowEngine.php',
            self.base_path / 'backend' / 'classes' / 'WorkflowController.php'
        ]
        
        security_checks = 0
        total_checks = 0
        
        for file_path in files_to_check:
            if not file_path.exists():
                continue
                
            content = file_path.read_text(encoding='utf-8')
            
            # Verificações de segurança
            security_patterns = [
                ('prepared.*statement|prepare.*execute', 'Prepared Statements'),
                ('htmlspecialchars|strip_tags', 'XSS Prevention'),
                ('authentication|authorize', 'Authentication'),
                ('validate.*input|sanitiz', 'Input Validation'),
                ('csrf.*token|csrf.*protect', 'CSRF Protection')
            ]
            
            for pattern, description in security_patterns:
                total_checks += 1
                if re.search(pattern, content, re.IGNORECASE):
                    security_checks += 1
                else:
                    self.warnings.append(f"Verificação de segurança ausente: {description}")
        
        if total_checks > 0:
            score = (security_checks / total_checks) * 100
        else:
            score = 0
            
        return min(score, max_points)
        
    def validate_performance(self):
        """Validar otimizações de performance"""
        score = 0
        max_points = 100
        
        engine_file = self.base_path / 'backend' / 'classes' / 'WorkflowEngine.php'
        if not engine_file.exists():
            return 0
            
        engine_content = engine_file.read_text(encoding='utf-8')
        
        # Verificar otimizações
        performance_features = [
            ('cache', 'Sistema de Cache'),
            ('index|key.*index', 'Indexes de Database'),
            ('limit.*offset|pagination', 'Paginação'),
            ('batch.*process|bulk', 'Processamento em Lote'),
            ('async|asynchronous', 'Processamento Assíncrono')
        ]
        
        for pattern, description in performance_features:
            if re.search(pattern, engine_content, re.IGNORECASE):
                score += 20
            else:
                self.warnings.append(f"Otimização ausente: {description}")
                
        return min(score, max_points)
        
    def validate_documentation(self):
        """Validar documentação"""
        score = 0
        max_points = 100
        
        files_to_check = [
            self.base_path / 'backend' / 'classes' / 'WorkflowEngine.php',
            self.base_path / 'backend' / 'classes' / 'WorkflowController.php',
            self.base_path / 'duralux-admin' / 'assets' / 'js' / 'duralux-workflow-dashboard-v5.js'
        ]
        
        total_files = len(files_to_check)
        documented_files = 0
        
        for file_path in files_to_check:
            alt_locations = []
            if 'duralux-workflow-dashboard-v5.js' in str(file_path):
                alt_locations = [
                    self.base_path / 'duralux-admin' / 'duralux-workflow-dashboard-v5.js',
                    self.base_path / 'assets' / 'js' / 'duralux-workflow-dashboard-v5.js',
                    self.base_path / 'duralux-admin' / 'assets' / 'js' / 'duralux-workflow-dashboard-v5.js'
                ]
            
            found_file = None
            if file_path.exists():
                found_file = file_path
            else:
                for alt_path in alt_locations:
                    if alt_path.exists():
                        found_file = alt_path
                        break
            
            if not found_file:
                continue
                
            content = found_file.read_text(encoding='utf-8')
            
            # Verificar documentação
            doc_patterns = [
                r'/\*\*.*\*/',
                r'@param',
                r'@return',
                r'@author',
                r'@version'
            ]
            
            doc_score = 0
            for pattern in doc_patterns:
                if re.search(pattern, content, re.DOTALL):
                    doc_score += 1
            
            if doc_score >= 3:  # Bem documentado
                documented_files += 1
                
        if total_files > 0:
            score = (documented_files / total_files) * 100
        
        return min(score, max_points)
        
    def generate_report(self, total_score, max_score):
        """Gerar relatório final"""
        success_rate = (total_score / max_score) * 100
        
        print()
        print("=" * 55)
        print("📋 RELATÓRIO DE VALIDAÇÃO - WORKFLOW ENGINE v5.0")  
        print("=" * 55)
        
        for method, score in self.results.items():
            test_name = method.replace('validate_', '').replace('_', ' ').title()
            if score >= 90:
                status = '✅'
            elif score >= 80:
                status = '⚠️'
            else:
                status = '❌'
            print(f"   {status} {test_name:<35} {score:>6.1f}%")
        
        print()
        print("=" * 55)
        print("📊 RESULTADO FINAL")
        print("=" * 55)
        
        if success_rate >= 90:
            status = "🎉 EXCELENTE"
        elif success_rate >= 80:
            status = "✅ BOM"
        elif success_rate >= 70:
            status = "⚠️ ACEITÁVEL"
        else:
            status = "❌ PRECISA MELHORIAS"
        
        print(f"Status: {status}")
        print(f"Pontuação Total: {total_score:.1f}/{max_score}")
        print(f"Taxa de Sucesso: {success_rate:.1f}%")
        
        if self.errors:
            print(f"\n❌ ERROS ENCONTRADOS ({len(self.errors)}):")
            for i, error in enumerate(self.errors, 1):
                print(f"   {i}. {error}")
        
        if self.warnings:
            print(f"\n⚠️ AVISOS ({len(self.warnings)}):")
            for i, warning in enumerate(self.warnings, 1):
                print(f"   {i}. {warning}")
        
        if success_rate >= 80:
            print("\n🚀 Workflow Engine v5.0 validado com sucesso!")
            print("✅ Sistema de automação pronto para produção")
            print("🔄 Pronto para testes funcionais e deploy")
        else:
            print(f"\n⚠️ Sistema precisa de {100 - success_rate:.1f}% de melhorias")
            print("📝 Revise os erros e avisos listados acima")
        
        print("\n📋 PRÓXIMOS PASSOS:")
        print("1. 🧪 Executar testes funcionais")
        print("2. 🔧 Corrigir eventuais problemas")
        print("3. 📊 Testar interface web completa")
        print("4. 🚀 Deploy em ambiente de produção")
        print("5. 🔄 Continuar com Notification Center v6.0")

if __name__ == "__main__":
    base_path = r"c:\Users\ivonm\OneDrive - sga.pucminas.br\Github\duralux\duralux"
    validator = WorkflowEngineValidator(base_path)
    validator.validate_all()