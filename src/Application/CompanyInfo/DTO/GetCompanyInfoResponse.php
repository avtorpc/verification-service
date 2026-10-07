<?php

namespace App\Application\CompanyInfo\DTO;

use App\Shared\Time\ClockInterface;

final class GetCompanyInfoResponse
{
    public function __construct(
        private array $companyInfo,
        private ClockInterface $clock,
    ) {}

    public function toArray(): array
    {
        return [
            'success' => true,
            'timestamp' => $this->clock->nowFormatted(),
            'message' => 'Данные компании успешно получены',
            'data' => $this->companyInfo,
        ];
    }
}
