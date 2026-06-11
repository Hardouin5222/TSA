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
  };
};
