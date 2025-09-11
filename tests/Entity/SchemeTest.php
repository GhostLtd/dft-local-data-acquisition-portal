<?php

namespace App\Tests\Entity;

use App\Entity\Authority;
use App\Entity\Enum\ActiveTravelElement;
use App\Entity\Enum\TransportMode;
use App\Entity\Scheme;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class SchemeTest extends TestCase
{
    public function testValidateActiveTravelPassesWhenTransportModeIsActiveTravel(): void
    {
        $scheme = new Scheme();
        $scheme->setTransportMode(TransportMode::AT_NEW_JUNCTION_TREATMENT);
        $scheme->setActiveTravelElement(null); // Should be allowed for active travel modes

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())->method('buildViolation');

        $scheme->validateActiveTravel($context);
    }

    public function testValidateActiveTravelFailsWhenNonActiveTravelModeAndElementIsNull(): void
    {
        $scheme = new Scheme();
        $scheme->setTransportMode(TransportMode::BUS_PRIORITY_MEASURES);
        $scheme->setActiveTravelElement(null);

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects($this->once())->method('atPath')->with('hasActiveTravelElements')->willReturnSelf();
        $violationBuilder->expects($this->once())->method('addViolation');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->once())
            ->method('buildViolation')
            ->with('scheme.active_travel_element.not_null')
            ->willReturn($violationBuilder);

        $scheme->validateActiveTravel($context);
    }

    public function testValidateActiveTravelPassesWhenNonActiveTravelModeButElementIsSet(): void
    {
        $scheme = new Scheme();
        $scheme->setTransportMode(TransportMode::BUS_PRIORITY_MEASURES);
        $scheme->setActiveTravelElement(ActiveTravelElement::ROUTE_IMPROVEMENTS);

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())->method('buildViolation');

        $scheme->validateActiveTravel($context);
    }

    public function testGetSchemeIdentifierReturnsRawIdentifierWhenNumberPatOnlyIsTrue(): void
    {
        $scheme = new Scheme();
        $scheme->setSchemeIdentifier('12345');

        $result = $scheme->getSchemeIdentifier(true);

        $this->assertEquals('12345', $result);
    }

    public function testGetSchemeIdentifierReturnsFormattedIdentifierWithAuthorityPrefix(): void
    {
        $authority = new Authority();
        $authority->setId(new Ulid());
        $authority->setName('Greater Manchester Combined Authority');

        $scheme = new Scheme();
        $scheme->setAuthority($authority);
        $scheme->setSchemeIdentifier('12345');

        $result = $scheme->getSchemeIdentifier(false);

        $this->assertEquals('GMCA-12345', $result);
    }
}
