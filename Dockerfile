FROM php:8.0-cli-alpine

WORKDIR /app

COPY builds/opsie-status-operator /app/opsie-status-operator

CMD ["/app/opsie-status-operator", "watch:resource"]
