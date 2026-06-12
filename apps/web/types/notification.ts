export type NotificationListEnvelope = {
  success: boolean;
  message: string;
  data: {
    notifications: Array<{
      notification_id: string;
      booking_reference: string;
      template_code: string;
      channel: string;
      status: string;
      recipient_email: string | null;
      recipient_phone: string | null;
      provider: string;
      created_at: string;
      subject?: string | null;
      content_preview?: string | null;
      provider_reference?: string | null;
      sent_at?: string | null;
    }>;
  };
};

export type NotificationDetailEnvelope = {
  success: boolean;
  message: string;
  data: {
    notification_id: string;
    booking_reference: string;
    template_code: string;
    channel: string;
    status: string;
    recipient_email: string | null;
    recipient_phone: string | null;
    provider: string;
    subject: string | null;
    content_preview: string | null;
    provider_reference: string | null;
    sent_at: string | null;
    created_at: string;
    text_body: string | null;
    html_body: string | null;
  };
};

export type NotificationDispatchEnvelope = {
  success: boolean;
  message: string;
  data: {
    notification_id: string;
    booking_reference: string;
    status: string;
    provider_reference: string | null;
    sent_at: string | null;
  };
};
