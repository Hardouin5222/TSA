from fastapi import Request
from sqlalchemy.orm import Session

from app.models.audit import AuditLog


def create_audit_log(
    db: Session,
    *,
    event_type: str,
    entity_type: str,
    entity_id: str | None = None,
    actor_user_id: str | None = None,
    request: Request | None = None,
    metadata: dict | None = None,
) -> None:
    audit_log = AuditLog(
        actor_user_id=actor_user_id,
        event_type=event_type,
        entity_type=entity_type,
        entity_id=entity_id,
        ip_address=request.client.host if request and request.client else None,
        user_agent=request.headers.get("user-agent") if request else None,
        event_metadata=metadata,
    )
    db.add(audit_log)
