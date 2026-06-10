from typing import Any


def success_response(data: Any, message: str = "OK") -> dict[str, Any]:
    return {
        "success": True,
        "message": message,
        "data": data,
    }
