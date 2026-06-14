from pydantic import BaseModel, Field


class FareServiceFlags(BaseModel):
    seat_selection: bool = False
    meal_selection: bool = False
    refundable: bool = False
    exchangeable: bool = False


class SupplierOfferCapabilities(BaseModel):
    branded_fares_supported: bool = False
    checked_baggage_upsell_supported: bool = False
    seat_selection_supported: bool = False
    meal_selection_supported: bool = False
    traveler_note_supported: bool = False
    price_calendar_supported: bool = False
    hold_supported: bool = False
    ticketing_supported: bool = False
    live_pricing_supported: bool = False


class SupplierContext(BaseModel):
    source_mode: str = Field(default="mock_supplier", min_length=1)
    provider_code: str | None = None
    provider_name: str | None = None
