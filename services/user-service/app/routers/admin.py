from fastapi import APIRouter, Depends

from app.api.dependencies import require_permissions
from app.models.user import User
from app.schemas.admin import AdminAccessResponse
from travel_shared.responses import success_response

router = APIRouter(prefix="/admin", tags=["admin"])


@router.get("/access-check")
async def admin_access_check(current_user: User = Depends(require_permissions("admin.access"))) -> dict:
    return success_response(
        AdminAccessResponse(access_granted=True, area="admin").model_dump(),
        message=f"Admin access granted for {current_user.email}",
    )
