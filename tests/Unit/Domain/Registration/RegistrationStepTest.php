<?php

namespace App\Tests\Unit\Domain\Registration;

use App\Domain\Registration\Step\RegistrationStep;
use PHPUnit\Framework\TestCase;

class RegistrationStepTest extends TestCase
{
    public function testOrderedStepsReturnsAll(): void
    {
        $steps = RegistrationStep::orderedSteps();

        self::assertCount(5, $steps);
        self::assertSame('company_info', $steps[0]->value);
        self::assertSame('address', $steps[1]->value);
        self::assertSame('leader', $steps[2]->value);
        self::assertSame('contact', $steps[3]->value);
        self::assertSame('bank_detail', $steps[4]->value);
    }

    public function testNextReturnsCorrectStep(): void
    {
        self::assertSame(RegistrationStep::ADDRESS, RegistrationStep::COMPANY_INFO->next());
        self::assertSame(RegistrationStep::LEADER, RegistrationStep::ADDRESS->next());
        self::assertSame(RegistrationStep::CONTACT, RegistrationStep::LEADER->next());
        self::assertSame(RegistrationStep::BANK_DETAIL, RegistrationStep::CONTACT->next());
        self::assertNull(RegistrationStep::BANK_DETAIL->next());
    }

    public function testLabelReturnsRussianText(): void
    {
        self::assertSame('Основная информация', RegistrationStep::COMPANY_INFO->label());
        self::assertSame('Банковские реквизиты', RegistrationStep::BANK_DETAIL->label());
    }

    public function testFromValue(): void
    {
        self::assertSame(RegistrationStep::CONTACT, RegistrationStep::from('contact'));
    }
}
