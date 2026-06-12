export type BookingEnvelope = {
  success: boolean;
  message: string;
  data: {
    booking_id: string;
    booking_reference: string;
    status: string;
    total_amount: number;
    currency: string;
    item_count: number;
  };
};

export type BookingListEnvelope = {
  success: boolean;
  message: string;
  data: {
    bookings: Array<{
      booking_id: string;
      booking_reference: string;
      status: string;
      total_amount: number;
      currency: string;
      item_count: number;
      created_at: string;
      primary_item_title: string;
    }>;
  };
};

export type BookingDetailEnvelope = {
  success: boolean;
  message: string;
  data: {
    booking_id: string;
    booking_reference: string;
    status: string;
    total_amount: number;
    currency: string;
    item_count: number;
    provider_reference: string;
    cart_id: string;
    user_id: string | null;
    guest_session_id: string | null;
    created_at: string;
    items: Array<{
      id: string;
      item_type: string;
      reference_id: string;
      title: string;
      quantity: number;
      unit_price: number;
      currency: string;
      item_payload: Record<string, unknown>;
    }>;
    contact: {
      email: string;
      phone: string;
    } | null;
    travelers: Array<{
      traveler_type: string;
      first_name: string;
      last_name: string;
      birth_date: string;
    }>;
    special_requests: {
      seat_preference?: string | null;
      meal_preference?: string | null;
      accessibility_note?: string | null;
    } | null;
    billing_details: {
      invoice_type: string;
      full_name: string;
      country: string;
      city: string;
      address_line: string;
      company_name?: string | null;
      tax_number?: string | null;
    } | null;
  };
};
