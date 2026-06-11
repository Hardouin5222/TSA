export type FlightSearchEnvelope = {
  success: boolean;
  message: string;
  data: {
    search_id: string;
    route_label: string;
    offers: Array<{
      id: string;
      provider: string;
      airline_name: string;
      airline_code: string;
      origin: string;
      destination: string;
      departure_at: string;
      arrival_at: string;
      duration_minutes: number;
      stop_count: number;
      cabin_class: string;
      baggage_summary: string;
      fare_family: string;
      cancellation_policy: string;
      seat_pitch: string;
      package_score: number;
      price_amount: number;
      price_currency: string;
      tags: string[];
    }>;
  };
};
