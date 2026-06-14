import httpx
from fastapi import APIRouter, Header, HTTPException, Query

router = APIRouter(prefix="/bookings", tags=["bookings"])

# Ham servis kök adresi (Sonda eğik çizgi yok)
SERVER_URL = "http://tsa-booking-service:8005/api/bookings"

def _raise_booking_error(response: httpx.Response) -> None:
    try:
        detail = response.json().get("detail", "Booking service request failed")
    except Exception:
        detail = f"Booking service HTML/Raw Error: {response.text[:100]}"
    raise HTTPException(status_code=response.status_code, detail=detail)

@router.post("/from-payment")
async def create_booking_from_payment(payload: dict) -> dict:
    # Sonda eğik çizgi olmadan doğrudan endpoint'e gider
    target_url = f"{SERVER_URL}/from-payment"
    async with httpx.AsyncClient(timeout=20.0) as client:
        response = await client.post(target_url, json=payload)
    if response.status_code >= 400:
        _raise_booking_error(response)
    return response.json()

@router.get("/reference/{booking_reference}")
async def get_booking_by_reference(booking_reference: str) -> dict:
    target_url = f"{SERVER_URL}/reference/{booking_reference}"
    async with httpx.AsyncClient(timeout=20.0) as client:
        response = await client.get(target_url)
    if response.status_code >= 400:
        _raise_booking_error(response)
    return response.json()

@router.get("/")
async def list_bookings(user_id: str | None = Query(default=None), guest_session_id: str | None = Query(default=None)) -> dict:
    # Listeleme servisi inatla 307 atmıştı, bu yüzden sonuna açıkça / koyuyoruz
    target_url = f"{SERVER_URL}/"
    async with httpx.AsyncClient(timeout=20.0) as client:
        response = await client.get(
            target_url,
            params={"user_id": user_id, "guest_session_id": guest_session_id},
        )
    if response.status_code >= 400:
        _raise_booking_error(response)
    return response.json()

@router.post("/claim-guest")
async def claim_guest_bookings(payload: dict, authorization: str | None = Header(default=None)) -> dict:
    target_url = f"{SERVER_URL}/claim-guest"
    headers = {"Content-Type": "application/json"}
    if authorization:
        headers["Authorization"] = authorization
    async with httpx.AsyncClient(timeout=20.0) as client:
        response = await client.post(target_url, json=payload, headers=headers)
    if response.status_code >= 400:
        _raise_booking_error(response)
    return response.json()
