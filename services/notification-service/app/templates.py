from app.schemas import CreateBookingConfirmationNotificationRequest


def render_booking_confirmation_template(
    payload: CreateBookingConfirmationNotificationRequest,
) -> dict[str, str]:
    subject = f"Rezervasyon onayi: {payload.booking_reference}"
    text_body = (
        f"Merhaba,\n\n"
        f"{payload.trip_summary} rezervasyonunuz olusturuldu.\n"
        f"Rezervasyon referansi: {payload.booking_reference}\n"
        f"Toplam tutar: {payload.total_amount:.0f} {payload.currency}\n"
        f"Rezervasyon detayi: {payload.booking_url}\n\n"
        f"Travel Super App"
    )
    html_body = f"""
    <html>
      <body style="font-family: Arial, sans-serif; color: #1f2d2a; background: #f8f4ec;">
        <div style="max-width: 640px; margin: 24px auto; background: #fffdf8; border: 1px solid #e8dece; border-radius: 18px; padding: 24px;">
          <p style="margin: 0 0 12px; color: #0f766e; font-weight: 700;">Travel Super App</p>
          <h1 style="margin: 0 0 16px; font-size: 28px;">Rezervasyonunuz hazir</h1>
          <p style="margin: 0 0 16px;">{payload.trip_summary} rezervasyonunuz basariyla olusturuldu.</p>
          <div style="display: grid; gap: 10px; margin: 0 0 18px;">
            <div><strong>Rezervasyon referansi:</strong> {payload.booking_reference}</div>
            <div><strong>Toplam tutar:</strong> {payload.total_amount:.0f} {payload.currency}</div>
            <div><strong>Detay linki:</strong> {payload.booking_url}</div>
          </div>
          <p style="margin: 0; color: #5e6d69;">Bu mesaj simdilik mock notification pipeline uzerinden uretilmistir.</p>
        </div>
      </body>
    </html>
    """.strip()

    return {
        "subject": subject,
        "text_body": text_body,
        "html_body": html_body,
        "content_preview": f"{payload.trip_summary} rezervasyonunuz hazir. Toplam {payload.total_amount:.0f} {payload.currency}.",
    }
