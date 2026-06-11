from pydantic import BaseModel


class AdminAccessResponse(BaseModel):
    access_granted: bool
    area: str
