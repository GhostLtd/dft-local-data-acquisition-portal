<?php

namespace App\Security;

use App\Entity\Enum\Role;
use App\Entity\FundReturn\FundReturn;
use App\Entity\Scheme;
use App\Entity\SchemeReturn\SchemeReturn;
use App\Entity\Authority;
use Psr\Log\LoggerInterface;

class SubjectResolver
{
    public function __construct(protected LoggerInterface $logger)
    {}

    public function isValidSubjectForRole(mixed $subject, string $role): bool
    {
        return $this->parseSubjectForRole($subject, $role) !== null;
    }

    public function resolveSubjectForRole(mixed $subject, string $role): ?ResolvedSubject
    {
        [$baseEntityClass, $section, $subject] = $this->parseSubjectForRole($subject, $role, true);

        if (!$baseEntityClass) {
            return null;
        }

        $admin = null;
        $fundType = null;
        $idMap = [];

        if ($subject instanceof SchemeReturn) {
            $idMap[SchemeReturn::class] = $subject->getId();
            $idMap[Scheme::class] = $subject?->getSchemeFund()?->getScheme()?->getId();
            $subject = $subject?->getFundReturn();
        }

        if ($subject instanceof FundReturn) {
            $idMap[FundReturn::class] = $subject->getId();
            $fundAward = $subject->getFundAward();
            $fundType = $fundAward?->getType();
            $subject = $fundAward?->getAuthority();
        }

        if ($subject instanceof Authority) {
            $idMap[Authority::class] = $subject->getId();
            $admin = $subject->getAdmin();
        }

        return new ResolvedSubject($baseEntityClass, $subject, $section, $idMap, $admin, $fundType);
    }

    protected function parseSubjectForRole(mixed $subject, string $role, bool $logErrors = false): ?array
    {
        if (!in_array($role, [Role::CAN_SUBMIT, Role::CAN_COMPLETE, Role::CAN_EDIT, Role::CAN_VIEW])) {
            $logErrors && $this->logger->error("Failed to parse subject - unsupported role {$role}");
            return null;
        }

        $section = null;
        if (is_array($subject)) {
            $section = $subject['section'] ?? null;
            $subject = $subject['subject'] ?? null;
        }

        if (!is_object($subject)) {
            $logErrors && $this->logger->error("Failed to parse subject - passed a non-object subject");
            return null;
        }

        if (!is_string($section) && !is_null($section)) {
            $logErrors && $this->logger->error("Failed to parse subject - passed an invalid section");
            return null;
        }

        if ($role === Role::CAN_SUBMIT && $section !== null) {
            $logErrors && $this->logger->error("Failed to parse subject - section is not valid for role {$role}");
            return null;
        }

        if (in_array($role, [Role::CAN_COMPLETE, Role::CAN_EDIT]) && $section === null) {
            $logErrors && $this->logger->error("Failed to parse subject - section must be specified for role {$role}");
            return null;
        }

        $baseEntityClass = $this->getBaseEntityClassForRole($subject, $role);

        if (!$baseEntityClass) {
            $logErrors && $this->logger->error("Failed to parse subject - invalid subject type (".$subject::class.") for role {$role}");
            return null;
        }

        if ($baseEntityClass === Authority::class && $section !== null) {
            $logErrors && $this->logger->error("Failed to parse subject - invalid subject - cannot specify a sectionType for an Authority");
            return null;
        }

        return [$baseEntityClass, $section, $subject];
    }

    protected function getBaseEntityClassForRole(object $subject, string $role): ?string
    {
        $validBaseClasses = [FundReturn::class];

        if ($role !== Role::CAN_SUBMIT) {
            $validBaseClasses[] = SchemeReturn::class;
        }

        if ($role === Role::CAN_VIEW) {
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