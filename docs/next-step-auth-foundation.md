# Next Step - Auth Foundation

## Goal

The next implementation phase should build the identity backbone for the whole platform.

## Planned deliverables

1. User entity and base tables
2. Roles and permissions
3. JWT access token flow
4. Refresh token rotation
5. Password hashing
6. Session tracking
7. Audit log hooks

## Why auth comes next

Auth is a cross-cutting concern for:

- customer panel
- admin panel
- booking ownership
- payment access control
- auditability

If we delay auth too much, later modules will need expensive rewrites.
