<?php

namespace App\Application\Registration\Step\Validation;

use App\Domain\Registration\Step\RegistrationStep;
use App\Shared\Exception\ValidationException;

class StepDataValidator
{
    private const RULES = [
        'address' => [
            'addressType'   => ['required' => true, 'maxLength' => 10],
            'countryCode'   => ['required' => true, 'maxLength' => 10],
            'region'        => ['required' => true, 'maxLength' => 100],
            'city'          => ['required' => true, 'maxLength' => 100],
            'street'        => ['required' => true, 'maxLength' => 255],
            'house'         => ['required' => true, 'maxLength' => 20],
            'zipCode'       => ['required' => true, 'maxLength' => 20],
            'apartment'     => ['required' => false, 'maxLength' => 20],
            'isSameAsLegal' => ['required' => false],
        ],
        'leader' => [
            'firstName'        => ['required' => true, 'maxLength' => 100],
            'lastName'         => ['required' => true, 'maxLength' => 100],
            'documentTypeCode' => ['required' => true, 'maxLength' => 50],
            'patronymic'       => ['required' => false, 'maxLength' => 100],
        ],
        'contact' => [
            'email' => ['required' => true, 'maxLength' => 255],
            'phone' => ['required' => true, 'maxLength' => 255],
        ],
        'bank_detail' => [
            'accountNumber'        => ['required' => true, 'maxLength' => 50],
            'bankName'             => ['required' => true, 'maxLength' => 255],
            'bik'                  => ['required' => true, 'maxLength' => 9],
            'countryCode'          => ['required' => true, 'maxLength' => 10],
            'swift'                => ['required' => false, 'maxLength' => 11],
            'correspondentAccount' => ['required' => false, 'maxLength' => 50],
            'iban'                 => ['required' => false, 'maxLength' => 34],
        ],
    ];

    public function validate(RegistrationStep $step, array $data): void
    {
        $rules = self::RULES[$step->value] ?? [];

        foreach ($rules as $field => $constraints) {
            $value = $data[$field] ?? null;

            if ($constraints['required'] ?? false) {
                if ($value === null || (is_string($value) && trim($value) === '')) {
                    throw new ValidationException(
                        "Поле '{$field}' обязательно для шага '{$step->label()}'"
                    );
                }
            }

            if ($value !== null && is_string($value) && isset($constraints['maxLength'])) {
                $length = mb_strlen(trim($value));
                if ($length > $constraints['maxLength']) {
                    throw new ValidationException(
                        "Поле '{$field}' превышает максимальную длину в {$constraints['maxLength']} символов"
                    );
                }
            }
        }
    }
}
