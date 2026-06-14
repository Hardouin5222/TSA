type GenericCartItem = {
  item_type: string;
  title: string;
  item_payload: Record<string, unknown>;
};

export type ProductSummary = {
  kind: "flight" | "hotel" | "car" | "generic";
  title: string;
  subtitle: string;
  meta: Array<{ label: string; value: string }>;
  timeline?: {
    leftTime: string;
    leftLabel: string;
    middleLabel: string;
    middleSubLabel: string;
    rightTime: string;
    rightLabel: string;
  };
};

function readString(payload: Record<string, unknown>, key: string, fallback = "") {
  const value = payload[key];
  return typeof value === "string" ? value : fallback;
}

function readNumber(payload: Record<string, unknown>, key: string, fallback = 0) {
  const value = payload[key];
  return typeof value === "number" ? value : fallback;
}

function formatTime(value: string | undefined) {
  if (!value) {
    return "--:--";
  }

  return new Intl.DateTimeFormat("tr-TR", {
    hour: "2-digit",
    minute: "2-digit",
  }).format(new Date(value));
}

export function getProductSummary(item: GenericCartItem | null): ProductSummary | null {
  if (!item) {
    return null;
  }

  const payload = item.item_payload ?? {};

  if (item.item_type === "flight") {
    const origin = readString(payload, "origin");
    const destination = readString(payload, "destination");
    const departureAt = readString(payload, "departure_at");
    const arrivalAt = readString(payload, "arrival_at");
    const durationMinutes = readNumber(payload, "duration_minutes");
    const airlineName = readString(payload, "airline_name", item.title);
    const provider = readString(payload, "provider");
    const fareFamily = readString(payload, "fare_family", "Standart paket");
    const baggageSummary = readString(payload, "baggage_summary", "Bagaj bilgisi yok");

    return {
      kind: "flight",
      title: airlineName,
      subtitle: `${origin} - ${destination}`,
      timeline: {
        leftTime: formatTime(departureAt),
        leftLabel: origin || "---",
        middleLabel: `${durationMinutes} dk`,
        middleSubLabel: provider || "Supplier",
        rightTime: formatTime(arrivalAt),
        rightLabel: destination || "---",
      },
      meta: [
        { label: "Paket", value: fareFamily },
        { label: "Bagaj", value: baggageSummary },
      ],
    };
  }

  if (item.item_type === "hotel") {
    const neighborhood = readString(payload, "neighborhood");
    const city = readString(payload, "city");
    const boardType = readString(payload, "board_type", "Oda");
    const roomName = readString(payload, "room_name", "Standart oda");
    const guestScore = readNumber(payload, "guest_score");
    const starRating = readNumber(payload, "star_rating");

    return {
      kind: "hotel",
      title: readString(payload, "name", item.title),
      subtitle: neighborhood ? `${city} / ${neighborhood}` : city,
      meta: [
        { label: "Pansiyon", value: boardType },
        { label: "Oda", value: roomName },
        { label: "Puan", value: guestScore > 0 ? `${guestScore}/10` : "-" },
        { label: "Yildiz", value: starRating > 0 ? `${starRating}` : "-" },
      ],
    };
  }

  if (item.item_type === "car") {
    const vehicleName = readString(payload, "vehicle_name", item.title);
    const vendorName = readString(payload, "vendor_name");
    const category = readString(payload, "category");
    const transmission = readString(payload, "transmission");
    const pickupLocation = readString(payload, "pickup_location");
    const dropoffLocation = readString(payload, "dropoff_location");
    const fuelPolicy = readString(payload, "fuel_policy");

    return {
      kind: "car",
      title: vehicleName,
      subtitle: vendorName ? `${vendorName} / ${category}` : category,
      meta: [
        { label: "Alis", value: pickupLocation || "-" },
        { label: "Teslim", value: dropoffLocation || "-" },
        { label: "Vites", value: transmission || "-" },
        { label: "Yakit", value: fuelPolicy || "-" },
      ],
    };
  }

  return {
    kind: "generic",
    title: item.title,
    subtitle: item.item_type,
    meta: [],
  };
}
