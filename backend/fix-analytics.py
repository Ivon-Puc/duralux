#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script para corrigir problemas específicos no analytics-advanced.html
"""

import os

def fix_analytics_file():
    file_path = "duralux-admin/analytics-advanced.html"
    
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Corrigir analyticsDate para analyticsData
        content = content.replace('analyticsDate.', 'analyticsData.')
        
        # Corrigir também problema no Date.setDate
        content = content.replace('Date.setDate(Date.getDate()', 'date.setDate(date.getDate()')
        
        with open(file_path, 'w', encoding='utf-8') as f:
            f.write(content)
        
        print("✅ Arquivo analytics-advanced.html corrigido com sucesso!")
        print("🔧 Correções aplicadas:")
        print("   - analyticsDate → analyticsData")
        print("   - Date.setDate → date.setDate")
        
    except Exception as e:
        print(f"❌ Erro: {str(e)}")

if __name__ == "__main__":
    fix_analytics_file()