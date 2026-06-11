"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { FormEvent, useState } from "react";

import { apiRequest } from "@/lib/api";
import { saveSession } from "@/lib/auth";
import type { AuthEnvelope } from "@/types/auth";

export function LoginForm() {
  const router = useRouter();
  const [email, setEmail] = useState("test@example.com");
  const [password, setPassword] = useState("StrongPass123!");
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setIsSubmitting(true);
    setError(null);

    try {
      const payload = await apiRequest<AuthEnvelope>("/api/auth/login", {
        method: "POST",
        body: { email, password },
      });

      saveSession(payload.data);
      router.push("/account");
      router.refresh();
    } catch (submitError) {
      setError(submitError instanceof Error ? submitError.message : "Login failed");
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <form className="auth-form" onSubmit={handleSubmit}>
      <div className="auth-form-header">
        <h2>Giris yap</h2>
        <p>Rezervasyonlarini gormek, hizli checkout yapmak ve paneline ulasmak icin giris yap.</p>
      </div>

      <label className="auth-field">
        <span>E-posta</span>
        <input
          autoComplete="email"
          name="email"
          onChange={(event) => setEmail(event.target.value)}
          placeholder="ornek@email.com"
          required
          type="email"
          value={email}
        />
      </label>

      <label className="auth-field">
        <span>Sifre</span>
        <input
          autoComplete="current-password"
          name="password"
          onChange={(event) => setPassword(event.target.value)}
          placeholder="Sifrenizi girin"
          required
          type="password"
          value={password}
        />
      </label>

      <div className="auth-inline-row">
        <label className="checkbox-row">
          <input type="checkbox" />
          <span>Beni hatirla</span>
        </label>
        <Link href="/forgot-password">Sifremi unuttum</Link>
      </div>

      {error ? <div className="form-feedback error">{error}</div> : null}

      <button className="primary-action auth-submit" disabled={isSubmitting} type="submit">
        {isSubmitting ? "Giris yapiliyor..." : "Giris yap"}
      </button>
    </form>
  );
}
