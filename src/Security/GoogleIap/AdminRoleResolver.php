<?php

namespace App\Security\GoogleIap;

use App\Entity\Feedback\Group;

class AdminRoleResolver
{
    // Specifically this allows users to perform add/allocate/delete actions on interviewers
    // N.B. All users that can log in will be able to view interviewers
    public function getInterviewerAdminDomains(): array
    {
        return [
            'ghostlimited.com',
            // 'dft.gov.uk',
        ];
    }

    public function getSampleImporterDomains(): array
    {
        return [
            'ghostlimited.com',
        ];
    }

    /** @return array<Group> */
    public function getAssigners(): array
    {
        return [
            new Group('NatCen', 'natcen.ac.uk', ['nts@natcen.ac.uk']),
            new Group('DfT', 'dft.gov.uk', []),
            new Group('Ghost', 'ghostlimited.com', []),
        ];
    }

    /** @return array<Group> */
    public function getAssignees(): array
    {
        return [
            new Group('DfT', 'dft.gov.uk', ['national.travelsurvey@dft.gov.uk']),
            new Group('Ghost', 'ghostlimited.com', ['feedback@ghostlimited.com ']),
            new Group('NatCen', 'natcen.ac.uk', ['nts@natcen.ac.uk']),
        ];
    }

    public function getAssigneeNameForDomain(string $domain): ?string
    {
        foreach($this->getAssignees() as $assignee) {
            if ($assignee->getDomain() === $domain) {
                return $assignee->getName();
            }
        }

        return null;
    }

    /** @return array<string> */
    public function getSuperAdminDomains(): array
    {
        return [
            'ghostlimited.com',
        ];
    }

    /** @return array<string> */
    public function getCanLogoutDomains(): array
    {
        return [
            'natcen.ac.uk',
        ];
    }

    /** @return array<string> */
    public function getDataExporterDomains(): array
    {
        return [
            'natcen.ac.uk',
            'ghostlimited.com',
            'dft.gov.uk',
        ];
    }

    public function getAssignee(string $name): ?Group
    {
        foreach ($this->getAssignees() as $assignee) {
            if ($assignee->getName() === $name) {
                return $assignee;
            }
        }

        return null;
    }

    public function getRolesForEmailAddress(string $emailAddress): array
    {
        $emailParts = explode('@', $emailAddress);
        if (count($emailParts) !== 2) {
            return [];
        }
        $domain = strtolower($emailParts[1]);

        $roleDomainsMap = [
            'ROLE_FEEDBACK_ASSIGNEE' => array_map(fn(Group $g) => $g->getDomain(), $this->getAssignees()),
            'ROLE_FEEDBACK_ASSIGNER' => array_map(fn(Group $g) => $g->getDomain(), $this->getAssigners()),
            'ROLE_INTERVIEWER_ADMIN' => $this->getInterviewerAdminDomains(),
            'ROLE_SAMPLE_IMPORTER' => $this->getSampleImporterDomains(),
            'ROLE_SUPER_ADMIN' => $this->getSuperAdminDomains(),
            'ROLE_CAN_LOGOUT' => $this->getCanLogoutDomains(),
            'ROLE_DATA_EXPORTER' => $this->getDataExporterDomains(),
        ];

        return array_keys(array_filter($roleDomainsMap, fn ($domains) => in_array($domain, $domains)));

    }
}