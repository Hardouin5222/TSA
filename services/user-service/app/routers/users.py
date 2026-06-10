from fastapi import APIRouter

from travel_shared.responses import success_response

router = APIRouter(prefix="/users", tags=["users"])


@router.get("/me")
async def get_current_user_placeholder() -> dict:
    return success_response(
        {
            "id": None,
            "email": None,
            "status": "auth-module-not-implemented-yet",
        },
        message="User module placeholder",
    )
