import os
import re
import json

def translate_final_strings():
    """Traduz strings específicas em inglês que ainda restam no projeto"""
    
    # Dicionário de traduções específicas para elementos restantes
    translations = {
        "CRM dashboard redesign": "Redesign do painel CRM",
        "dashboard": "painel de controle",
        "Dashboard": "Painel de Controle",
        "DASHBOARD": "PAINEL DE CONTROLE",
        "Grand Total": "Total Geral",
        "Grand total": "Total geral",
        "Grand total invoice": "Total geral da fatura",
        "Grand total proposal": "Total geral da proposta",
        "Total Storage": "Armazenamento Total",
        "Free space": "Espaço livre",
        "Total Email": "Total de E-mails"
    }
    
    # Estatísticas
    files_changed = 0
    total_replacements = 0
    
    # Buscar em arquivos HTML
    admin_path = "../duralux-admin"
    
    print("🔍 Buscando strings em inglês restantes...")
    
    for root, dirs, files in os.walk(admin_path):
        for file in files:
            if file.endswith('.html'):
                file_path = os.path.join(root, file)
                
                try:
                    with open(file_path, 'r', encoding='utf-8') as f:
                        content = f.read()
                    
                    original_content = content
                    
                    # Aplicar traduções
                    for english, portuguese in translations.items():
                        if english in content:
                            content = content.replace(english, portuguese)
                            total_replacements += 1
                            print(f"  ✅ {file}: '{english}' → '{portuguese}'")
                    
                    # Salvar se houve mudanças
                    if content != original_content:
                        with open(file_path, 'w', encoding='utf-8') as f:
                            f.write(content)
                        files_changed += 1
                        
                except Exception as e:
                    print(f"  ❌ Erro em {file}: {e}")
    
    print(f"\n📊 Resultados:")
    print(f"   📁 Arquivos modificados: {files_changed}")
    print(f"   🔄 Substituições realizadas: {total_replacements}")
    
    return files_changed, total_replacements

if __name__ == "__main__":
    print("🚀 DURALUX - Tradutor Final PT-BR")
    print("=" * 40)
    
    files_changed, total_replacements = translate_final_strings()
    
    if total_replacements > 0:
        print("✅ Tradução final concluída!")
    else:
        print("ℹ️ Nenhuma string em inglês encontrada.")