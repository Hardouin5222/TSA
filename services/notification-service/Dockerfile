FROM python:3.12-slim

ENV PYTHONDONTWRITEBYTECODE=1
ENV PYTHONUNBUFFERED=1

WORKDIR /app

COPY packages/shared-python /app/packages/shared-python
COPY services/notification-service /app/services/notification-service

RUN pip install --no-cache-dir --upgrade pip && \
    pip install --no-cache-dir /app/packages/shared-python /app/services/notification-service

EXPOSE 8006

CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8006"]
