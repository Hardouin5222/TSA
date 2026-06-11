import { AuthShell } from "@/components/auth/auth-shell";
import { RegisterForm } from "@/components/auth/register-form";

export default function RegisterPage() {
  return (
    <AuthShell
      description="Ilk kayittan sonra kullaniciyi dogrudan paneline ve sonraki satin alma adimlarina tasiyan temiz bir onboarding kuruyoruz."
      helperHref="/login"
      helperLabel="Zaten hesabin var mi?"
      helperText="Giris yap"
      title="Tek hesapla tum seyahat akislarini yonet."
    >
      <RegisterForm />
    </AuthShell>
  );
}
