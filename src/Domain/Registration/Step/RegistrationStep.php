<?php

namespace App\Domain\Registration\Step;

enum RegistrationStep: string
{
    case COMPANY_INFO = 'company_info';
    case ADDRESS = 'address';
    case LEADER = 'leader';
    case CONTACT = 'contact';
    case BANK_DETAIL = 'bank_detail';

    public function label(): string
    {
        return match ($this) {
            self::COMPANY_INFO => 'Основная информация',
            self::ADDRESS => 'Адреса',
            self::LEADER => 'Руководитель',
            self::CONTACT => 'Контакты',
            self::BANK_DETAIL => 'Банковские реквизиты',
        };
    }

    public function next(): ?self
    {
        return match ($this) {
            self::COMPANY_INFO => self::ADDRESS,
            self::ADDRESS => self::LEADER,
            self::LEADER => self::CONTACT,
            self::CONTACT => self::BANK_DETAIL,
            self::BANK_DETAIL => null,
        };
    }

    public static function orderedSteps(): array
    {
        return [self::COMPANY_INFO, self::ADDRESS, self::LEADER, self::CONTACT, self::BANK_DETAIL];
    }
}
