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

## Adapter modes

The supplier engine uses `TSA_SUPPLIER_ENGINE_MODE` to select a flight supplier adapter.

Supported MVP modes:

    TSA_SUPPLIER_ENGINE_MODE=mock
    TSA_SUPPLIER_ENGINE_MODE=duffel_sandbox
    TSA_SUPPLIER_ENGINE_MODE=mystifly_sandbox
    TSA_SUPPLIER_ENGINE_MODE=biletbank_sandbox

`mock` returns local normalized offers and is safe for development.

The sandbox modes are registered as safe placeholders. If their credentials are missing, they return a normalized `SUPPLIER_NOT_CONFIGURED` error instead of a raw 500 error.

## Future supplier credentials

    DUFFEL_API_TOKEN=

    MYSTIFLY_USERNAME=
    MYSTIFLY_PASSWORD=
    MYSTIFLY_ACCOUNT_NUMBER=

    BILETBANK_USERNAME=
    BILETBANK_PASSWORD=
    BILETBANK_AGENCY_CODE=

