export type FlightSearchEnvelope = {
  success: boolean;
  message: string;
  data: {
    search_id: string;
    route_label: string;
    offers: Array<{
      fare_options: Array<{
        id: string;
        label: string;
        badge: string | null;
        price_delta: number;
        hand_baggage: string;
        checked_baggage: string;
        features: string[];
        seat_selection: boolean;
        refundable: boolean;
        exchangeable: boolean;
        meal_included: boolean;
        service_flags: {
          seat_selection: boolean;
          meal_selection: boolean;
          refundable: boolean;
          exchangeable: boolean;
        };
      }>;
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
      selected_fare_option_id: string;
      tags: string[];
      capabilities: {
        branded_fares_supported: boolean;
        checked_baggage_upsell_supported: boolean;
        seat_selection_supported: boolean;
        meal_selection_supported: boolean;
        traveler_note_supported: boolean;
        price_calendar_supported: boolean;
        hold_supported: boolean;
        ticketing_supported: boolean;
        live_pricing_supported: boolean;
      };
      supplier_context: {
        source_mode: string;
        provider_code: string | null;
        provider_name: string | null;
      };
    }>;
  };
};

export type FlightOffer = FlightSearchEnvelope["data"]["offers"][number];
