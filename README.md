# Справочники Service

Микросервис **«Справочники»** предоставляет REST-API для трёх справочников с полным CRUD и массовым импортом из Excel/CSV через очередь RabbitMQ. Упакован в Docker Compose и готов к развёртыванию на любой машине с Docker и Docker-Compose.

---

## 📦 Структура проекта

- **services:**
    - `app` – PHP-FPM Laravel-приложение
    - `nginx` – обратный прокси и статика
    - `db` – PostgreSQL 14
    - `redis` – кэш/драйвер очередей
    - `rabbitmq` – RabbitMQ с management UI
    - `queue-worker` – воркер очередей Laravel


- **Справочники (API-ресурсы):**
    1. **SWIFT-коды** (`/api/swift-codes`)
    2. **Бюджетополучатели** (`/api/budget-holders`)
    3. **Казначейские счета** (`/api/treasury-accounts`)