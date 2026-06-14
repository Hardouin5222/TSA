from typing import Annotated

from fastapi import APIRouter, Depends, Query

from app.dependencies import DbSession, get_optional_user_id
from app.schemas import AddFlightToCartRequest, AddGenericItemToCartRequest, ClaimGuestCartRequest
from app.service import add_flight_to_cart, add_generic_item_to_cart, claim_guest_cart, get_current_cart
from travel_shared.responses import success_response

router = APIRouter(prefix="/cart", tags=["cart"])


@router.post("/items/flight")
async def add_flight_item(
    payload: AddFlightToCartRequest,
    db: DbSession,
    user_id: Annotated[str | None, Depends(get_optional_user_id)],
) -> dict:
    result = add_flight_to_cart(payload, db, user_id)
    return success_response(result.model_dump(), message="Flight offer added to cart")


@router.post("/items/hotel")
async def add_hotel_item(
    payload: AddGenericItemToCartRequest,
    db: DbSession,
    user_id: Annotated[str | None, Depends(get_optional_user_id)],
) -> dict:
    result = add_generic_item_to_cart(payload, db, user_id)
    return success_response(result.model_dump(), message="Hotel offer added to cart")


@router.post("/items/car")
async def add_car_item(
    payload: AddGenericItemToCartRequest,
    db: DbSession,
    user_id: Annotated[str | None, Depends(get_optional_user_id)],
) -> dict:
    result = add_generic_item_to_cart(payload, db, user_id)
    return success_response(result.model_dump(), message="Car rental offer added to cart")


@router.get("/current")
async def current_cart(
    db: DbSession,
    user_id: Annotated[str | None, Depends(get_optional_user_id)],
    guest_session_id: Annotated[str | None, Query()] = None,
) -> dict:
    result = get_current_cart(db, user_id, guest_session_id)
    return success_response(result.model_dump(), message="Cart fetched successfully")


@router.post("/claim-guest")
async def claim_guest(
    payload: ClaimGuestCartRequest,
    db: DbSession,
) -> dict:
    result = claim_guest_cart(payload, db)
    return success_response(result.model_dump(), message="Guest cart claimed successfully")
