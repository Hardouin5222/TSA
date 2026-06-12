import { MockCheckoutContent } from "@/components/checkout/mock-checkout-content";
import { serverApiRequest } from "@/lib/api";

type PaymentIntentEnvelope = {
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
    cart_id: string;
    user_id: string | null;
    guest_session_id: string | null;
    contact: {
      email: string;
      phone: string;
    };
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
    };
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

export default async function MockCheckoutPage({
  params,
}: {
  params: Promise<{ providerReference: string }>;
}) {
  const { providerReference } = await params;
  const payload = await serverApiRequest<PaymentIntentEnvelope>(`/api/payments/intents/${providerReference}`);

  return <MockCheckoutContent intent={payload.data} providerReference={providerReference} />;
}
