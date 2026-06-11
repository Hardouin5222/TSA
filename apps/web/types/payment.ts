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
  };
};
