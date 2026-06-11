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
