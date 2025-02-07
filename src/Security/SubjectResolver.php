<?php

namespace App\Security;

use App\Entity\Enum\Fund;
use App\Entity\Enum\InternalRole;
use App\Entity\FundReturn\FundReturn;
use App\Entity\Scheme;
use App\Entity\SchemeReturn\SchemeReturn;
use App\Entity\Authority;
use Psr\Log\LoggerInterface;

class SubjectResolver
{
    protected array $memoizationCacheParseSubject = [];
    protected array $memoizationCacheResolveResult = [];

    public function __construct(protected LoggerInterface $logger)
    {}

    public function isValidSubjectForInternalRole(mixed $subject, string $role): bool
    {
        if ($subject === null) {
            return false;
        }

        return $this->parseSubjectForRole($subject, $role) !== null;
    }

    public function resolveSubjectForRole(mixed $subject, string $role): ?ResolvedSubject
    {
        if ($subject === null) {
            return null;
        }

        $cacheKey = spl_object_hash($subject).'-'.$role;

        if (!isset($this->memoizationCacheResolveResult[$cacheKey])) {
            [$baseEntityClass, $subject] = $this->parseSubjectForRole($subject, $role, true);

            if (!$baseEntityClass) {
                return null;
            }

            $admin = null;
            $fundType = null;
            $idMap = [];

            $originalSubject = $subject;

            if ($subject instanceof SchemeReturn) {
                $idMap[SchemeReturn::class] = $subject->getId();
                $idMap[Scheme::class] = $subject?->getSchemeFund()?->getScheme()?->getId();
                $subject = $subject?->getFundReturn();
            }

            if ($subject instanceof FundReturn) {
                $idMap[FundReturn::class] = $subject->getId();
                $fundAward = $subject->getFundAward();
                $fundType = $fundAward?->getType();
                $idMap[Fund::class] = $fundType?->value;
                $subject = $fundAward?->getAuthority();
            }

            if ($subject instanceof Authority) {
                $idMap[Authority::class] = $subject->getId();
                $admin = $subject->getAdmin();
            }

            $this->memoizationCacheResolveResult[$cacheKey] = new ResolvedSubject($baseEntityClass, $originalSubject, $idMap, $admin, $fundType);
        }

        return $this->memoizationCacheResolveResult[$cacheKey];
    }

    /**
     * @return ?array{0: class-string, 1: mixed}
     */
    protected function parseSubjectForRole(mixed $subject, string $role, bool $logErrors = false): ?array
    {
        $cacheKey = spl_object_hash($subject).'-'.$role;

        if (!isset($this->memoizationCacheParseSubject[$cacheKey])) {
            if (!in_array($role, [
                InternalRole::HAS_VALID_SIGN_OFF_PERMISSION,
                InternalRole::HAS_VALID_MARK_AS_READY_PERMISSION,
                InternalRole::HAS_VALID_EDIT_PERMISSION,
                InternalRole::HAS_VALID_VIEW_PERMISSION,
            ])) {
                $logErrors && $this->logger->error("Failed to parse subject - unsupported role {$role}");
                return null;
            }

            if (!is_object($subject)) {
                $logErrors && $this->logger->error("Failed to parse subject - passed a non-object subject");
                return null;
            }

            $baseEntityClass = $this->getBaseEntityClassForInternalRole($subject, $role);

            if (!$baseEntityClass) {
                $logErrors && $this->logger->error("Failed to parse subject - invalid subject type (" . $subject::class . ") for role {$role}");
                return null;
            }

            $this->memoizationCacheParseSubject[$cacheKey] = [$baseEntityClass, $subject];
        }

        return $this->memoizationCacheParseSubject[$cacheKey];
    }

    protected function getBaseEntityClassForInternalRole(object $subject, string $role): ?string
    {
        $validBaseClasses = [];

        if (in_array($role, [
            InternalRole::HAS_VALID_SIGN_OFF_PERMISSION,
            InternalRole::HAS_VALID_EDIT_PERMISSION,
            InternalRole::HAS_VALID_VIEW_PERMISSION,
        ])) {
            $validBaseClasses[] = FundReturn::class;
        }

        if (in_array($role, [
            InternalRole::HAS_VALID_MARK_AS_READY_PERMISSION,
            InternalRole::HAS_VALID_EDIT_PERMISSION,
            InternalRole::HAS_VALID_VIEW_PERMISSION,
        ])) {
            $validBaseClasses[] = SchemeReturn::class;
        }

        if ($role === InternalRole::HAS_VALID_VIEW_PERMISSION) {
            $validBaseClasses[] = Authority::class;
        }

        // No, you can't replace this loop with in_array, because ::class is not the same as instanceof (inheritance!)
        foreach($validBaseClasses as $validBaseClass) {
            if ($subject instanceof $validBaseClass) {
                return $validBaseClass;
            }
        }

        return null;
    }
}
