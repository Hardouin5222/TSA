import uuid
from datetime import datetime

from pydantic import BaseModel, ConfigDict, EmailStr


class UserMeResponse(BaseModel):
    model_config = ConfigDict(from_attributes=True)

    id: uuid.UUID
    email: EmailStr
    first_name: str
    last_name: str
    phone_number: str | None
    status: str
    is_email_verified: bool
    created_at: datetime
