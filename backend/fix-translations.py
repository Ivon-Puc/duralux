import os
import re

def fix_translation_errors():
    """Corrige erros de tradução duplicados"""
    
    # Correções necessárias
    fixes = {
        "Exportararar": "Exportar",
        "Exportarararar": "Exportar", 
        "Exportarar": "Exportar",
        "enableExportarar": "enableExport"
    }
    
    files_fixed = 0
    total_fixes = 0
    
    admin_path = "../duralux-admin"
    
    print("🔧 Corrigindo erros de tradução...")
    
    for root, dirs, files in os.walk(admin_path):
        for file in files:
            if file.endswith('.html'):
                file_path = os.path.join(root, file)
                
                try:
                    with open(file_path, 'r', encoding='utf-8') as f:
                        content = f.read()
                    
                    original_content = content
                    
                    # Aplicar correções
                    for wrong, correct in fixes.items():
                        if wrong in content:
                            content = content.replace(wrong, correct)
                            total_fixes += 1
                            print(f"  ✅ {file}: '{wrong}' → '{correct}'")
                    
                    # Salvar se houve mudanças
                    if content != original_content:
                        with open(file_path, 'w', encoding='utf-8') as f:
                            f.write(content)
                        files_fixed += 1
                        
                except Exception as e:
                    print(f"  ❌ Erro em {file}: {e}")
    
    print(f"\n📊 Resultados:")
    print(f"   📁 Arquivos corrigidos: {files_fixed}")
    print(f"   🔄 Correções realizadas: {total_fixes}")

if __name__ == "__main__":
    print("🚀 DURALUX - Corretor de Traduções")
    print("=" * 40)
    
    fix_translation_errors()
    
    print("✅ Correções concluídas!")