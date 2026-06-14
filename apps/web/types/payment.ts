export type PaymentIntentEnvelope = {
  success: boolean;
  message: string;
  data: {
    payment_intent_id: string;
    provider: string;
    provider_reference: string;
    status: string;
    amount: number;
    currency: string;
    checkout_url: string;
    contact?: {
      email: string;
      phone: string;
    };
    travelers?: Array<{
      traveler_type: string;
      first_name: string;
      last_name: string;
      birth_date: string;
    }>;
    special_requests?: {
      seat_preference?: string | null;
      meal_preference?: string | null;
      accessibility_note?: string | null;
    } | null;
    billing_details?: {
      invoice_type: string;
      full_name: string;
      country: string;
      city: string;
      address_line: string;
      company_name?: string | null;
      tax_number?: string | null;
    };
  };
};
