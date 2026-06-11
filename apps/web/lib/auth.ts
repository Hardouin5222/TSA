"use client";

export type SessionUser = {
  id: string;
  email: string;
  first_name: string;
  last_name: string;
  phone_number: string | null;
  status: string;
  is_email_verified: boolean;
  created_at: string;
};

export type SessionTokens = {
  access_token: string;
  refresh_token: string;
  token_type: string;
};

export type StoredSession = {
  user: SessionUser;
  tokens: SessionTokens;
};

const STORAGE_KEY = "tsa.auth.session";

export function saveSession(session: StoredSession) {
  if (typeof window === "undefined") {
    return;
  }
  window.localStorage.setItem(STORAGE_KEY, JSON.stringify(session));
}

export function getSession(): StoredSession | null {
  if (typeof window === "undefined") {
    return null;
  }

  const raw = window.localStorage.getItem(STORAGE_KEY);
  if (!raw) {
    return null;
  }

  try {
    return JSON.parse(raw) as StoredSession;
  } catch {
    window.localStorage.removeItem(STORAGE_KEY);
    return null;
  }
}

export function clearSession() {
  if (typeof window === "undefined") {
    return;
  }
  window.localStorage.removeItem(STORAGE_KEY);
}
