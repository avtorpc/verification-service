<?php

namespace App\Tests\Unit\Application\Registration\Step\Validation;

use App\Application\Registration\Step\Validation\StepDataValidator;
use App\Domain\Registration\Step\RegistrationStep;
use App\Shared\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

class StepDataValidatorTest extends TestCase
{
    private StepDataValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new StepDataValidator();
    }

    private static function validStepData(RegistrationStep $step): array
    {
        return match ($step) {
            RegistrationStep::ADDRESS => [
                'addressType' => 'legal',
                'countryCode' => 'RU',
                'region' => 'Московская обл.',
                'city' => 'Москва',
                'street' => 'Тверская',
                'house' => '1',
                'zipCode' => '101000',
            ],
            RegistrationStep::LEADER => [
                'firstName' => 'Иван',
                'lastName' => 'Петров',
                'documentTypeCode' => 'PASSPORT',
            ],
            RegistrationStep::CONTACT => [
                'email' => 'ivan@example.com',
                'phone' => '+79152314454',
            ],
            RegistrationStep::BANK_DETAIL => [
                'accountNumber' => '40702810123456789012',
                'bankName' => 'Сбербанк',
                'bik' => '044525974',
                'countryCode' => 'RU',
            ],
        };
    }

    public static function requiredFieldProvider(): array
    {
        return [
            'address missing addressType' => [RegistrationStep::ADDRESS, 'addressType'],
            'address missing countryCode' => [RegistrationStep::ADDRESS, 'countryCode'],
            'address missing region' => [RegistrationStep::ADDRESS, 'region'],
            'address missing city' => [RegistrationStep::ADDRESS, 'city'],
            'address missing street' => [RegistrationStep::ADDRESS, 'street'],
            'address missing house' => [RegistrationStep::ADDRESS, 'house'],
            'address missing zipCode' => [RegistrationStep::ADDRESS, 'zipCode'],
            'leader missing firstName' => [RegistrationStep::LEADER, 'firstName'],
            'leader missing lastName' => [RegistrationStep::LEADER, 'lastName'],
            'leader missing documentTypeCode' => [RegistrationStep::LEADER, 'documentTypeCode'],
            'contact missing email' => [RegistrationStep::CONTACT, 'email'],
            'contact missing phone' => [RegistrationStep::CONTACT, 'phone'],
            'bank_detail missing accountNumber' => [RegistrationStep::BANK_DETAIL, 'accountNumber'],
            'bank_detail missing bankName' => [RegistrationStep::BANK_DETAIL, 'bankName'],
            'bank_detail missing bik' => [RegistrationStep::BANK_DETAIL, 'bik'],
            'bank_detail missing countryCode' => [RegistrationStep::BANK_DETAIL, 'countryCode'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('requiredFieldProvider')]
    public function testRequiredFieldMissingThrowsException(RegistrationStep $step, string $field): void
    {
        $data = self::validStepData($step);
        unset($data[$field]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Поле '{$field}' обязательно для шага '{$step->label()}'");

        $this->validator->validate($step, $data);
    }

    public function testRequiredFieldEmptyStringThrowsException(): void
    {
        $data = self::validStepData(RegistrationStep::ADDRESS);
        $data['city'] = '';

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Поле 'city' обязательно для шага 'Адреса'");

        $this->validator->validate(RegistrationStep::ADDRESS, $data);
    }

    public function testRequiredFieldNullThrowsException(): void
    {
        $data = self::validStepData(RegistrationStep::LEADER);
        $data['firstName'] = null;

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Поле 'firstName' обязательно для шага 'Руководитель'");

        $this->validator->validate(RegistrationStep::LEADER, $data);
    }

    public static function maxLengthFieldProvider(): array
    {
        return [
            'bik exceeds 9 chars' => [RegistrationStep::BANK_DETAIL, 'bik', '0123456789', 9],
            'swift exceeds 11 chars' => [RegistrationStep::BANK_DETAIL, 'swift', 'ABCDEFGHIJKLM', 11],
            'iban exceeds 34 chars' => [RegistrationStep::BANK_DETAIL, 'iban', str_repeat('X', 35), 34],
            'city exceeds 100 chars' => [RegistrationStep::ADDRESS, 'city', str_repeat('X', 101), 100],
            'house exceeds 20 chars' => [RegistrationStep::ADDRESS, 'house', str_repeat('X', 21), 20],
            'zipCode exceeds 20 chars' => [RegistrationStep::ADDRESS, 'zipCode', str_repeat('X', 21), 20],
            'firstName exceeds 100 chars' => [RegistrationStep::LEADER, 'firstName', str_repeat('X', 101), 100],
            'documentTypeCode exceeds 50 chars' => [RegistrationStep::LEADER, 'documentTypeCode', str_repeat('X', 51), 50],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('maxLengthFieldProvider')]
    public function testFieldExceedsMaxLengthThrowsException(RegistrationStep $step, string $field, string $value, int $maxLength): void
    {
        $data = self::validStepData($step);
        $data[$field] = $value;

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Поле '{$field}' превышает максимальную длину в {$maxLength} символов");

        $this->validator->validate($step, $data);
    }

    public function testFieldAtExactMaxLengthPasses(): void
    {
        $data = self::validStepData(RegistrationStep::BANK_DETAIL);
        $data['bik'] = '123456789';

        $this->validator->validate(RegistrationStep::BANK_DETAIL, $data);
        $this->expectNotToPerformAssertions();
    }

    public function testOptionalFieldMissingPasses(): void
    {
        $data = self::validStepData(RegistrationStep::ADDRESS);
        unset($data['apartment']);

        $this->validator->validate(RegistrationStep::ADDRESS, $data);
        $this->expectNotToPerformAssertions();
    }

    public function testOptionalFieldNullPasses(): void
    {
        $data = self::validStepData(RegistrationStep::LEADER);
        $data['patronymic'] = null;

        $this->validator->validate(RegistrationStep::LEADER, $data);
        $this->expectNotToPerformAssertions();
    }

    public function testOptionalFieldExceedsMaxLengthThrowsException(): void
    {
        $data = self::validStepData(RegistrationStep::ADDRESS);
        $data['apartment'] = str_repeat('X', 21);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Поле 'apartment' превышает максимальную длину в 20 символов");

        $this->validator->validate(RegistrationStep::ADDRESS, $data);
    }

    public function testValidDataPassesForAllSteps(): void
    {
        foreach (RegistrationStep::orderedSteps() as $step) {
            if ($step === RegistrationStep::COMPANY_INFO) {
                continue;
            }
            $data = self::validStepData($step);
            $this->validator->validate($step, $data);
        }

        $this->expectNotToPerformAssertions();
    }
}
