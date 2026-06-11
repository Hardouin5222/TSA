"use client";

const GUEST_STORAGE_KEY = "tsa.guest.session";

export function getOrCreateGuestSessionId(): string {
  if (typeof window === "undefined") {
    return "server-guest";
  }

  const current = window.localStorage.getItem(GUEST_STORAGE_KEY);
  if (current) {
    return current;
  }

  const generated = `guest_${crypto.randomUUID()}`;
  window.localStorage.setItem(GUEST_STORAGE_KEY, generated);
  return generated;
}
