import { AuthShell } from "@/components/auth/auth-shell";
import { ForgotPasswordForm } from "@/components/auth/forgot-password-form";

export default function ForgotPasswordPage() {
  return (
    <AuthShell
      description="Bu ekran backend password reset foundation ile eslesir. E-posta teslim katmanini sonraki sprintte ekleyecegiz."
      helperHref="/login"
      helperLabel="Sifreni hatirladiysan"
      helperText="Giris yap"
      title="Hesabina donmek icin sifirlama akisini baslat."
    >
      <ForgotPasswordForm />
    </AuthShell>
  );
}
