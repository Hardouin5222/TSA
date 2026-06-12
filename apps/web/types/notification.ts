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
    }>;
  };
};
