import httpx
from fastapi import APIRouter, HTTPException, status

from app.core.settings import get_gateway_settings

router = APIRouter(prefix="/payments", tags=["payments"])
settings = get_gateway_settings()


@router.post("/intents")
async def create_payment_intent(payload: dict) -> dict:
    target_url = f"{settings.payment_service_base_url}{settings.api_prefix}/payments/intents"

    async with httpx.AsyncClient(timeout=20.0) as client:
        response = await client.post(target_url, json=payload)

    if response.status_code >= 400:
        raise HTTPException(status_code=status.HTTP_502_BAD_GATEWAY, detail="Payment service request failed")

    return response.json()
