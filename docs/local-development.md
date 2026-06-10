# Local Development

## Current scope

This setup currently includes:

- API Gateway
- User Service
- PostgreSQL
- Redis
- RabbitMQ
- MinIO

## Start the local platform

1. Copy `.env.example` to `.env`
2. Update passwords and secrets
3. Start the stack with Docker Compose

Recommended first checks:

- API Gateway: `http://localhost:8000/`
- API Gateway live health: `http://localhost:8000/api/health/live`
- User Service live health: `http://localhost:8001/api/health/live`
- RabbitMQ UI: `http://localhost:15672`
- MinIO Console: `http://localhost:9001`

## Why this matters

We are intentionally building the platform around stable operational primitives first:

- reproducible local environments
- isolated service boundaries
- shared logging and middleware
- predictable healthchecks

This reduces rework when auth, bookings, payments and provider integrations are added.
