"use client";

import { useRouter } from "next/navigation";
import { FormEvent, useState } from "react";

import { apiRequest } from "@/lib/api";
import { saveSession } from "@/lib/auth";
import { getGuestSessionId } from "@/lib/guest-session";
import { claimGuestAssets } from "@/lib/session-bridge";
import type { AuthEnvelope } from "@/types/auth";

export function RegisterForm() {
  const router = useRouter();
  const [form, setForm] = useState({
    first_name: "",
    last_name: "",
    email: "",
    password: "",
    phone_number: "",
  });
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  function updateField(name: keyof typeof form, value: string) {
    setForm((current) => ({ ...current, [name]: value }));
  }

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setIsSubmitting(true);
    setError(null);

    try {
      const payload = await apiRequest<AuthEnvelope>("/api/auth/register", {
        method: "POST",
        headers: getGuestSessionId() ? { "X-Guest-Session-Id": getGuestSessionId() as string } : undefined,
        body: {
          ...form,
          phone_number: form.phone_number || null,
        },
      });

      saveSession(payload.data);
      await claimGuestAssets(payload.data);
      router.push("/account");
      router.refresh();
    } catch (submitError) {
      setError(submitError instanceof Error ? submitError.message : "Registration failed");
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <form className="auth-form" onSubmit={handleSubmit}>
      <div className="auth-form-header">
        <h2>Hesap olustur</h2>
        <p>Tek hesapla ucak, otel, arac ve paket akislarini yonetmeye basla.</p>
      </div>

      <div className="auth-split-grid">
        <label className="auth-field">
          <span>Ad</span>
          <input
            autoComplete="given-name"
            onChange={(event) => updateField("first_name", event.target.value)}
            placeholder="Atinc"
            required
            value={form.first_name}
          />
        </label>

        <label className="auth-field">
          <span>Soyad</span>
          <input
            autoComplete="family-name"
            onChange={(event) => updateField("last_name", event.target.value)}
            placeholder="Egemen"
            required
            value={form.last_name}
          />
        </label>
      </div>

      <label className="auth-field">
        <span>E-posta</span>
        <input
          autoComplete="email"
          onChange={(event) => updateField("email", event.target.value)}
          placeholder="ornek@email.com"
          required
          type="email"
          value={form.email}
        />
      </label>

      <label className="auth-field">
        <span>Telefon</span>
        <input
          autoComplete="tel"
          onChange={(event) => updateField("phone_number", event.target.value)}
          placeholder="+90 5xx xxx xx xx"
          value={form.phone_number}
        />
      </label>

      <label className="auth-field">
        <span>Sifre</span>
        <input
          autoComplete="new-password"
          minLength={8}
          onChange={(event) => updateField("password", event.target.value)}
          placeholder="En az 8 karakter"
          required
          type="password"
          value={form.password}
        />
      </label>

      <label className="checkbox-row checkbox-card">
        <input required type="checkbox" />
        <span>Kullanim kosullari ve gizlilik metnini okudum.</span>
      </label>

      {error ? <div className="form-feedback error">{error}</div> : null}

      <button className="primary-action auth-submit" disabled={isSubmitting} type="submit">
        {isSubmitting ? "Hesap olusturuluyor..." : "Hesap olustur"}
      </button>
    </form>
  );
}
