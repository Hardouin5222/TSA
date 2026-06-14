export type HotelSearchEnvelope = {
  success: boolean;
  message: string;
  data: {
    search_id: string;
    destination_label: string;
    nights: number;
    offers: Array<{
      id: string;
      provider: string;
      property_id: string;
      name: string;
      city: string;
      country_code: string;
      star_rating: number;
      guest_score: number;
      guest_count: number;
      nightly_price: number;
      total_price: number;
      currency: string;
      board_type: string;
      cancellation_policy: string;
      neighborhood: string;
      image_url: string;
      amenities: string[];
      tags: string[];
      room_name: string;
      room_size_sqm: number;
      refundable: boolean;
      pay_at_hotel: boolean;
      latitude: number;
      longitude: number;
    }>;
  };
};
