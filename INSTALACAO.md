# 🚀 DURALUX CRM - Guia de Instalação do Ambiente

## 📋 Pré-requisitos
- Windows 10/11
- Navegador moderno (Chrome, Firefox, Edge)

## 🛠️ Opção 1: XAMPP (Recomendado)

### 1. Download e Instalação
1. Acesse: https://www.apachefriends.org/pt_br/download.html
2. Baixe XAMPP para Windows (versão mais recente)
3. Execute o instalador como Administrador
4. Instale em: `C:\xampp`
5. Componentes necessários: ✅ Apache ✅ PHP ✅ MySQL (opcional)

### 2. Configuração
1. Abra XAMPP Control Panel
2. Clique em "Start" no Apache
3. Teste: http://localhost (deve mostrar página do XAMPP)

### 3. Configurar Projeto
```bash
# Copie o projeto para:
C:\xampp\htdocs\duralux\

# Ou crie link simbólico (Execute como Admin):
cd C:\xampp\htdocs\
mklink /D duralux "C:\Users\[SEU_USUARIO]\OneDrive - sga.pucminas.br\Github\duralux\duralux"
```

## 🛠️ Opção 2: WAMP

### 1. Download e Instalação  
1. Acesse: https://www.wampserver.com/en/download-wampserver-64bits/
2. Baixe WampServer 64-bit
3. Instale seguindo o assistente
4. Inicie o WampServer

### 2. Configurar Projeto
```bash
# Copie para:
C:\wamp64\www\duralux\
```

## 🛠️ Opção 3: PHP Built-in Server (Desenvolvimento)

### 1. Instalar PHP Standalone
1. Acesse: https://windows.php.net/download/
2. Baixe "Thread Safe" ZIP
3. Extraia em: `C:\php`
4. Adicione `C:\php` no PATH do Windows

### 2. Testar Instalação
```bash
# Abra CMD e teste:
php --version
```

### 3. Executar Projeto
```bash
# No diretório do projeto:
cd "C:\Users\ivonm\OneDrive - sga.pucminas.br\Github\duralux\duralux"
php -S localhost:8000 -t duralux-admin
```

## ✅ Verificar Instalação

### 1. Testar PHP
Crie arquivo `teste.php`:
```php
<?php
phpinfo();
echo "PHP funcionando!";
?>
```

### 2. Inicializar Banco de Dados
```bash
# Via navegador:
http://localhost/duralux/backend/init.php

# Ou via linha de comando:
cd backend
php init.php
```

### 3. Acessar Sistema
- Frontend: http://localhost/duralux/duralux-admin/
- Login: admin@duralux.com
- Senha: admin123

## 🔧 Configurações Adicionais

### Habilitar SQLite no PHP
Edite `php.ini` (se necessário):
```ini
extension=sqlite3
extension=pdo_sqlite
```

### Configurar Uploads
Edite `php.ini`:
```ini
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
```

## 📁 Estrutura Final
```
duralux/
├── duralux-admin/          # Frontend HTML/CSS/JS
├── backend/                # Backend PHP
│   ├── config/            # Configurações
│   ├── api/               # Endpoints da API
│   ├── classes/           # Classes PHP
│   ├── uploads/           # Arquivos enviados
│   ├── database/          # Banco SQLite
│   └── logs/              # Logs do sistema
└── docs/                   # Documentação
```

## 🐛 Soluções de Problemas

### Apache não inicia
- Verificar se porta 80 está ocupada
- Executar XAMPP como Administrador
- Desabilitar Skype (usa porta 80)

### SQLite não funciona
- Verificar se extensão está habilitada
- Checar permissões da pasta database/

### Erro 403/404
- Verificar se arquivo existe
- Checar configuração do virtual host

## 📞 Próximos Passos
1. ✅ Instalar ambiente
2. ✅ Testar backend/init.php 
3. ✅ Acessar login do sistema
4. 🔄 Continuar desenvolvimento...