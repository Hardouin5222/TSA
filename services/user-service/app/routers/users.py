from fastapi import APIRouter, Depends

from app.api.dependencies import require_permissions
from app.models.user import User
from app.schemas.user import UserMeResponse
from travel_shared.responses import success_response

router = APIRouter(prefix="/users", tags=["users"])


@router.get("/me")
async def get_current_user(current_user: User = Depends(require_permissions("profile.read"))) -> dict:
    return success_response(
        UserMeResponse.model_validate(current_user).model_dump(mode="json"),
        message="Current user profile",
    )
