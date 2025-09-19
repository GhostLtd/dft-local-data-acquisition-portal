<?php

namespace App\Tests\Twig;

use App\Features;
use App\Twig\FeaturesExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\TwigFunction;

class FeaturesExtensionTest extends TestCase
{
    /** @var RequestStack&MockObject */
    private RequestStack $requestStack;
    private FeaturesExtension $extension;

    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->extension = new FeaturesExtension($this->requestStack);
    }

    public function testGetFunctions(): void
    {
        $functions = $this->extension->getFunctions();

        $this->assertCount(1, $functions);
        $this->assertInstanceOf(TwigFunction::class, $functions[0]);
        $this->assertSame('is_feature_enabled', $functions[0]->getName());
    }

    /**
     * @dataProvider validFeatureProvider
     */
    public function testIsFeatureEnabledWithValidFeature(string $feature): void
    {
        // Since we can't easily mock static methods, we'll test with actual feature constants
        // and just verify the method executes without exception and returns a boolean
        $result = $this->extension->isFeatureEnabled($feature);

        $this->assertIsBool($result);
    }

    public function validFeatureProvider(): array
    {
        return [
            'dev auto login feature' => [Features::FEATURE_DEV_AUTO_LOGIN],
            'dev mca fixtures feature' => [Features::FEATURE_DEV_MCA_FIXTURES],
        ];
    }

    /**
     * @dataProvider invalidFeatureProvider
     */
    public function testIsFeatureEnabledWithInvalidFeature(string $invalidFeature): void
    {
        // Invalid features return false, they don't throw exceptions
        // because Features::isEnabled() is called without $checkFeatureIsValid = true
        $result = $this->extension->isFeatureEnabled($invalidFeature);
        $this->assertFalse($result);
    }

    public function invalidFeatureProvider(): array
    {
        return [
            'non-existent feature' => ['unknown_feature'],
            'numeric start' => ['123_feature'],
            'with dashes' => ['feature-with-dashes'],
            'uppercase' => ['FEATURE_UPPERCASE'],
            'with dots' => ['feature.with.dots'],
            'with special chars' => ['feature@with@special'],
            'empty string' => [''],
            'just spaces' => ['   '],
            'very long name' => [str_repeat('feature', 20)],
            'mixed case' => ['Feature_Name'],
            'with numbers' => ['feature123'],
            'sql injection attempt' => ["'; DROP TABLE features; --"],
        ];
    }

    public function testFunctionCallableIsValid(): void
    {
        $functions = $this->extension->getFunctions();
        $function = $functions[0];

        $callable = $function->getCallable();
        $this->assertIsCallable($callable);

        // The callable is provided by Twig's first-class callable syntax
        // We just need to verify it's callable, not its internal structure
    }


    public function testFunctionName(): void
    {
        $functions = $this->extension->getFunctions();
        $function = $functions[0];

        $this->assertSame('is_feature_enabled', $function->getName());

        $callable = $function->getCallable();
        $this->assertIsCallable($callable);
    }

    /**
     * @dataProvider edgeCaseProvider
     */
    public function testEdgeCases(string $feature): void
    {
        // Edge case features also return false instead of throwing exceptions
        $result = $this->extension->isFeatureEnabled($feature);
        $this->assertFalse($result);
    }

    public function edgeCaseProvider(): array
    {
        return [
            'null byte injection' => ["feature\0"],
            'newline injection' => ["feature\nmalicious"],
            'tab injection' => ["feature\ttest"],
        ];
    }
}