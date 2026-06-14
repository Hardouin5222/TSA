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

export function getGuestSessionId(): string | null {
  if (typeof window === "undefined") {
    return null;
  }

  try {
    return window.localStorage.getItem(GUEST_STORAGE_KEY);
  } catch {
    return null;
  }
}

export function clearGuestSessionId() {
  if (typeof window === "undefined") {
    return;
  }

  try {
    window.localStorage.removeItem(GUEST_STORAGE_KEY);
  } catch {
    return;
  }
}
