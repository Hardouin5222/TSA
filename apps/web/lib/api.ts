export const API_BASE_URL =
  process.env.NEXT_PUBLIC_API_BASE_URL?.replace(/\/$/, "") || "http://localhost";

type ApiOptions = {
  method?: "GET" | "POST";
  body?: unknown;
  token?: string;
};

export async function apiRequest<T>(path: string, options: ApiOptions = {}): Promise<T> {
  const response = await fetch(`${API_BASE_URL}${path}`, {
    method: options.method || "GET",
    headers: {
      "Content-Type": "application/json",
      ...(options.token ? { Authorization: `Bearer ${options.token}` } : {}),
    },
    body: options.body ? JSON.stringify(options.body) : undefined,
    cache: "no-store",
  });

  const payload = await response.json().catch(() => null);

  if (!response.ok) {
    const message =
      payload && typeof payload === "object" && "detail" in payload
        ? String((payload as { detail: string }).detail)
        : "Unexpected API error";
    throw new Error(message);
  }

  return payload as T;
}
