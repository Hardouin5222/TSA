export type AuthEnvelope = {
  success: boolean;
  message: string;
  data: {
    user: {
      id: string;
      email: string;
      first_name: string;
      last_name: string;
      phone_number: string | null;
      status: string;
      is_email_verified: boolean;
      created_at: string;
    };
    tokens: {
      access_token: string;
      refresh_token: string;
      token_type: string;
    };
  };
};
