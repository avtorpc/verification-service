# Место — verification-service

Сервис регистрации соискателей и работодателей с подтверждением email. Актуальный сценарий, ограничения и интеграции описаны в [registration-mvp.md](docs/registration-mvp.md).

## База данных

В схеме verification две прикладные таблицы:

- signup_requests — заявки, регистрационные поля, код/HMAC, временный хеш пароля, сроки и счётчики;
- registration_outbox — сохраняемые задания отправки письма через Kafka и создания аккаунта через внутренний API auth-service.

Семь таблиц старого company/SMS-модуля удаляются миграцией Version20261008120000. Исторические миграции сохранены для обновления ранее развёрнутых баз. Чистая установка завершает весь набор миграций и получает тот же состав таблиц. Обратная миграция очистки запрещена; для восстановления старых данных используется резервная копия.

## Код и API

Controller/EmailRegistrationController принимает /quick-signup, /check-code-email, /resend-code-email, /cancel, /status. Registration/Email/RegistrationService управляет состояниями и ограничениями; RegistrationTransport отправляет события и создаёт аккаунт; app:registration:dispatch обрабатывает очередь. Старые company/SMS-контроллеры, обработчики, модели, репозитории и их тесты удалены.

## Запуск и проверки

Все команды запуска находятся в ONMI_infra: make registration-up (также make web-up), make registration-test. Сайт: http://localhost:8080; почта: http://localhost:8025.

В контейнере verification_service_php: php bin/console doctrine:migrations:migrate --no-interaction; php tests/schema-cleanup-contract.php проверяет чистую установку и обновление в временных базах. Сквозная проверка из корня: python3 services/web-service/tests/registration-e2e.py. Она создаёт тестовые аккаунты example.invalid.

Резервная копия перед очисткой: ONMI_infra/storage/backups/registration-before-cleanup-20261008.sql.
