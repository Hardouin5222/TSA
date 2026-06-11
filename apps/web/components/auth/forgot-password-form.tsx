"use client";

import { FormEvent, useState } from "react";

import { apiRequest } from "@/lib/api";

type PasswordResetEnvelope = {
  success: boolean;
  message: string;
  data: {
    accepted: boolean;
  };
};

export function ForgotPasswordForm() {
  const [email, setEmail] = useState("");
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [feedback, setFeedback] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setIsSubmitting(true);
    setFeedback(null);
    setError(null);

    try {
      const payload = await apiRequest<PasswordResetEnvelope>("/api/auth/password-reset/request", {
        method: "POST",
        body: { email },
      });
      setFeedback(payload.message);
    } catch (submitError) {
      setError(submitError instanceof Error ? submitError.message : "Request failed");
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <form className="auth-form" onSubmit={handleSubmit}>
      <div className="auth-form-header">
        <h2>Sifre sifirlama</h2>
        <p>Bu asamada teslim akisi backend seviyesinde hazir. E-posta gonderimi sonraki sprintte baglanacak.</p>
      </div>

      <label className="auth-field">
        <span>E-posta</span>
        <input
          autoComplete="email"
          onChange={(event) => setEmail(event.target.value)}
          placeholder="ornek@email.com"
          required
          type="email"
          value={email}
        />
      </label>

      {feedback ? <div className="form-feedback success">{feedback}</div> : null}
      {error ? <div className="form-feedback error">{error}</div> : null}

      <button className="primary-action auth-submit" disabled={isSubmitting} type="submit">
        {isSubmitting ? "Gonderiliyor..." : "Sifirlama talebi olustur"}
      </button>
    </form>
  );
}
