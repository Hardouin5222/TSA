from decimal import Decimal

from sqlalchemy import select
from sqlalchemy.orm import Session

from app.models import Cart, CartItem
from app.schemas import AddFlightToCartRequest, CartItemResponse, CartResponse


def add_flight_to_cart(payload: AddFlightToCartRequest, db: Session, user_id: str | None) -> CartResponse:
    cart = _get_or_create_open_cart(db, user_id=user_id, guest_session_id=payload.guest_session_id)

    title = f"{payload.offer.airline_name} {payload.offer.origin} → {payload.offer.destination}"
    existing_item = db.scalar(
        select(CartItem).where(
            CartItem.cart_id == cart.id,
            CartItem.item_type == "flight",
            CartItem.reference_id == payload.offer.id,
        )
    )

    if existing_item:
        existing_item.item_payload = payload.offer.model_dump()
        existing_item.unit_price = payload.offer.price_amount
        existing_item.title = title
        existing_item.currency = payload.offer.price_currency
    else:
        db.add(
            CartItem(
                cart_id=cart.id,
                item_type="flight",
                reference_id=payload.offer.id,
                item_payload=payload.offer.model_dump(),
                quantity=1,
                unit_price=payload.offer.price_amount,
                currency=payload.offer.price_currency,
                title=title,
            )
        )

    db.commit()
    db.refresh(cart)
    return get_current_cart(db, user_id=user_id, guest_session_id=payload.guest_session_id)


def get_current_cart(db: Session, user_id: str | None, guest_session_id: str | None) -> CartResponse:
    cart = _find_open_cart(db, user_id=user_id, guest_session_id=guest_session_id)
    if not cart:
        return CartResponse(
            cart_id="",
            user_id=user_id,
            guest_session_id=guest_session_id,
            status="empty",
            currency="EUR",
            items=[],
            total_amount=0.0,
        )

    items = db.scalars(select(CartItem).where(CartItem.cart_id == cart.id)).all()
    total = sum(Decimal(item.unit_price) * item.quantity for item in items)

    return CartResponse(
        cart_id=str(cart.id),
        user_id=str(cart.user_id) if cart.user_id else None,
        guest_session_id=cart.guest_session_id,
        status=cart.status,
        currency=cart.currency,
        items=[
            CartItemResponse(
                id=str(item.id),
                item_type=item.item_type,
                reference_id=item.reference_id,
                title=item.title,
                quantity=item.quantity,
                unit_price=float(item.unit_price),
                currency=item.currency,
                item_payload=item.item_payload,
            )
            for item in items
        ],
        total_amount=float(total),
    )


def _get_or_create_open_cart(db: Session, user_id: str | None, guest_session_id: str | None) -> Cart:
    cart = _find_open_cart(db, user_id=user_id, guest_session_id=guest_session_id)
    if cart:
        return cart

    cart = Cart(user_id=user_id, guest_session_id=guest_session_id, status="open", currency="EUR")
    db.add(cart)
    db.commit()
    db.refresh(cart)
    return cart


def _find_open_cart(db: Session, user_id: str | None, guest_session_id: str | None) -> Cart | None:
    if user_id:
        return db.scalar(select(Cart).where(Cart.user_id == user_id, Cart.status == "open"))

    if guest_session_id:
        return db.scalar(
            select(Cart).where(Cart.guest_session_id == guest_session_id, Cart.status == "open")
        )

    return None
