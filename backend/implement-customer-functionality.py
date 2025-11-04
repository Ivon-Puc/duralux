#!/usr/bin/env python3
"""
Script para implementar funcionalidade completa de adicionar cliente
com campos brasileiros e validações
"""

import os
import re

def create_functional_customer_form():
    """Adiciona JavaScript funcional para o formulário de clientes"""
    
    # JavaScript para funcionalidade completa
    javascript_code = '''
        // Sistema de Gerenciamento de Clientes - Duralux CRM
        class CustomerManager {
            constructor() {
                this.customers = [];
                this.currentCustomer = null;
                this.init();
            }
            
            init() {
                this.loadCustomersFromStorage();
                this.setupEventListeners();
                this.setupFormValidation();
                this.setupMasks();
                console.log('🚀 Sistema de Clientes Duralux inicializado');
            }
            
            // Configurar máscaras para campos brasileiros
            setupMasks() {
                // Máscara para telefone brasileiro
                const phoneInput = document.getElementById('phoneInput');
                if (phoneInput) {
                    phoneInput.addEventListener('input', (e) => {
                        let value = e.target.value.replace(/\\D/g, '');
                        if (value.length <= 11) {
                            value = value.replace(/(\\d{2})(\\d{5})(\\d{4})/, '($1) $2-$3');
                        } else {
                            value = value.replace(/(\\d{2})(\\d{4})(\\d{4})/, '($1) $2-$3');
                        }
                        e.target.value = value;
                    });
                }
                
                // Máscara para CPF/CNPJ
                const vatInput = document.getElementById('VATInput');
                if (vatInput) {
                    vatInput.placeholder = 'CPF ou CNPJ';
                    vatInput.addEventListener('input', (e) => {
                        let value = e.target.value.replace(/\\D/g, '');
                        if (value.length <= 11) {
                            // CPF
                            value = value.replace(/(\\d{3})(\\d{3})(\\d{3})(\\d{2})/, '$1.$2.$3-$4');
                        } else {
                            // CNPJ
                            value = value.replace(/(\\d{2})(\\d{3})(\\d{3})(\\d{4})(\\d{2})/, '$1.$2.$3/$4-$5');
                        }
                        e.target.value = value;
                    });
                }
                
                // Máscara para CEP
                const addressInput = document.getElementById('addressInput_2');
                if (addressInput) {
                    addressInput.placeholder = 'Endereço completo com CEP';
                }
            }
            
            // Configurar eventos
            setupEventListeners() {
                // Botão de criar cliente
                const createBtn = document.querySelector('.btn-primary.successAlertMessage');
                if (createBtn) {
                    createBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        this.saveCustomer();
                    });
                }
                
                // Botão de rascunho
                const draftBtn = document.querySelector('.btn-light-brand.successAlertMessage');
                if (draftBtn) {
                    draftBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        this.saveDraft();
                    });
                }
                
                // Preview em tempo real
                const nameInput = document.getElementById('fullnameInput');
                if (nameInput) {
                    nameInput.addEventListener('input', () => this.updatePreview());
                }
                
                // Validação em tempo real
                document.querySelectorAll('input, textarea, select').forEach(input => {
                    input.addEventListener('blur', () => this.validateField(input));
                });
            }
            
            // Configurar validação
            setupFormValidation() {
                // Adicionar indicadores de validação
                document.querySelectorAll('input[required], input[type="email"]').forEach(input => {
                    const group = input.closest('.input-group') || input.parentNode;
                    group.style.position = 'relative';
                });
            }
            
            // Validar campo individual
            validateField(field) {
                const value = field.value.trim();
                let isValid = true;
                let message = '';
                
                // Limpar validações anteriores
                this.clearFieldValidation(field);
                
                switch(field.id) {
                    case 'fullnameInput':
                        isValid = value.length >= 2 && /^[a-zA-ZÀ-ÿ\\s]+$/.test(value);
                        message = isValid ? '' : 'Nome deve ter pelo menos 2 caracteres e conter apenas letras';
                        break;
                        
                    case 'mailInput':
                        const emailRegex = /^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$/;
                        isValid = emailRegex.test(value);
                        message = isValid ? '' : 'Email inválido';
                        break;
                        
                    case 'phoneInput':
                        const phoneRegex = /\\([0-9]{2}\\)\\s[0-9]{4,5}-[0-9]{4}/;
                        isValid = phoneRegex.test(value) || value === '';
                        message = isValid ? '' : 'Telefone inválido. Use o formato (11) 99999-9999';
                        break;
                        
                    case 'VATInput':
                        isValid = this.validateCPFCNPJ(value) || value === '';
                        message = isValid ? '' : 'CPF ou CNPJ inválido';
                        break;
                }
                
                this.showFieldValidation(field, isValid, message);
                return isValid;
            }
            
            // Validar CPF/CNPJ
            validateCPFCNPJ(value) {
                const numbers = value.replace(/\\D/g, '');
                
                if (numbers.length === 11) {
                    return this.validateCPF(numbers);
                } else if (numbers.length === 14) {
                    return this.validateCNPJ(numbers);
                }
                
                return false;
            }
            
            // Validar CPF
            validateCPF(cpf) {
                if (cpf.length !== 11 || /^(\\d)\\1{10}$/.test(cpf)) {
                    return false;
                }
                
                let sum = 0;
                for (let i = 0; i < 9; i++) {
                    sum += parseInt(cpf.charAt(i)) * (10 - i);
                }
                
                let digit = 11 - (sum % 11);
                if (digit === 10 || digit === 11) digit = 0;
                if (digit !== parseInt(cpf.charAt(9))) return false;
                
                sum = 0;
                for (let i = 0; i < 10; i++) {
                    sum += parseInt(cpf.charAt(i)) * (11 - i);
                }
                
                digit = 11 - (sum % 11);
                if (digit === 10 || digit === 11) digit = 0;
                
                return digit === parseInt(cpf.charAt(10));
            }
            
            // Validar CNPJ
            validateCNPJ(cnpj) {
                if (cnpj.length !== 14) return false;
                
                const weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
                const weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
                
                let sum = 0;
                for (let i = 0; i < 12; i++) {
                    sum += parseInt(cnpj.charAt(i)) * weights1[i];
                }
                
                let digit = sum % 11 < 2 ? 0 : 11 - (sum % 11);
                if (digit !== parseInt(cnpj.charAt(12))) return false;
                
                sum = 0;
                for (let i = 0; i < 13; i++) {
                    sum += parseInt(cnpj.charAt(i)) * weights2[i];
                }
                
                digit = sum % 11 < 2 ? 0 : 11 - (sum % 11);
                return digit === parseInt(cnpj.charAt(13));
            }
            
            // Mostrar validação do campo
            showFieldValidation(field, isValid, message) {
                const group = field.closest('.input-group') || field.parentNode;
                
                // Remover classes anteriores
                field.classList.remove('is-valid', 'is-invalid');
                
                // Adicionar nova classe
                field.classList.add(isValid ? 'is-valid' : 'is-invalid');
                
                // Mostrar mensagem se inválido
                if (!isValid && message) {
                    let feedback = group.querySelector('.invalid-feedback');
                    if (!feedback) {
                        feedback = document.createElement('div');
                        feedback.className = 'invalid-feedback';
                        group.appendChild(feedback);
                    }
                    feedback.textContent = message;
                }
            }
            
            // Limpar validação do campo
            clearFieldValidation(field) {
                field.classList.remove('is-valid', 'is-invalid');
                const group = field.closest('.input-group') || field.parentNode;
                const feedback = group.querySelector('.invalid-feedback');
                if (feedback) {
                    feedback.remove();
                }
            }
            
            // Atualizar preview em tempo real
            updatePreview() {
                const name = document.getElementById('fullnameInput').value;
                const email = document.getElementById('mailInput').value;
                
                // Atualizar título da página se necessário
                if (name) {
                    document.title = `Criando Cliente: ${name} - Duralux CRM`;
                }
            }
            
            // Coletar dados do formulário
            collectFormData() {
                const formData = {
                    // Informações pessoais
                    nome: document.getElementById('fullnameInput')?.value || '',
                    email: document.getElementById('mailInput')?.value || '',
                    usuario: document.getElementById('usernameInput')?.value || '',
                    telefone: document.getElementById('phoneInput')?.value || '',
                    empresa: document.getElementById('companyInput')?.value || '',
                    cargo: document.getElementById('designationInput')?.value || '',
                    website: document.getElementById('websiteInput')?.value || '',
                    cpf_cnpj: document.getElementById('VATInput')?.value || '',
                    endereco: document.getElementById('addressInput_2')?.value || '',
                    sobre: document.getElementById('aboutInput')?.value || '',
                    
                    // Informações adicionais
                    data_nascimento: document.getElementById('dateofBirth')?.value || '',
                    pais: document.querySelector('[data-select2-selector="country"]')?.value || 'br',
                    estado: document.querySelector('[data-select2-selector="state"]')?.value || '',
                    cidade: document.querySelector('[data-select2-selector="city"]')?.value || '',
                    fuso_horario: document.querySelector('[data-select2-selector="tzone"]')?.value || '',
                    idiomas: this.getSelectValues('[data-select2-selector="language"]'),
                    moeda: document.querySelector('[data-select2-selector="currency"]')?.value || 'BRL',
                    grupos: this.getSelectValues('[data-select2-selector="tag"]'),
                    status: document.querySelector('[data-select2-selector="status"]')?.value || 'success',
                    privacidade: document.querySelector('[data-select2-selector="privacy"]')?.value || 'everyone',
                    
                    // Metadados
                    criado_em: new Date().toISOString(),
                    atualizado_em: new Date().toISOString(),
                    id: this.generateId()
                };
                
                return formData;
            }
            
            // Obter valores de select múltiplo
            getSelectValues(selector) {
                const select = document.querySelector(selector);
                if (!select) return [];
                
                return Array.from(select.selectedOptions).map(option => option.value);
            }
            
            // Gerar ID único
            generateId() {
                return 'CLT' + Date.now().toString(36).toUpperCase() + Math.random().toString(36).substr(2, 5).toUpperCase();
            }
            
            // Validar formulário completo
            validateForm() {
                const requiredFields = [
                    'fullnameInput',
                    'mailInput'
                ];
                
                let isValid = true;
                const errors = [];
                
                requiredFields.forEach(fieldId => {
                    const field = document.getElementById(fieldId);
                    if (field && !this.validateField(field)) {
                        isValid = false;
                        errors.push(`Campo ${field.placeholder || fieldId} é obrigatório ou inválido`);
                    }
                });
                
                return { isValid, errors };
            }
            
            // Salvar cliente
            async saveCustomer() {
                const validation = this.validateForm();
                
                if (!validation.isValid) {
                    this.showNotification('⚠️ Corrija os erros no formulário', 'error');
                    validation.errors.forEach(error => console.warn(error));
                    return false;
                }
                
                const customerData = this.collectFormData();
                
                try {
                    // Tentar salvar na API
                    const response = await this.saveToAPI(customerData);
                    
                    if (response.success) {
                        this.customers.push(customerData);
                        this.saveCustomersToStorage();
                        this.showNotification(`✅ Cliente ${customerData.nome} criado com sucesso!`, 'success');
                        this.resetForm();
                        
                        // Redirecionar para lista de clientes após 2 segundos
                        setTimeout(() => {
                            window.location.href = 'customers.html';
                        }, 2000);
                    } else {
                        throw new Error(response.message || 'Erro ao salvar cliente');
                    }
                } catch (error) {
                    // Salvar localmente em caso de erro
                    console.warn('API não disponível, salvando localmente:', error.message);
                    
                    this.customers.push(customerData);
                    this.saveCustomersToStorage();
                    this.showNotification(`✅ Cliente ${customerData.nome} salvo localmente!`, 'success');
                    this.resetForm();
                }
                
                return true;
            }
            
            // Salvar rascunho
            saveDraft() {
                const customerData = this.collectFormData();
                customerData.status = 'draft';
                customerData.id = 'DRAFT' + Date.now().toString(36).toUpperCase();
                
                // Salvar no localStorage
                const drafts = JSON.parse(localStorage.getItem('customer_drafts') || '[]');
                drafts.push(customerData);
                localStorage.setItem('customer_drafts', JSON.stringify(drafts));
                
                this.showNotification('💾 Rascunho salvo com sucesso!', 'info');
            }
            
            // Salvar via API
            async saveToAPI(customerData) {
                const response = await fetch('/duralux/api/customers.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(customerData)
                });
                
                return await response.json();
            }
            
            // Salvar no localStorage
            saveCustomersToStorage() {
                localStorage.setItem('duralux_customers', JSON.stringify(this.customers));
            }
            
            // Carregar do localStorage
            loadCustomersFromStorage() {
                const stored = localStorage.getItem('duralux_customers');
                this.customers = stored ? JSON.parse(stored) : [];
            }
            
            // Limpar formulário
            resetForm() {
                document.querySelectorAll('input, textarea, select').forEach(field => {
                    if (field.type !== 'file') {
                        field.value = '';
                        this.clearFieldValidation(field);
                    }
                });
                
                // Resetar título
                document.title = 'Criar Cliente - Duralux CRM';
            }
            
            // Mostrar notificação
            showNotification(message, type = 'info') {
                // Remover notificação anterior se existir
                const existingNotification = document.querySelector('.duralux-notification');
                if (existingNotification) {
                    existingNotification.remove();
                }
                
                const notification = document.createElement('div');
                notification.className = `duralux-notification notification-${type}`;
                
                const colors = {
                    success: '#28a745',
                    error: '#dc3545',
                    warning: '#ffc107',
                    info: '#17a2b8'
                };
                
                notification.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: ${colors[type] || colors.info};
                    color: white;
                    padding: 15px 25px;
                    border-radius: 8px;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
                    z-index: 10000;
                    font-weight: 600;
                    max-width: 400px;
                    animation: slideInRight 0.3s ease;
                `;
                
                notification.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span>${message}</span>
                        <button onclick="this.parentElement.parentElement.remove()" 
                                style="background: none; border: none; color: white; font-size: 18px; cursor: pointer;">×</button>
                    </div>
                `;
                
                document.body.appendChild(notification);
                
                // Remover automaticamente após 5 segundos
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 5000);
            }
            
            // Obter estatísticas
            getStats() {
                return {
                    total: this.customers.length,
                    ativos: this.customers.filter(c => c.status === 'success').length,
                    inativos: this.customers.filter(c => c.status === 'warning').length,
                    bloqueados: this.customers.filter(c => c.status === 'danger').length
                };
            }
        }
        
        // CSS adicional para animações
        const additionalCSS = `
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            .is-valid {
                border-color: #28a745 !important;
            }
            
            .is-invalid {
                border-color: #dc3545 !important;
            }
            
            .invalid-feedback {
                display: block;
                width: 100%;
                margin-top: 0.25rem;
                font-size: 0.875em;
                color: #dc3545;
            }
            
            .form-control:focus.is-valid {
                border-color: #28a745;
                box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
            }
            
            .form-control:focus.is-invalid {
                border-color: #dc3545;
                box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
            }
        `;
        
        // Adicionar CSS
        const styleSheet = document.createElement('style');
        styleSheet.textContent = additionalCSS;
        document.head.appendChild(styleSheet);
        
        // Inicializar quando o DOM estiver pronto
        document.addEventListener('DOMContentLoaded', function() {
            // Aguardar um pouco para garantir que todos os elementos estejam carregados
            setTimeout(() => {
                window.customerManager = new CustomerManager();
                console.log('✅ Sistema de Clientes Duralux totalmente carregado!');
            }, 500);
        });'''
    
    return javascript_code

def main():
    """Função principal para implementar a funcionalidade"""
    
    customer_create_path = r"C:\Users\ivonm\OneDrive - sga.pucminas.br\Github\duralux\duralux\duralux-admin\customers-create.html"
    
    try:
        print("🔧 Implementando funcionalidade de adicionar cliente...")
        
        # Ler o arquivo atual
        with open(customer_create_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Criar o JavaScript funcional
        customer_js = create_functional_customer_form()
        
        # Encontrar onde inserir o JavaScript (antes do script do notification center)
        insert_position = content.find('class NotificationCenter {')
        
        if insert_position == -1:
            # Se não encontrar, inserir antes do </body>
            insert_position = content.find('</body>')
            new_content = content[:insert_position] + f"\n<script>\n{customer_js}\n</script>\n" + content[insert_position:]
        else:
            # Inserir antes do NotificationCenter
            new_content = content[:insert_position-9] + f"{customer_js}\n\n        " + content[insert_position:]
        
        # Modificar campos para Brasil
        # Alterar moeda padrão para Real
        new_content = re.sub(
            r'<option data-currency="us" selected> - US Dollar - \$</option>',
            '<option data-currency="us"> - US Dollar - $</option>',
            new_content
        )
        
        new_content = re.sub(
            r'<option data-currency="br">BRL - Brazilian Real - R\$</option>',
            '<option data-currency="br" selected>BRL - Real Brasileiro - R$</option>',
            new_content
        )
        
        # Alterar país padrão para Brasil
        new_content = re.sub(
            r'<option data-country="us" selected>United States</option>',
            '<option data-country="us">United States</option>',
            new_content
        )
        
        new_content = re.sub(
            r'<option data-country="br">Brazil</option>',
            '<option data-country="br" selected>Brasil</option>',
            new_content
        )
        
        # Alterar idioma padrão para português
        new_content = re.sub(
            r'<option data-language="bg-danger" selected>English</option>',
            '<option data-language="bg-danger">English</option>',
            new_content
        )
        
        new_content = re.sub(
            r'<option data-language="bg-teal">Portuguese - português</option>',
            '<option data-language="bg-teal" selected>Português - Brasil</option>',
            new_content
        )
        
        # Alterar placeholders para português
        new_content = re.sub(r'placeholder="Nome"', 'placeholder="Nome Completo"', new_content)
        new_content = re.sub(r'placeholder="Email"', 'placeholder="exemplo@email.com"', new_content)
        new_content = re.sub(r'placeholder="Phone"', 'placeholder="(11) 99999-9999"', new_content)
        new_content = re.sub(r'placeholder="Company"', 'placeholder="Nome da Empresa"', new_content)
        new_content = re.sub(r'placeholder="Designation"', 'placeholder="Cargo/Função"', new_content)
        new_content = re.sub(r'placeholder="Website"', 'placeholder="https://exemplo.com"', new_content)
        new_content = re.sub(r'placeholder="VAT"', 'placeholder="CPF ou CNPJ"', new_content)
        new_content = re.sub(r'placeholder="Address"', 'placeholder="Endereço completo com CEP"', new_content)
        
        # Atualizar textos dos botões
        new_content = re.sub(
            r'<span>Create Customer</span>',
            '<span>Criar Cliente</span>',
            new_content
        )
        
        new_content = re.sub(
            r'<span>Save as Draft</span>',
            '<span>Salvar Rascunho</span>',
            new_content
        )
        
        # Salvar o arquivo modificado
        with open(customer_create_path, 'w', encoding='utf-8') as f:
            f.write(new_content)
        
        print("✅ Funcionalidade de cliente implementada com sucesso!")
        print("\n📋 Recursos implementados:")
        print("• Validação em tempo real de campos")
        print("• Máscaras para telefone, CPF e CNPJ brasileiros")
        print("• Validação de CPF e CNPJ com algoritmo correto")
        print("• Validação de email com regex")
        print("• Sistema de notificações visuais")
        print("• Salvamento local e via API")
        print("• Funcionalidade de rascunho")
        print("• Campos configurados para Brasil")
        print("• Moeda padrão: Real (R$)")
        print("• País padrão: Brasil")
        print("• Idioma padrão: Português")
        print("• Placeholders em português")
        print("• Interface responsiva")
        
        # Verificar tamanho do arquivo
        if os.path.exists(customer_create_path):
            size = os.path.getsize(customer_create_path)
            print(f"• Arquivo atualizado: {size:,} bytes")
        
        return True
        
    except Exception as e:
        print(f"❌ Erro ao implementar funcionalidade: {str(e)}")
        return False

if __name__ == "__main__":
    success = main()
    if success:
        print("\n🎉 Sistema de clientes pronto para uso!")
        print("🔗 Acesse: http://localhost/duralux/duralux-admin/customers-create.html")
    else:
        print("\n❌ Falha na implementação do sistema de clientes.")