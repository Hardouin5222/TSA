"use client";

const GUEST_STORAGE_KEY = "tsa.guest.session";

function createGuestSessionId() {
  const browserCrypto = globalThis.crypto;

  if (browserCrypto && typeof browserCrypto.randomUUID === "function") {
    return `guest_${browserCrypto.randomUUID()}`;
  }

  const fallback = `${Date.now()}_${Math.random().toString(36).slice(2, 10)}`;
  return `guest_${fallback}`;
}

export function getOrCreateGuestSessionId(): string {
  if (typeof window === "undefined") {
    return "server-guest";
  }

  try {
    const current = window.localStorage.getItem(GUEST_STORAGE_KEY);
    if (current) {
      return current;
    }

    const generated = createGuestSessionId();
    window.localStorage.setItem(GUEST_STORAGE_KEY, generated);
    return generated;
  } catch {
    return createGuestSessionId();
  }
}
