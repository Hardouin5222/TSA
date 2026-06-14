export type CartEnvelope = {
  success: boolean;
  message: string;
  data: {
    cart_id: string;
    user_id: string | null;
    guest_session_id: string | null;
    status: string;
    currency: string;
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
    total_amount: number;
  };
};
