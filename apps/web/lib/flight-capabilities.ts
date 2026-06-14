type GenericPayload = Record<string, unknown>;

type FareOptionSnapshot = {
  seat_selection: boolean;
  meal_included: boolean;
  service_flags?: {
    seat_selection?: boolean;
    meal_selection?: boolean;
    refundable?: boolean;
    exchangeable?: boolean;
  };
};

type FlightCapabilitySummary = {
  seatSelectionSupported: boolean;
  mealSelectionSupported: boolean;
  travelerNoteSupported: boolean;
  brandedFaresSupported: boolean;
};

function isObject(value: unknown): value is Record<string, unknown> {
  return typeof value === "object" && value !== null;
}

function readBoolean(value: unknown): boolean {
  return value === true;
}

export function getFlightCapabilitySummary(payload: GenericPayload): FlightCapabilitySummary {
  const rawCapabilities = isObject(payload.capabilities) ? payload.capabilities : {};

  return {
    seatSelectionSupported: readBoolean(rawCapabilities.seat_selection_supported),
    mealSelectionSupported: readBoolean(rawCapabilities.meal_selection_supported),
    travelerNoteSupported: readBoolean(rawCapabilities.traveler_note_supported),
    brandedFaresSupported: readBoolean(rawCapabilities.branded_fares_supported),
  };
}

export function getFareOptionServiceFlags(option: FareOptionSnapshot | null) {
  if (!option) {
    return {
      seatSelection: false,
      mealSelection: false,
    };
  }

  return {
    seatSelection: readBoolean(option.service_flags?.seat_selection) || option.seat_selection === true,
    mealSelection: readBoolean(option.service_flags?.meal_selection) || option.meal_included === true,
  };
}
