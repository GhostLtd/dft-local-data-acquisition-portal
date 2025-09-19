<?php

namespace App\Tests\Utility;

use App\Utility\TypeHelper;
use PHPUnit\Framework\TestCase;

class TypeHelperTest extends TestCase
{
    /**
     * @dataProvider validTypeCheckProvider
     */
    public function testCheckMatchesClassReturnsValueForValidTypes(string $className, $value, $expectedResult): void
    {
        $result = TypeHelper::checkMatchesClass($className, $value);

        $this->assertSame($expectedResult, $result);
    }

    public function validTypeCheckProvider(): array
    {
        $stdClass = new \stdClass();
        $exception = new \Exception('test');
        $dateTime = new \DateTime('2023-01-01');

        return [
            'stdClass instance' => [
                'className' => \stdClass::class,
                'value' => $stdClass,
                'expectedResult' => $stdClass,
            ],
            'Exception instance' => [
                'className' => \Exception::class,
                'value' => $exception,
                'expectedResult' => $exception,
            ],
            'DateTime instance' => [
                'className' => \DateTime::class,
                'value' => $dateTime,
                'expectedResult' => $dateTime,
            ],
        ];
    }

    /**
     * @dataProvider invalidTypeCheckProvider
     */
    public function testCheckMatchesClassThrowsExceptionForInvalidTypes(string $className, $value, string $expectedMessage): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        TypeHelper::checkMatchesClass($className, $value);
    }

    public function invalidTypeCheckProvider(): array
    {
        return [
            'string passed for stdClass' => [
                'className' => \stdClass::class,
                'value' => 'not an object',
                'expectedMessage' => 'Value passed is not an instance of stdClass',
            ],
            'integer passed for DateTime' => [
                'className' => \DateTime::class,
                'value' => 42,
                'expectedMessage' => 'Value passed is not an instance of DateTime',
            ],
            'array passed for Exception' => [
                'className' => \Exception::class,
                'value' => ['not', 'an', 'exception'],
                'expectedMessage' => 'Value passed is not an instance of Exception',
            ],
            'null passed for stdClass' => [
                'className' => \stdClass::class,
                'value' => null,
                'expectedMessage' => 'Value passed is not an instance of stdClass',
            ],
        ];
    }

    /**
     * @dataProvider validTypeCheckOrNullProvider
     */
    public function testCheckMatchesClassOrNullReturnsValueForValidTypes(string $className, $value, $expectedResult): void
    {
        $result = TypeHelper::checkMatchesClassOrNull($className, $value);

        $this->assertSame($expectedResult, $result);
    }

    public function validTypeCheckOrNullProvider(): array
    {
        $stdClass = new \stdClass();
        $exception = new \Exception('test');

        return [
            'stdClass instance' => [
                'className' => \stdClass::class,
                'value' => $stdClass,
                'expectedResult' => $stdClass,
            ],
            'Exception instance' => [
                'className' => \Exception::class,
                'value' => $exception,
                'expectedResult' => $exception,
            ],
            'null value' => [
                'className' => \stdClass::class,
                'value' => null,
                'expectedResult' => null,
            ],
            'null for DateTime' => [
                'className' => \DateTime::class,
                'value' => null,
                'expectedResult' => null,
            ],
        ];
    }

    /**
     * @dataProvider invalidTypeCheckOrNullProvider
     */
    public function testCheckMatchesClassOrNullThrowsExceptionForInvalidTypes(string $className, $value, string $expectedMessage): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        TypeHelper::checkMatchesClassOrNull($className, $value);
    }

    public function invalidTypeCheckOrNullProvider(): array
    {
        return [
            'string passed for stdClass' => [
                'className' => \stdClass::class,
                'value' => 'not an object',
                'expectedMessage' => 'Value passed is neither null, nor an instance of stdClass',
            ],
            'integer passed for DateTime' => [
                'className' => \DateTime::class,
                'value' => 42,
                'expectedMessage' => 'Value passed is neither null, nor an instance of DateTime',
            ],
            'array passed for Exception' => [
                'className' => \Exception::class,
                'value' => ['not', 'an', 'exception'],
                'expectedMessage' => 'Value passed is neither null, nor an instance of Exception',
            ],
        ];
    }

    public function testCheckMatchesClassWorksWithInheritance(): void
    {
        $runtimeException = new \RuntimeException('test');

        $result = TypeHelper::checkMatchesClass(\Exception::class, $runtimeException);

        $this->assertSame($runtimeException, $result);
    }

    public function testCheckMatchesClassOrNullWorksWithInheritance(): void
    {
        $runtimeException = new \RuntimeException('test');

        $result = TypeHelper::checkMatchesClassOrNull(\Exception::class, $runtimeException);

        $this->assertSame($runtimeException, $result);
    }
}
