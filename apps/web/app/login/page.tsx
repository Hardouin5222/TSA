import { AuthShell } from "@/components/auth/auth-shell";
import { LoginForm } from "@/components/auth/login-form";

export default function LoginPage() {
  return (
    <AuthShell
      description="Kisa, guvenli ve mobilde rahat bir oturum acma akisi ile kullaniciyi tekrar satin alma niyetine geri donduruyoruz."
      helperHref="/register"
      helperLabel="Hesabin yok mu?"
      helperText="Kayit ol"
      title="Rezervasyonlarina ve hizli checkout akisina ulas."
    >
      <LoginForm />
    </AuthShell>
  );
}
