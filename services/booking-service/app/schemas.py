from pydantic import BaseModel, Field


class BookingSourceItem(BaseModel):
    id: str
    item_type: str
    reference_id: str
    title: str
    quantity: int
    unit_price: float
    currency: str
    item_payload: dict


class BookingContact(BaseModel):
    email: str = Field(min_length=3)
    phone: str = Field(min_length=6)


class BookingTraveler(BaseModel):
    traveler_type: str = Field(default="adult", min_length=1)
    first_name: str = Field(min_length=1)
    last_name: str = Field(min_length=1)
    birth_date: str = Field(min_length=8)


class CreateBookingFromPaymentRequest(BaseModel):
    payment_intent_id: str = Field(min_length=1)
    provider_reference: str = Field(min_length=1)
    cart_id: str = Field(min_length=1)
    user_id: str | None = None
    guest_session_id: str | None = None
    customer_email: str | None = None
    customer_phone: str | None = None
    total_amount: float = Field(gt=0)
    currency: str
    items: list[BookingSourceItem]
    contact: BookingContact
    travelers: list[BookingTraveler] = Field(min_length=1)


class BookingResponse(BaseModel):
    booking_id: str
    booking_reference: str
    status: str
    total_amount: float
    currency: str
    item_count: int


class BookingItemResponse(BaseModel):
    id: str
    item_type: str
    reference_id: str
    title: str
    quantity: int
    unit_price: float
    currency: str
    item_payload: dict


class BookingDetailResponse(BaseModel):
    booking_id: str
    booking_reference: str
    status: str
    total_amount: float
    currency: str
    item_count: int
    provider_reference: str
    cart_id: str
    user_id: str | None
    guest_session_id: str | None
    created_at: str
    items: list[BookingItemResponse]
    contact: BookingContact | None = None
    travelers: list[BookingTraveler] = Field(default_factory=list)


class BookingListItemResponse(BaseModel):
    booking_id: str
    booking_reference: str
    status: str
    total_amount: float
    currency: str
    item_count: int
    created_at: str
    primary_item_title: str


class BookingListResponse(BaseModel):
    bookings: list[BookingListItemResponse]


class ClaimGuestBookingRequest(BaseModel):
    guest_session_id: str = Field(min_length=1, max_length=100)
    user_id: str = Field(min_length=1, max_length=100)


class ClaimGuestBookingResponse(BaseModel):
    claimed_count: int
