<?php

namespace App\Tests\Validator\Constraint;

use App\Entity\Enum\BenefitCostRatioType;
use App\Entity\SchemeFund\BenefitCostRatio as BenefitCostRatioEntity;
use App\Validator\Constraint\BenefitCostRatio;
use App\Validator\Constraint\BenefitCostRatioValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Validator\ContextualValidatorInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class BenefitCostRatioValidatorTest extends TestCase
{
    /** @var ExecutionContext&MockObject */
    private ExecutionContext $context;
    /** @var ValidatorInterface&MockObject */
    private ValidatorInterface $validator;
    /** @var ContextualValidatorInterface&MockObject */
    private ContextualValidatorInterface $contextualValidator;
    /** @var ConstraintViolationBuilderInterface&MockObject */
    private ConstraintViolationBuilderInterface $violationBuilder;
    private BenefitCostRatioValidator $bcrValidator;

    protected function setUp(): void
    {
        $this->context = $this->createMock(ExecutionContext::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->contextualValidator = $this->createMock(ContextualValidatorInterface::class);
        $this->violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);

        $this->bcrValidator = new BenefitCostRatioValidator();
        $this->bcrValidator->initialize($this->context);
    }

    /**
     * @dataProvider exceptionProvider
     */
    public function testValidateThrowsExpectedExceptions(
        mixed $value,
        mixed $constraint,
        string $expectedException,
        string $expectedMessage
    ): void {
        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedMessage);

        $this->bcrValidator->validate($value, $constraint);
    }

    public function exceptionProvider(): array
    {
        return [
            'wrong constraint type' => [
                'value',
                new Length(['max' => 10]),
                UnexpectedTypeException::class,
                'Expected argument of type "App\Validator\Constraint\BenefitCostRatio"'
            ],
            'wrong value type' => [
                'invalid',
                new BenefitCostRatio(),
                UnexpectedValueException::class,
                'Expected argument of type "App\Entity\SchemeFund\BenefitCostRatio"'
            ],
        ];
    }

    public function testValidateWithNullValue(): void
    {
        $constraint = new BenefitCostRatio();

        $this->context->expects($this->never())
            ->method('addViolation');

        $this->bcrValidator->validate(null, $constraint);
    }



    /**
     * @dataProvider valueValidationProvider
     */
    public function testValidateWithTypeValue(
        ?string $value,
        int $precision,
        int $scale,
        bool $expectValidation
    ): void {
        $constraint = new BenefitCostRatio(precision: $precision, scale: $scale);
        $bcr = $this->createMock(BenefitCostRatioEntity::class);
        $bcr->method('getValue')->willReturn($value);
        $bcr->method('getType')->willReturn(BenefitCostRatioType::VALUE);

        if ($expectValidation) {
            $this->setupValidatorExpectations($value);
        }

        $this->bcrValidator->validate($bcr, $constraint);
    }

    public function valueValidationProvider(): array
    {
        return [
            'null value with validation' => [null, 10, 2, true],
            'valid value with validation' => ['123.45', 10, 2, true],
            'empty string with validation' => ['', 5, 1, true],
            'large precision with validation' => ['123456789.99', 12, 2, true],
        ];
    }

    private function setupValidatorExpectations(?string $expectedValue): void
    {
        $this->context->expects($this->once())
            ->method('getValidator')
            ->willReturn($this->validator);

        $this->validator->expects($this->once())
            ->method('inContext')
            ->with($this->context)
            ->willReturn($this->contextualValidator);

        $this->contextualValidator->expects($this->once())
            ->method('atPath')
            ->with('value')
            ->willReturn($this->contextualValidator);

        $this->contextualValidator->expects($this->once())
            ->method('validate')
            ->with(
                $expectedValue,
                $this->callback(function ($validators) {
                    return is_array($validators) && count($validators) === 2;
                }),
                ['Default']
            );
    }

    /**
     * @dataProvider benefitCostRatioTypeProvider
     */
    public function testValidateWithDifferentTypes(
        ?BenefitCostRatioType $type,
        ?string $value,
        bool $expectValidation,
        bool $expectViolation = false
    ): void {
        $constraint = new BenefitCostRatio();

        $bcr = $this->createMock(BenefitCostRatioEntity::class);
        $bcr->method('getValue')->willReturn($value);
        $bcr->method('getType')->willReturn($type);

        if ($expectViolation) {
            $this->context->expects($this->once())
                ->method('addViolation')
                ->with('benefit_cost_ratio.not_null');

            // When violation is expected, validator is never called (early return)
            $this->context->expects($this->never())
                ->method('getValidator');
        } else {
            $this->context->expects($this->never())
                ->method('addViolation');

            // The validator is always called when no violation, but only used for VALUE type
            $this->context->expects($this->once())
                ->method('getValidator')
                ->willReturn($this->validator);

            $this->validator->expects($this->once())
                ->method('inContext')
                ->with($this->context)
                ->willReturn($this->contextualValidator);

            if ($expectValidation) {
                $this->contextualValidator->expects($this->once())
                    ->method('atPath')
                    ->with('value')
                    ->willReturn($this->contextualValidator);

                $this->contextualValidator->expects($this->once())
                    ->method('validate')
                    ->with(
                        $value,
                        $this->callback(function ($validators) {
                            return is_array($validators) && count($validators) === 2;
                        }),
                        ['Default']
                    );
            } else {
                $this->contextualValidator->expects($this->never())
                    ->method('atPath');
            }
        }

        $this->bcrValidator->validate($bcr, $constraint);
    }

    public function benefitCostRatioTypeProvider(): array
    {
        return [
            'value type with value' => [BenefitCostRatioType::VALUE, '10.5', true, false],
            'value type without value' => [BenefitCostRatioType::VALUE, null, true, false],
            'na type with value' => [BenefitCostRatioType::NA, '10.5', false, false],
            'na type without value' => [BenefitCostRatioType::NA, null, false, false],
            'tbc type with value' => [BenefitCostRatioType::TBC, '15.75', false, false],
            'tbc type without value' => [BenefitCostRatioType::TBC, null, false, false],
            'both null values' => [null, null, false, true],
        ];
    }
}