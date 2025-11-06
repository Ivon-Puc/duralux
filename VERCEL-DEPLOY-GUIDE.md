# 🚀 Deploy no Vercel - Duralux

## ⚙️ Configurações Necessárias no Vercel

### 1. Build Settings

- **Framework Preset**: Other
- **Build Command**: `npm run build`
- **Output Directory**: `./`
- **Install Command**: `npm install`

### 2. Environment Variables (se necessário)

```
NODE_ENV=production
```

### 3. Domain Settings

- Após o deploy, anote a URL gerada (ex: `duralux-abc123.vercel.app`)
- Configure domínio personalizado se desejar

## 🔧 Arquivos de Configuração

### ✅ Arquivos Criados/Configurados:

- `package.json` - Configuração Node.js
- `vercel.json` - Configuração específica do Vercel
- `_redirects` - Redirecionamentos
- `.vercelignore` - Arquivos a ignorar
- `duralux-admin/assets/js/config.js` - Configuração de APIs

### 🌐 URLs de Acesso Após Deploy:

- **Página Principal**: `https://seu-dominio.vercel.app/`
- **Dashboard Admin**: `https://seu-dominio.vercel.app/duralux-admin/`
- **Alias Amigáveis**:
  - `https://seu-dominio.vercel.app/dashboard`
  - `https://seu-dominio.vercel.app/admin`

## 🔍 Funcionalidades

### ✅ Funcionarão:

- Dashboard completo (HTML/CSS/JS)
- APIs PHP como serverless functions
- Assets estáticos (imagens, CSS, JS)
- Redirecionamentos automáticos

### ⚠️ Limitações:

- Banco de dados local não funcionará (usar Vercel Postgres/PlanetScale)
- Arquivos da pasta `backend/` complexos foram excluídos
- Sessions PHP podem ter limitações

## 🐛 Troubleshooting

### Se houver erros de API:

1. Verifique se as URLs estão sendo geradas corretamente
2. Confira os logs do Vercel na dashboard
3. APIs estão em `/api/*.php`

### Se assets não carregarem:

1. Verifique se os caminhos são relativos
2. Confirme que arquivos existem na pasta `duralux-admin/assets/`

## 📋 Próximos Passos Após Deploy:

1. ✅ Testar todas as páginas do dashboard
2. ✅ Verificar se APIs respondem corretamente
3. ✅ Configurar banco de dados em nuvem
4. ✅ Ajustar variáveis de ambiente se necessário
5. ✅ Configurar domínio personalizado (opcional)

---

**Status**: ✅ Pronto para deploy no Vercel
