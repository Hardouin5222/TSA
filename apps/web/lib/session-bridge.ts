"use client";

import { apiRequest } from "@/lib/api";
import type { StoredSession } from "@/lib/auth";
import { clearGuestSessionId, getGuestSessionId } from "@/lib/guest-session";

type ClaimResponse = {
  success: boolean;
  message: string;
  data: {
    claimed_count?: number;
  };
};

export async function claimGuestAssets(session: StoredSession) {
  const guestSessionId = getGuestSessionId();
  if (!guestSessionId) {
    return;
  }

  const token = session.tokens.access_token;
  const body = {
    guest_session_id: guestSessionId,
    user_id: session.user.id,
  };

  await Promise.allSettled([
    apiRequest<ClaimResponse>("/api/cart/claim-guest", {
      method: "POST",
      token,
      body,
    }),
    apiRequest<ClaimResponse>("/api/bookings/claim-guest", {
      method: "POST",
      token,
      body,
    }),
  ]);

  clearGuestSessionId();
}
