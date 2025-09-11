<?php

namespace App\Tests\Security;

use App\Entity\Authority;
use App\Entity\Enum\Fund;
use App\Entity\Enum\InternalRole;
use App\Entity\FundAward;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\FundReturn\FundReturn;
use App\Entity\Scheme;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Entity\SchemeReturn\SchemeReturn;
use App\Entity\User;
use App\Security\ResolvedSubject;
use App\Security\SubjectResolver;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Ulid;

class SubjectResolverTest extends TestCase
{
    private SubjectResolver $resolver;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->resolver = new SubjectResolver($this->logger);
    }

    /**
     * @dataProvider validSubjectRoleProvider
     */
    public function testIsValidSubjectForInternalRole(object $subject, string $role, bool $expectedValid): void
    {
        $result = $this->resolver->isValidSubjectForInternalRole($subject, $role);

        $this->assertEquals($expectedValid, $result);
    }

    public function validSubjectRoleProvider(): array
    {
        return [
            // HAS_VALID_SIGN_OFF_PERMISSION - valid for FundReturn only
            'SIGN_OFF valid for FundReturn' => [new CrstsFundReturn(), InternalRole::HAS_VALID_SIGN_OFF_PERMISSION, true],
            'SIGN_OFF invalid for Authority' => [new Authority(), InternalRole::HAS_VALID_SIGN_OFF_PERMISSION, false],
            'SIGN_OFF invalid for SchemeReturn' => [new CrstsSchemeReturn(), InternalRole::HAS_VALID_SIGN_OFF_PERMISSION, false],

            // HAS_VALID_MARK_AS_READY_PERMISSION - valid for SchemeReturn only
            'MARK_AS_READY valid for SchemeReturn' => [new CrstsSchemeReturn(), InternalRole::HAS_VALID_MARK_AS_READY_PERMISSION, true],
            'MARK_AS_READY invalid for FundReturn' => [new CrstsFundReturn(), InternalRole::HAS_VALID_MARK_AS_READY_PERMISSION, false],
            'MARK_AS_READY invalid for Authority' => [new Authority(), InternalRole::HAS_VALID_MARK_AS_READY_PERMISSION, false],

            // HAS_VALID_MANAGE_SCHEME_PERMISSION - valid for Authority only
            'MANAGE_SCHEME valid for Authority' => [new Authority(), InternalRole::HAS_VALID_MANAGE_SCHEME_PERMISSION, true],
            'MANAGE_SCHEME invalid for FundReturn' => [new CrstsFundReturn(), InternalRole::HAS_VALID_MANAGE_SCHEME_PERMISSION, false],
            'MANAGE_SCHEME invalid for SchemeReturn' => [new CrstsSchemeReturn(), InternalRole::HAS_VALID_MANAGE_SCHEME_PERMISSION, false],

            // HAS_VALID_EDIT_PERMISSION - valid for FundReturn and SchemeReturn
            'EDIT valid for FundReturn' => [new CrstsFundReturn(), InternalRole::HAS_VALID_EDIT_PERMISSION, true],
            'EDIT valid for SchemeReturn' => [new CrstsSchemeReturn(), InternalRole::HAS_VALID_EDIT_PERMISSION, true],
            'EDIT invalid for Authority' => [new Authority(), InternalRole::HAS_VALID_EDIT_PERMISSION, false],

            // HAS_VALID_VIEW_PERMISSION - valid for all three entity types
            'VIEW valid for FundReturn' => [new CrstsFundReturn(), InternalRole::HAS_VALID_VIEW_PERMISSION, true],
            'VIEW valid for SchemeReturn' => [new CrstsSchemeReturn(), InternalRole::HAS_VALID_VIEW_PERMISSION, true],
            'VIEW valid for Authority' => [new Authority(), InternalRole::HAS_VALID_VIEW_PERMISSION, true],
        ];
    }

    public function testIsValidSubjectForInternalRoleReturnsFalseForNullSubject(): void
    {
        $result = $this->resolver->isValidSubjectForInternalRole(null, InternalRole::HAS_VALID_VIEW_PERMISSION);

        $this->assertFalse($result);
    }

    public function testResolveSubjectForRoleReturnsnullForNullSubject(): void
    {
        $result = $this->resolver->resolveSubjectForRole(null, InternalRole::HAS_VALID_VIEW_PERMISSION);

        $this->assertNull($result);
    }

    public function testResolveSubjectForRoleWithFundReturn(): void
    {
        $admin = new User();
        $authority = new Authority();
        $authority->setId(new Ulid());
        $authority->setAdmin($admin);

        $fundAward = new FundAward();
        $fundAward->setType(Fund::CRSTS1);
        $fundAward->setAuthority($authority);

        $fundReturn = new CrstsFundReturn();
        $fundReturn->setId(new Ulid());
        $fundReturn->setFundAward($fundAward);

        $result = $this->resolver->resolveSubjectForRole($fundReturn, InternalRole::HAS_VALID_SIGN_OFF_PERMISSION);

        $this->assertInstanceOf(ResolvedSubject::class, $result);
        $this->assertEquals(FundReturn::class, $result->getBaseClass());
        $this->assertSame($fundReturn, $result->getEntity());
        $this->assertSame($admin, $result->getAdmin());
        $this->assertEquals(Fund::CRSTS1, $result->getFund());

        $idMap = $result->getIdMap();
        $this->assertArrayHasKey(FundReturn::class, $idMap);
        $this->assertArrayHasKey(Authority::class, $idMap);
        $this->assertArrayHasKey(Fund::class, $idMap);
        $this->assertEquals('CRSTS1', $idMap[Fund::class]);
    }

    public function testResolveSubjectForRoleWithSchemeReturn(): void
    {
        $admin = new User();
        $authority = new Authority();
        $authority->setId(new Ulid());
        $authority->setAdmin($admin);

        $fundAward = new FundAward();
        $fundAward->setType(Fund::CRSTS2);
        $fundAward->setAuthority($authority);

        $fundReturn = new CrstsFundReturn();
        $fundReturn->setId(new Ulid());
        $fundReturn->setFundAward($fundAward);

        $scheme = new Scheme();
        $scheme->setId(new Ulid());

        $schemeReturn = new CrstsSchemeReturn();
        $schemeReturn->setId(new Ulid());
        $schemeReturn->setScheme($scheme);
        $schemeReturn->setFundReturn($fundReturn);

        $result = $this->resolver->resolveSubjectForRole($schemeReturn, InternalRole::HAS_VALID_MARK_AS_READY_PERMISSION);

        $this->assertInstanceOf(ResolvedSubject::class, $result);
        $this->assertEquals(SchemeReturn::class, $result->getBaseClass());
        $this->assertSame($schemeReturn, $result->getEntity());
        $this->assertSame($admin, $result->getAdmin());
        $this->assertEquals(Fund::CRSTS2, $result->getFund());

        $idMap = $result->getIdMap();
        $this->assertArrayHasKey(SchemeReturn::class, $idMap);
        $this->assertArrayHasKey(Scheme::class, $idMap);
        $this->assertArrayHasKey(FundReturn::class, $idMap);
        $this->assertArrayHasKey(Authority::class, $idMap);
        $this->assertArrayHasKey(Fund::class, $idMap);
    }

    public function testResolveSubjectForRoleWithAuthority(): void
    {
        $admin = new User();
        $authority = new Authority();
        $authority->setId(new Ulid());
        $authority->setAdmin($admin);

        $result = $this->resolver->resolveSubjectForRole($authority, InternalRole::HAS_VALID_MANAGE_SCHEME_PERMISSION);

        $this->assertInstanceOf(ResolvedSubject::class, $result);
        $this->assertEquals(Authority::class, $result->getBaseClass());
        $this->assertSame($authority, $result->getEntity());
        $this->assertSame($admin, $result->getAdmin());
        $this->assertNull($result->getFund());

        $idMap = $result->getIdMap();
        $this->assertArrayHasKey(Authority::class, $idMap);
    }

    public function testResolveSubjectForRoleReturnsNullForInvalidRole(): void
    {
        $authority = new Authority();

        $result = $this->resolver->resolveSubjectForRole($authority, 'INVALID_ROLE');

        $this->assertNull($result);
    }

    public function testResolveSubjectForRoleReturnsNullForInvalidSubjectType(): void
    {
        $scheme = new Scheme(); // Scheme is not valid for SIGN_OFF permission

        $result = $this->resolver->resolveSubjectForRole($scheme, InternalRole::HAS_VALID_SIGN_OFF_PERMISSION);

        $this->assertNull($result);
    }

    public function testMemoisationCacheWorks(): void
    {
        $authority = new Authority();
        $authority->setId(new Ulid());
        $authority->setAdmin(new User());

        // First call - should create the result
        $result1 = $this->resolver->resolveSubjectForRole($authority, InternalRole::HAS_VALID_MANAGE_SCHEME_PERMISSION);

        // Second call - should return cached result
        $result2 = $this->resolver->resolveSubjectForRole($authority, InternalRole::HAS_VALID_MANAGE_SCHEME_PERMISSION);

        $this->assertSame($result1, $result2); // Same instance due to caching
    }
}
