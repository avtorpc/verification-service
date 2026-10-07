<?php

namespace App\Infrastructure\Registration;

use App\Domain\Registration\SignupRequestStorageInterface;
use App\Infrastructure\DB\SchemaSqlHelper;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class SignupRequestStorage implements SignupRequestStorageInterface
{
    private const TABLE = 'signup_requests';

    public function __construct(
        private Connection $connection,
        private readonly SchemaSqlHelper $schemaSqlHelper
    ) {}

    /**
     * Имя стоража (аналог getName() у словарей)
     */
    public function getName(): string
    {
        return 'signup-request';
    }

    /**
     * Первое сохранение данных пользователя при быстрой регистрации
     *
     * @param string $requestId
     * @param string $email
     * @param string $roleCode
     * @param string $country
     * @param string $statusCode
     * @param bool $acceptTerms
     * @param bool|null $acceptMarketing
     * @param string|null $acceptMarketingVer
     * @param string|null $urlPageCheckout
     * @param string|null $userAgent
     * @param string|null $ip
     * @return void
     * @throws \Doctrine\DBAL\Exception
     */
    public function create(
        string $requestId,
        string $email,
        string $roleCode,
        string $country,
        string $statusCode,
        bool $acceptTerms,
        bool $acceptMarketing,
        ?string $acceptMarketingVer,
        ?string $urlPageCheckout,
        ?string $userAgent,
        ?string $ip
    ): void {
        $sql = "
        INSERT INTO  " . $this->schemaSqlHelper->table(self::TABLE) . "(
            request_id,
            email,
            role_code,
            country_alpha2,
            status,
            accept_terms,
            accept_marketing,
            accept_marketing_ver,
            url_page_checkout,
            user_agent,
            ip_address,
            created_at
        )
        VALUES (
            :request_id,
            :email,
            :role_code,
            :country_alpha2,
            :status_code,
            :accept_terms,
            :accept_marketing,
            :accept_marketing_ver,
            :url_page_checkout,
            :user_agent,
            :ip_address,
            NOW()
        )
    ";

        $this->connection->executeStatement($sql, [
            'request_id' => $requestId,
            'email' => $email,
            'role_code' => $roleCode,
            'country_alpha2' => $country,
            'status_code' => $statusCode,

            'accept_terms' => $acceptTerms,
            'accept_marketing' => $acceptMarketing,
            'accept_marketing_ver' => $acceptMarketingVer,
            'url_page_checkout' => $urlPageCheckout,
            'user_agent' => $userAgent,
            'ip_address' => $ip,
        ],
            [
                'accept_terms' => ParameterType::BOOLEAN,
                'accept_marketing' => ParameterType::BOOLEAN,
                'verification_attempts' => ParameterType::INTEGER,
            ]
        );
    }


    public function createWithCode(
        string $requestId,
        string $email,
        string $roleCode,
        string $country,
        string $statusCode,
        string $code,
        int $verification_attempts,
        bool $acceptTerms,
        bool $acceptMarketing,
        ?string $acceptMarketingVer,
        ?string $urlPageCheckout,
        ?string $userAgent,
        ?string $ip
    ): void {
        $sql = "
        INSERT INTO  " . $this->schemaSqlHelper->table(self::TABLE) . "(
            request_id,
            email,
            role_code,
            country_alpha2,
            status,
            verification_code,
            verification_attempts,
            accept_terms,
            accept_marketing,
            accept_marketing_ver,
            url_page_checkout,
            user_agent,
            ip_address,
            created_at
        )
        VALUES (
            :request_id,
            :email,
            :role_code,
            :country_alpha2,
            :status_code,
            :verification_code,
            :verification_attempts,
            :accept_terms,
            :accept_marketing,
            :accept_marketing_ver,
            :url_page_checkout,
            :user_agent,
            :ip_address,
            NOW()
        )
    ";

        $this->connection->executeStatement($sql, [
            'request_id' => $requestId,
            'email' => $email,
            'role_code' => $roleCode,
            'country_alpha2' => $country,
            'status_code' => $statusCode,
            'verification_code' => $code,
            'verification_attempts'=> $verification_attempts,
            'accept_terms' => $acceptTerms,
            'accept_marketing' => $acceptMarketing,
            'accept_marketing_ver' => $acceptMarketingVer,
            'url_page_checkout' => $urlPageCheckout,
            'user_agent' => $userAgent,
            'ip_address' => $ip,
        ],
            [
                'accept_terms' => ParameterType::BOOLEAN,
                'accept_marketing' => ParameterType::BOOLEAN,
                'verification_attempts' => ParameterType::INTEGER,
            ]
        );
    }

    /**
     * Сохраняем код верификаии пользователя в базу
     *
     * @param string $requestId
     * @param string $code
     * @return void
     * @throws \Doctrine\DBAL\Exception
     */
    public function saveCode(string $requestId, string $code): void
    {
        $this->connection->update(
            $this->schemaSqlHelper->table(self::TABLE) ,
            ['verification_code' => $code],
            ['request_id' => $requestId]
        );
    }

    /**
     * Проставляем время сохранения кода верификации пользователя в базу данных
     *
     * @param string $requestId
     * @return void
     * @throws \Doctrine\DBAL\Exception
     */
    public function updateSendTime(string $requestId): void
    {
        $sql = "
        UPDATE  " . $this->schemaSqlHelper->table(self::TABLE) . "
        SET date_time = CURRENT_TIMESTAMP
        WHERE request_id = :request_id
    ";

        $this->connection->executeStatement($sql, [
            'request_id' => $requestId
        ]);
    }

    /**
     * Проверка по email
     * Пытался ли этот email зарегистрироваться в последние N секунд?
     * 429 Too Many Requests
     *
     * @param string $email
     * @param int $seconds
     * @return bool
     * @throws \Doctrine\DBAL\Exception
     */
    public function hasRecentSignupByEmail(string $email, int $seconds): bool
    {
        $sql = "
        SELECT 1
        FROM  " . $this->schemaSqlHelper->table(self::TABLE) . "
        WHERE email = :email
        AND created_at > NOW() - (:seconds * INTERVAL '1 second')
        LIMIT 1
    ";

        return (bool) $this->connection->fetchOne($sql, [
            'email' => $email,
            'seconds' => $seconds
        ]);
    }

    /**
     * Проверка активной регистрации - нельзя создавать новую заявку, пока предыдущая в процессе.
     * 429 Too Many Requests
     *
     * @param string $email
     * @param int $ttl
     * @return bool
     * @throws \Doctrine\DBAL\Exception
     */
    public function hasActiveSignup(string $email, int $ttl): bool
    {
        $sql = "
        SELECT 1
        FROM  " . $this->schemaSqlHelper->table(self::TABLE) . "
        WHERE email = :email
        AND is_verified = false
        AND expires_at > CURRENT_TIMESTAMP
        LIMIT 1
    ";

        return (bool) $this->connection->fetchOne($sql, [
            'email' => $email,
        ]);
    }

    /**
     * Проверка по IP - активность регистраций с одного IP
     * 429 Too Many Requests
     *
     * @param string $ip
     * @param int $seconds
     * @return int
     * @throws \Doctrine\DBAL\Exception
     */
    public function countRecentByIp(string $ip, int $seconds): int
    {
        $sql = "
        SELECT count(*)
        FROM  " . $this->schemaSqlHelper->table(self::TABLE) . "
        WHERE ip_address = :ip
        AND created_at > NOW() - (:seconds * INTERVAL '1 second')
    ";

        return (int) $this->connection->fetchOne($sql, [
            'ip' => $ip,
            'seconds' => $seconds
        ]);
    }

    /**
     * Записываем что пользователь ввел код правильно и его email верифицирован
     *
     * @param string $requestId
     * @return void
     * @throws \Doctrine\DBAL\Exception
     */
    public function markVerified(string $requestId): void
    {
        $updated = $this->connection->executeStatement(
            'UPDATE '.$this->schemaSqlHelper->table(self::TABLE).'
             SET is_verified = true
             WHERE request_id = :id AND is_verified = false AND expires_at > clock_timestamp()',
            ['id' => $requestId]
        );
        if ($updated === 0) {
            $registration = $this->findByRequestId($requestId);
            if (!$registration || !$registration['is_verified']) {
                throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException('Время регистрации истекло');
            }
        }
    }

    public function findByRequestId(string $requestId): ?array
    {
        $sql = "
        SELECT *
        FROM " . $this->schemaSqlHelper->table(self::TABLE) . "
        WHERE request_id = :request_id
        LIMIT 1
    ";

        $result = $this->connection->fetchAssociative($sql, [
            'request_id' => $requestId
        ]);

        return $result ?: null;
    }

    /** Atomically reserve a user-requested generation; delivery retries never call this. */
    public function reserveEmailResend(string $requestId, int $maxResends, int $interval): ?array
    {
        $sql = 'UPDATE '.$this->schemaSqlHelper->table(self::TABLE).'
            SET resend_attempts = resend_attempts + 1, date_time = clock_timestamp()
            WHERE request_id = :id AND is_verified = false
              AND expires_at > clock_timestamp() + INTERVAL \'1 second\'
              AND resend_attempts < :maximum
              AND date_time <= clock_timestamp() - (:interval * INTERVAL \'1 second\')
            RETURNING *, FLOOR(EXTRACT(EPOCH FROM (expires_at - clock_timestamp()))) AS remaining_seconds';
        return $this->connection->fetchAssociative($sql, [
            'id' => $requestId, 'maximum' => $maxResends, 'interval' => $interval,
        ]) ?: null;
    }

    public function incrementVerificationAttempts(string $requestId): void
    {
        $sql = "
        UPDATE " . $this->schemaSqlHelper->table(self::TABLE) . "
        SET verification_attempts = verification_attempts + 1
        WHERE request_id = :request_id
    ";

        $this->connection->executeStatement($sql, [
            'request_id' => $requestId
        ]);
    }

    public function markFailed(string $requestId, int $attempts, int $maxAttempt): void
    {
        $sql = "
        UPDATE " . $this->schemaSqlHelper->table(self::TABLE) . "
        SET verification_attempts = :attempts
        WHERE request_id = :request_id
    ";

        $this->connection->executeStatement($sql, [
            'request_id' => $requestId,
            'attempts' => $attempts
        ]);
    }

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
