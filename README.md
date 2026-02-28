# Hub Inteligente de Recursos Educacionais

Projeto fullstack para cadastro de recursos educacionais com CRUD, paginação, filtros e assistente de descrição/tags via Google Gemini.

## Tecnologias
- Backend: Laravel 11, PHP 8.3, PostgreSQL, HTTP Client, PHPUnit, Pint
- Frontend: React, Vite, TypeScript, Axios, React Query, Tailwind
- Infra: Docker Compose, GitHub Actions

## Como rodar com Docker
1. Prepare os arquivos de ambiente:
   ```bash
   cp backend/.env.example backend/.env
   cp frontend/.env.example frontend/.env
   ```
2. No `backend/.env`, defina:
   - `GEMINI_API_KEY="SUA_CHAVE"`
   - `GEMINI_MODEL="gemini-2.5-flash"` (opcional)
3. (Opcional) Se quiser sobrescrever a URL da API no build do frontend Docker, exporte:
   ```bash
   export VITE_API_URL="http://localhost:8000/api/v1"
   ```
4. Suba os serviços:
   ```bash
   docker compose up -d --build
   ```
5. Acesse:
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
- `VITE_API_URL` (para rodar `npm run dev` localmente)

### Frontend no Docker
- O frontend Docker recebe `VITE_API_URL` via `build.args` no `docker-compose.yml`
- Valor padrão: `http://localhost:8000/api/v1`
- Para sobrescrever, use `export VITE_API_URL=...` antes de `docker compose up --build`

## Endpoints
- `GET /api/v1/resources`
- `POST /api/v1/resources`
- `PUT /api/v1/resources/{id}`
- `DELETE /api/v1/resources/{id}`
- `POST /api/v1/resources/smart-assist`
- `GET /health`

## Requisitos do processo seletivo (atendidos)
### Frontend
- Tratamento de falha da IA: quando `POST /api/v1/resources/smart-assist` falha, a interface exibe mensagem de erro amigável.
- Tratamento de demora da IA: durante a geração, o botão fica em estado de carregamento (`Gerando...`) e erros de timeout/resposta lenta são exibidos ao usuário via feedback de erro.

### Backend
- Observabilidade com logs estruturados na integração com IA:
  - Sucesso e erro são registrados com `title`, `tokens`, `latency_ms` e `status`.
  - Exemplo esperado no desafio: `[INFO] AI Request: Title="Matemática Financeira", TokenUsage=150, Latency=1.2s`.
  - Exemplo atual (estruturado): `{"message":"AI Request","context":{"title":"Matemática Financeira","tokens":150,"latency_ms":1200,"status":"success"}}`.
- Health Check disponível em `GET /health`.

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
