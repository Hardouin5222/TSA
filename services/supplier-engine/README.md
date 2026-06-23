# TSA Supplier Engine Minimal

Minimal isolated FastAPI supplier engine for Booking Core bridge testing.

## Run without Docker

```bash
cd services/supplier-engine
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
uvicorn app.main:app --host 127.0.0.1 --port 8010
```

## Health

```bash
curl -i http://127.0.0.1:8010/health
```

## Search test

```bash
curl -s -X POST http://127.0.0.1:8010/api/flights/search \
  -H 'Content-Type: application/json' \
  -d '{"origin":"IST","destination":"LHR","departure_date":"2026-06-18","adult_count":1}' | python3 -m json.tool
```

## Laravel .env

```env
TSA_SUPPLIER_ENGINE_MODE=bridge
TSA_SUPPLIER_ENGINE_BASE_URL=http://127.0.0.1:8010
TSA_SUPPLIER_ENGINE_TIMEOUT=20
```

After editing Laravel .env:

```bash
php artisan optimize:clear
php artisan config:clear
```
