<?php

declare(strict_types=1);

namespace App\Infrastructure\Registration;

use App\Infrastructure\DB\SchemaSqlHelper;
use Doctrine\DBAL\Connection;

final class VerificationUsersStorage
{
    private const TABLE = 'verification_users';

    public function __construct(
        private Connection $connection,
        private readonly SchemaSqlHelper $schemaSqlHelper
    ) {}

    /*
    |--------------------------------------------------------------------------
    | CREATE
    |--------------------------------------------------------------------------
    */

    public function createWithCode(
        string $requestId,
        string $legalEntity,
        ?string $companyId,
        string $roleId,
        string $countryId,
        string $email,
        string $lastName,
        string $firstName,
        ?string $patronymic,
        string $phoneNumber,
        string $verificationChannelId,
        ?string $verificationCode,
        int $verificationAttempts
    ): void {

        $sql = "
            INSERT INTO " . $this->schemaSqlHelper->table(self::TABLE) . " (
                request_id,
                legal_entity,
                company_id,
                role_id,
                country_id,
                email,
                last_name,
                first_name,
                patronymic,
                phone_number,
                verification_channel_id,
                verification_code,
                verification_attempts,
                is_verified,
                created_at,
                updated_at
            )
            VALUES (
                :request_id,
                :legal_entity,
                :company_id,
                :role_id,
                :country_id,
                :email,
                :last_name,
                :first_name,
                :patronymic,
                :phone_number,
                :verification_channel_id,
                :verification_code,
                :verification_attempts,
                FALSE,
                NOW(),
                NOW()
            )
        ";

        $this->connection->executeStatement($sql, [
            'request_id' => $requestId,
            'legal_entity' => $legalEntity,
            'company_id' => $companyId,
            'role_id' => $roleId,
            'country_id' => $countryId,
            'email' => $email,
            'last_name' => $lastName,
            'first_name' => $firstName,
            'patronymic' => $patronymic,
            'phone_number' => $phoneNumber,
            'verification_channel_id' => $verificationChannelId,
            'verification_code' => $verificationCode,
            'verification_attempts' => $verificationAttempts,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function saveCode(string $requestId, string $code): void
    {
        $sql = "
            UPDATE " . $this->schemaSqlHelper->table(self::TABLE) . "
            SET verification_code = :code,
                updated_at = NOW()
            WHERE request_id = :request_id
        ";

        $this->connection->executeStatement($sql, [
            'request_id' => $requestId,
            'code' => $code,
        ]);
    }

    public function incrementAttempts(string $requestId): void
    {
        $sql = "
            UPDATE " . $this->schemaSqlHelper->table(self::TABLE) . "
            SET verification_attempts = verification_attempts + 1,
                updated_at = NOW()
            WHERE request_id = :request_id
        ";

        $this->connection->executeStatement($sql, [
            'request_id' => $requestId,
        ]);
    }

    public function updateSendTime(string $requestId): void
    {
        $sql = "
            UPDATE " . $this->schemaSqlHelper->table(self::TABLE) . "
            SET updated_at = NOW()
            WHERE request_id = :request_id
        ";

        $this->connection->executeStatement($sql, [
            'request_id' => $requestId,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | VERIFICATION FLOW
    |--------------------------------------------------------------------------
    */

    public function markVerified(string $requestId, ?string $channelCode = null): void
    {
        $sql = "
            UPDATE " . $this->schemaSqlHelper->table(self::TABLE) . "
            SET is_verified = TRUE,
                verification_channel_id = COALESCE(:channel_code, verification_channel_id),
                updated_at = NOW()
            WHERE request_id = :request_id
              AND is_verified = FALSE
        ";

        $this->connection->executeStatement($sql, [
            'request_id' => $requestId,
            'channel_code' => $channelCode,
        ]);
    }

    public function markFailed(string $requestId): void
    {
        $sql = "
            UPDATE " . $this->schemaSqlHelper->table(self::TABLE) . "
            SET updated_at = NOW()
            WHERE request_id = :request_id
        ";

        $this->connection->executeStatement($sql, [
            'request_id' => $requestId,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | READ
    |--------------------------------------------------------------------------
    */

    public function findByRequestId(string $requestId): ?array
    {
        $sql = "
            SELECT *
            FROM " . $this->schemaSqlHelper->table(self::TABLE) . "
            WHERE request_id = :request_id
            LIMIT 1
        ";

        $result = $this->connection->fetchAssociative($sql, [
            'request_id' => $requestId,
        ]);

        return $result ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $sql = "
            SELECT *
            FROM " . $this->schemaSqlHelper->table(self::TABLE) . "
            WHERE email = :email
            LIMIT 1
        ";

        $result = $this->connection->fetchAssociative($sql, [
            'email' => $email,
        ]);

        return $result ?: null;
    }

    /*
    |--------------------------------------------------------------------------
    | RATE LIMIT
    |--------------------------------------------------------------------------
    */

    public function hasActiveSmsVerification(string $phoneNumber, int $ttl): bool
    {
        $sql = "
            SELECT 1
            FROM " . $this->schemaSqlHelper->table(self::TABLE) . "
            WHERE phone_number = :phone_number
              AND is_verified = FALSE
              AND updated_at >= (NOW() - (:ttl * INTERVAL '1 second'))
            LIMIT 1
        ";

        return (bool) $this->connection->fetchOne($sql, [
            'phone_number' => $phoneNumber,
            'ttl' => $ttl,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Считаем сокращение попыток регистрации
    |--------------------------------------------------------------------------
    */
    public function updateVerificationAttemptsLeft(string $requestId, int $attemptsLeft): void
    {
        $sql = "
        UPDATE " . $this->schemaSqlHelper->table(self::TABLE) . "
        SET verification_attempts = :attempts_left
        WHERE request_id = :request_id
    ";

        $this->connection->executeStatement($sql, [
            'request_id' => $requestId,
            'attempts_left' => $attemptsLeft,
        ]);
    }
}
