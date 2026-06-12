import httpx
from fastapi import APIRouter, Header, HTTPException, Query, status

from app.core.settings import get_gateway_settings

router = APIRouter(prefix="/cart", tags=["cart"])
settings = get_gateway_settings()


def _raise_cart_error(response: httpx.Response) -> None:
    detail = response.json().get("detail", "Cart service request failed")
    raise HTTPException(status_code=response.status_code, detail=detail)


@router.post("/items/flight")
async def add_flight_to_cart(payload: dict, authorization: str | None = Header(default=None)) -> dict:
    target_url = f"{settings.cart_service_base_url}{settings.api_prefix}/cart/items/flight"
    headers = {"Content-Type": "application/json"}
    if authorization:
        headers["Authorization"] = authorization

    async with httpx.AsyncClient(timeout=20.0) as client:
        response = await client.post(target_url, json=payload, headers=headers)

    if response.status_code >= 400:
        _raise_cart_error(response)

    return response.json()


@router.get("/current")
async def get_current_cart(
    authorization: str | None = Header(default=None),
    guest_session_id: str | None = Query(default=None),
) -> dict:
    target_url = f"{settings.cart_service_base_url}{settings.api_prefix}/cart/current"
    headers: dict[str, str] = {}
    if authorization:
        headers["Authorization"] = authorization

    async with httpx.AsyncClient(timeout=20.0) as client:
        response = await client.get(
            target_url,
            params={"guest_session_id": guest_session_id} if guest_session_id else None,
            headers=headers,
        )

    if response.status_code >= 400:
        _raise_cart_error(response)

    return response.json()


@router.post("/claim-guest")
async def claim_guest_cart(payload: dict, authorization: str | None = Header(default=None)) -> dict:
    target_url = f"{settings.cart_service_base_url}{settings.api_prefix}/cart/claim-guest"
    headers = {"Content-Type": "application/json"}
    if authorization:
        headers["Authorization"] = authorization

    async with httpx.AsyncClient(timeout=20.0) as client:
        response = await client.post(target_url, json=payload, headers=headers)

    if response.status_code >= 400:
        _raise_cart_error(response)

    return response.json()
