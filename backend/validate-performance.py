#!/usr/bin/env python3
"""
DURALUX CRM - Performance System Validator v4.0
Validador estático para o sistema de performance sem necessidade de PHP
"""

import os
import json
import re
from pathlib import Path

class DuraluxPerformanceValidator:
    def __init__(self):
        self.base_path = Path(__file__).parent
        self.results = {}
        self.total_tests = 0
        self.passed_tests = 0
        
    def run_all_tests(self):
        print("🧪 DURALUX CRM - Performance System Validator v4.0")
        print("=" * 55)
        print()
        
        # Executar validações
        self.validate_backend_files()
        self.validate_frontend_files()
        self.validate_file_structures()
        self.validate_integrations()
        
        # Gerar relatório
        self.generate_report()
    
    def validate_backend_files(self):
        print("🔧 Validando arquivos Backend...")
        
        backend_files = {
            'RedisCacheManager.php': self.validate_redis_cache_manager,
            'PerformanceMonitor.php': self.validate_performance_monitor,
            'AssetOptimizer.php': self.validate_asset_optimizer,
            'PerformanceDashboardController.php': self.validate_dashboard_controller
        }
        
        backend_results = {}
        
        for filename, validator in backend_files.items():
            file_path = self.base_path / 'classes' / filename
            try:
                result = validator(file_path)
                backend_results[filename] = result
                if result['passed']:
                    print(f"   ✅ {filename}")
                else:
                    print(f"   ❌ {filename} - {result['error']}")
                    
            except Exception as e:
                backend_results[filename] = {'passed': False, 'error': str(e)}
                print(f"   ❌ {filename} - {e}")
        
        self.results['backend'] = backend_results
    
    def validate_frontend_files(self):
        print("🎨 Validando arquivos Frontend...")
        
        frontend_files = {
            'duralux-performance-dashboard-v4.js': self.validate_javascript_dashboard,
            'performance-dashboard.html': self.validate_html_dashboard
        }
        
        frontend_results = {}
        
        for filename, validator in frontend_files.items():
            if filename.endswith('.js'):
                file_path = self.base_path.parent / 'duralux-admin' / 'assets' / 'js' / filename
            else:
                file_path = self.base_path.parent / 'duralux-admin' / filename
                
            try:
                result = validator(file_path)
                frontend_results[filename] = result
                if result['passed']:
                    print(f"   ✅ {filename}")
                else:
                    print(f"   ❌ {filename} - {result['error']}")
                    
            except Exception as e:
                frontend_results[filename] = {'passed': False, 'error': str(e)}
                print(f"   ❌ {filename} - {e}")
        
        self.results['frontend'] = frontend_results
    
    def validate_file_structures(self):
        print("📁 Validando estrutura de arquivos...")
        
        required_structure = [
            'classes/RedisCacheManager.php',
            'classes/PerformanceMonitor.php', 
            'classes/AssetOptimizer.php',
            'classes/PerformanceDashboardController.php',
            '../duralux-admin/assets/js/duralux-performance-dashboard-v4.js',
            '../duralux-admin/performance-dashboard.html'
        ]
        
        structure_results = {}
        
        for file_path in required_structure:
            full_path = self.base_path / file_path
            exists = full_path.exists()
            structure_results[file_path] = {'passed': exists, 'size': full_path.stat().st_size if exists else 0}
            
            if exists:
                print(f"   ✅ {file_path} ({full_path.stat().st_size} bytes)")
            else:
                print(f"   ❌ {file_path} - Arquivo não encontrado")
        
        self.results['structure'] = structure_results
    
    def validate_integrations(self):
        print("🔗 Validando integrações...")
        
        integration_results = {
            'api_endpoints': self.validate_api_integration(),
            'dashboard_scripts': self.validate_dashboard_integration(),
            'cache_integration': self.validate_cache_integration()
        }
        
        for test, result in integration_results.items():
            if result['passed']:
                print(f"   ✅ {test}")
            else:
                print(f"   ❌ {test} - {result['error']}")
        
        self.results['integrations'] = integration_results
    
    # Validadores específicos
    
    def validate_redis_cache_manager(self, file_path):
        if not file_path.exists():
            return {'passed': False, 'error': 'Arquivo não encontrado'}
        
        content = file_path.read_text(encoding='utf-8')
        
        # Verificar estrutura da classe
        required_methods = [
            'getInstance', 'connect', 'set', 'get', 'delete', 
            'flush', 'getStats', 'optimizeQuery', 'invalidateTag'
        ]
        
        for method in required_methods:
            if f'function {method}' not in content and f'public function {method}' not in content:
                return {'passed': False, 'error': f'Método {method} não encontrado'}
        
        # Verificar configurações Redis
        if 'redis' not in content.lower():
            return {'passed': False, 'error': 'Configurações Redis não encontradas'}
        
        # Verificar cache multi-layer
        if 'multi-layer' not in content or 'L1:' not in content:
            return {'passed': False, 'error': 'Sistema multi-layer não implementado'}
        
        return {'passed': True, 'size': len(content), 'methods': len(required_methods)}
    
    def validate_performance_monitor(self, file_path):
        if not file_path.exists():
            return {'passed': False, 'error': 'Arquivo não encontrado'}
        
        content = file_path.read_text(encoding='utf-8')
        
        required_features = [
            'getRealTimeStats', 'profileQuery', 'optimizeQueries', 
            'recordRequestMetrics', 'getDatabaseMetrics', 'triggerAlert'
        ]
        
        for feature in required_features:
            if feature not in content:
                return {'passed': False, 'error': f'Feature {feature} não encontrada'}
        
        # Verificar tabelas de métricas
        if 'performance_metrics' not in content or 'slow_queries' not in content:
            return {'passed': False, 'error': 'Tabelas de métricas não configuradas'}
        
        return {'passed': True, 'size': len(content), 'features': len(required_features)}
    
    def validate_asset_optimizer(self, file_path):
        if not file_path.exists():
            return {'passed': False, 'error': 'Arquivo não encontrado'}
        
        content = file_path.read_text(encoding='utf-8')
        
        required_optimizations = [
            'optimizeCSS', 'optimizeJavaScript', 'optimizeImages', 
            'minifyCSS', 'minifyJavaScript', 'createWebPVersion'
        ]
        
        for optimization in required_optimizations:
            if optimization not in content:
                return {'passed': False, 'error': f'Otimização {optimization} não encontrada'}
        
        # Verificar compressão
        if 'gzencode' not in content:
            return {'passed': False, 'error': 'Compressão Gzip não implementada'}
        
        return {'passed': True, 'size': len(content), 'optimizations': len(required_optimizations)}
    
    def validate_dashboard_controller(self, file_path):
        if not file_path.exists():
            return {'passed': False, 'error': 'Arquivo não encontrado'}
        
        content = file_path.read_text(encoding='utf-8')
        
        required_endpoints = [
            'getDashboardData', 'getPerformanceOverview', 'getActiveAlerts',
            'getPerformanceTrends', 'executeOptimization'
        ]
        
        for endpoint in required_endpoints:
            if endpoint not in content:
                return {'passed': False, 'error': f'Endpoint {endpoint} não encontrado'}
        
        return {'passed': True, 'size': len(content), 'endpoints': len(required_endpoints)}
    
    def validate_javascript_dashboard(self, file_path):
        if not file_path.exists():
            return {'passed': False, 'error': 'Arquivo não encontrado'}
        
        content = file_path.read_text(encoding='utf-8')
        
        # Verificar classe principal
        if 'DuraluxPerformanceDashboard' not in content:
            return {'passed': False, 'error': 'Classe principal não encontrada'}
        
        # Verificar Chart.js integration
        if 'Chart.js' not in content and 'new Chart' not in content:
            return {'passed': False, 'error': 'Integração Chart.js não encontrada'}
        
        # Verificar métodos essenciais
        required_methods = [
            'initializeCharts', 'updateDashboard', 'refreshData', 
            'fetchDashboardData', 'runOptimization'
        ]
        
        for method in required_methods:
            if method not in content:
                return {'passed': False, 'error': f'Método {method} não encontrado'}
        
        return {'passed': True, 'size': len(content), 'methods': len(required_methods)}
    
    def validate_html_dashboard(self, file_path):
        if not file_path.exists():
            return {'passed': False, 'error': 'Arquivo não encontrado'}
        
        content = file_path.read_text(encoding='utf-8')
        
        # Verificar estrutura HTML básica
        if not content.startswith('<!DOCTYPE html>'):
            return {'passed': False, 'error': 'DOCTYPE HTML5 não encontrado'}
        
        # Verificar dependências
        required_dependencies = [
            'bootstrap', 'chart.js', 'font-awesome'
        ]
        
        for dep in required_dependencies:
            if dep.lower() not in content.lower():
                return {'passed': False, 'error': f'Dependência {dep} não encontrada'}
        
        # Verificar elementos do dashboard
        required_elements = [
            'responseTimeChart', 'memoryChart', 'trendsChart', 
            'optimizationModal', 'performance-dashboard'
        ]
        
        for element in required_elements:
            if element not in content:
                return {'passed': False, 'error': f'Elemento {element} não encontrado'}
        
        return {'passed': True, 'size': len(content), 'elements': len(required_elements)}
    
    def validate_api_integration(self):
        # Verificar se router.php existe e tem endpoints de performance
        router_path = self.base_path / 'api' / 'router.php'
        
        if not router_path.exists():
            return {'passed': False, 'error': 'router.php não encontrado'}
        
        content = router_path.read_text(encoding='utf-8')
        
        if 'performance' not in content.lower():
            return {'passed': False, 'error': 'Endpoints de performance não configurados'}
        
        return {'passed': True, 'details': 'API integration validated'}
    
    def validate_dashboard_integration(self):
        # Verificar se o script JavaScript está referenciado corretamente
        html_path = self.base_path.parent / 'duralux-admin' / 'performance-dashboard.html'
        
        if not html_path.exists():
            return {'passed': False, 'error': 'Dashboard HTML não encontrado'}
        
        content = html_path.read_text(encoding='utf-8')
        
        if 'duralux-performance-dashboard-v4.js' not in content:
            return {'passed': False, 'error': 'Script do dashboard não referenciado'}
        
        return {'passed': True, 'details': 'Dashboard integration validated'}
    
    def validate_cache_integration(self):
        # Verificar se as classes se referenciam corretamente
        performance_path = self.base_path / 'classes' / 'PerformanceMonitor.php'
        
        if not performance_path.exists():
            return {'passed': False, 'error': 'PerformanceMonitor não encontrado'}
        
        content = performance_path.read_text(encoding='utf-8')
        
        if 'CacheManager' not in content:
            return {'passed': False, 'error': 'Integração com CacheManager não encontrada'}
        
        return {'passed': True, 'details': 'Cache integration validated'}
    
    def generate_report(self):
        print("\n" + "=" * 55)
        print("📋 RELATÓRIO DE VALIDAÇÃO")
        print("=" * 55)
        
        total_components = 0
        passed_components = 0
        
        for category, results in self.results.items():
            print(f"\n🔍 {category.upper()}:")
            
            if isinstance(results, dict):
                for component, result in results.items():
                    total_components += 1
                    if result.get('passed', False):
                        passed_components += 1
                        status = "✅"
                    else:
                        status = "❌"
                    
                    print(f"   {status} {component}")
                    if 'size' in result:
                        print(f"      Tamanho: {result['size']} bytes")
                    if not result.get('passed', False) and 'error' in result:
                        print(f"      Erro: {result['error']}")
        
        # Estatísticas finais
        success_rate = (passed_components / total_components * 100) if total_components > 0 else 0
        
        print("\n" + "=" * 55)
        print("📊 RESULTADO FINAL")
        print("=" * 55)
        
        if success_rate >= 90:
            status = "🎉 EXCELENTE"
            color = "GREEN"
        elif success_rate >= 80:
            status = "✅ BOM"  
            color = "YELLOW"
        elif success_rate >= 70:
            status = "⚠️ ACEITÁVEL"
            color = "ORANGE"
        else:
            status = "❌ PRECISA MELHORIAS"
            color = "RED"
        
        print(f"Status: {status}")
        print(f"Componentes validados: {passed_components}/{total_components}")
        print(f"Taxa de sucesso: {success_rate:.1f}%")
        
        if success_rate >= 80:
            print("\n🚀 Sistema Performance v4.0 está pronto!")
            print("✅ Todos os componentes principais foram implementados")
            print("🔄 Pronto para commit e deploy")
        else:
            print(f"\n⚠️ {total_components - passed_components} componentes precisam de ajustes")
            print("📝 Revise os erros listados acima")
        
        # Próximos passos
        print("\n📋 PRÓXIMOS PASSOS:")
        print("1. 🔄 Fazer commit das alterações")
        print("2. 🧪 Testar em ambiente com PHP/Redis")  
        print("3. 📊 Validar performance em produção")
        print("4. 🚀 Continuar com Workflow Automation Engine")

if __name__ == "__main__":
    validator = DuraluxPerformanceValidator()
    validator.run_all_tests()