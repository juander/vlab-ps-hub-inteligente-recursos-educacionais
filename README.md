# Hub Inteligente de Recursos Educacionais

Projeto fullstack para cadastro de recursos educacionais com CRUD, paginação, filtros e assistente de descrição/tags via Google Gemini.

## Tecnologias
- Backend: Laravel 11, PHP 8.3, PostgreSQL, HTTP Client, PHPUnit, Pint
- Frontend: React, Vite, TypeScript, Axios, React Query, Tailwind
- Infra: Docker Compose, GitHub Actions

## Como rodar com Docker
1. Defina as variáveis no shell (ou em um `.env` na raiz usado pelo Docker Compose):
   ```bash
   export GEMINI_API_KEY="SUA_CHAVE"
   export GEMINI_MODEL="gemini-2.5-flash"
   ```
2. Suba os serviços:
   ```bash
   docker compose up -d --build
   ```
3. Acesse:
   - Frontend: `http://localhost:5173`
   - Backend: `http://localhost:8000`

## Variáveis de ambiente
### Backend (`backend/.env`)
- `APP_ENV`, `APP_KEY`, `APP_URL`
- `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `CACHE_STORE`, `SESSION_DRIVER`
- `GEMINI_API_KEY`, `GEMINI_MODEL`
- `FRONTEND_URL`

### Frontend (`frontend/.env`)
- `VITE_API_URL` (exemplo: `http://localhost:8000/api/v1`)

## Endpoints
- `GET /api/v1/resources`
- `POST /api/v1/resources`
- `PUT /api/v1/resources/{id}`
- `DELETE /api/v1/resources/{id}`
- `POST /api/v1/resources/smart-assist`
- `GET /health`

## Exemplos de request
### Health
```bash
curl -i http://localhost:8000/health
```

### Criar recurso
```bash
curl -X POST http://localhost:8000/api/v1/resources \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Matemática Financeira",
    "description": "Conceitos de juros simples e compostos.",
    "type": "video",
    "url": "https://example.com/video",
    "tags": ["matematica", "financas"]
  }'
```

### Smart Assist (campos obrigatórios: `title`, `type`, `url`)
```bash
curl -X POST http://localhost:8000/api/v1/resources/smart-assist \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Matemática Financeira",
    "type": "video",
    "url": "https://example.com/video"
  }'
```

## Testes
### Backend (fora do container `app`)
> O container `app` usa `composer install --no-dev`; por isso os comandos de teste não ficam disponíveis nele.

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan test
```

### Frontend
```bash
cd frontend
npm ci
npm run build
```

## CI (GitHub Actions)
Arquivo: `.github/workflows/ci.yml`
- Backend: instala dependências, roda Pint e PHPUnit
- Frontend: instala dependências e roda build

## Estrutura
```text
.
├── backend/
├── frontend/
├── Dockerfile.backend
├── Dockerfile.frontend
├── docker-compose.yml
├── .github/workflows/ci.yml
└── README.md
```
