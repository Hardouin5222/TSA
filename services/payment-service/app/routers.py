from typing import Annotated

from fastapi import APIRouter, Depends
from sqlalchemy.orm import Session

from app.core.database import get_db_session
from app.schemas import CreatePaymentIntentRequest
from app.service import confirm_payment_intent, create_payment_intent, get_payment_intent
from travel_shared.responses import success_response

router = APIRouter(prefix="/payments", tags=["payments"])
DbSession = Annotated[Session, Depends(get_db_session)]


@router.post("/intents")
async def create_intent(payload: CreatePaymentIntentRequest, db: DbSession) -> dict:
    result = create_payment_intent(payload, db)
    return success_response(result.model_dump(), message="Payment intent created successfully")


@router.get("/intents/{provider_reference}")
async def get_intent(provider_reference: str, db: DbSession) -> dict:
    result = get_payment_intent(provider_reference, db)
    return success_response(result.model_dump(), message="Payment intent fetched successfully")


@router.post("/intents/{provider_reference}/confirm")
async def confirm_intent(provider_reference: str, db: DbSession) -> dict:
    result = confirm_payment_intent(provider_reference, db)
    return success_response(result.model_dump(), message="Payment marked as paid")
