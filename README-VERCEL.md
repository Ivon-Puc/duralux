# Duralux Dashboard

Sistema de gestão empresarial Duralux hospedado no Vercel.

## Deploy

Este projeto está configurado para deploy automático no Vercel:

- **Build Command**: `npm run build` (apenas echoes, projeto estático)
- **Output Directory**: `duralux-admin/`
- **Install Command**: `npm install`

## Estrutura

- `duralux-admin/` - Interface principal do dashboard
- `api/` - APIs PHP (serverless functions)
- `backend/api/` - APIs adicionais PHP

## Acesso

- URL principal: redireciona para `/duralux-admin/index.html`
- Dashboard: `/duralux-admin/`

## Tecnologias

- HTML5
- CSS3 (Bootstrap)
- JavaScript
- PHP (Serverless Functions)
