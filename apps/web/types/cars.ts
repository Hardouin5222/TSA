export type CarSearchEnvelope = {
  success: boolean;
  message: string;
  data: {
    search_id: string;
    route_label: string;
    rental_days: number;
    offers: Array<{
      id: string;
      provider: string;
      vendor_name: string;
      vehicle_name: string;
      category: string;
      transmission: string;
      fuel_policy: string;
      seats: number;
      bags: number;
      doors: number;
      daily_price: number;
      total_price: number;
      currency: string;
      pickup_location: string;
      dropoff_location: string;
      image_url: string;
      included: string[];
      tags: string[];
      air_conditioning: boolean;
      unlimited_mileage: boolean;
    }>;
  };
};
