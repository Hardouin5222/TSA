from __future__ import annotations

from typing import Any, Dict, Optional


class SupplierAdapterError(Exception):
    """Base normalized supplier adapter error."""

    def __init__(
        self,
        message: str,
        error_code: str = "SUPPLIER_ERROR",
        status_code: int = 502,
        details: Optional[Dict[str, Any]] = None,
    ) -> None:
        super().__init__(message)
        self.message = message
        self.error_code = error_code
        self.status_code = status_code
        self.details = details or {}


class SupplierConfigurationError(SupplierAdapterError):
    def __init__(self, message: str = "Supplier adapter is not configured", details: Optional[Dict[str, Any]] = None) -> None:
        super().__init__(
            message=message,
            error_code="SUPPLIER_NOT_CONFIGURED",
            status_code=503,
            details=details,
        )


class SupplierValidationError(SupplierAdapterError):
    def __init__(self, message: str = "Invalid supplier request", details: Optional[Dict[str, Any]] = None) -> None:
        super().__init__(
            message=message,
            error_code="INVALID_SUPPLIER_REQUEST",
            status_code=422,
            details=details,
        )
