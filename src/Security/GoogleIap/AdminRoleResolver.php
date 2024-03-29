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
            new Group('DfT', 'dft.gov.uk', ['national.travelsurvey@dft.gov.uk']),
            new Group('Ghost', 'ghostlimited.com', []),
        ];
    }

    /** @return array<Group> */
    public function getAssignees(): array
    {
        return [
            new Group('DfT', 'dft.gov.uk', []),
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

        $roles = [];

        $domain = strtolower($emailParts[1]);

        foreach($this->getAssigners() as $assignerGroup) {
            if ($assignerGroup->getDomain() === $domain) {
                $roles[] = 'ROLE_FEEDBACK_ASSIGNER';
            }
        }

        foreach($this->getAssignees() as $assigneeGroup) {
            if ($assigneeGroup->getDomain() === $domain) {
                $roles[] = 'ROLE_FEEDBACK_VIEWER';
            }
        }

        foreach($this->getInterviewerAdminDomains() as $adminDomain) {
            if ($adminDomain === $domain) {
                $roles[] = 'ROLE_INTERVIEWER_ADMIN';
            }
        }

        foreach($this->getSampleImporterDomains() as $sampleImporterDomain) {
            if ($sampleImporterDomain === $domain) {
                $roles[] = 'ROLE_SAMPLE_IMPORTER';
            }
        }

        if (in_array($domain, $this->getSuperAdminDomains())) {
            $roles[] = 'ROLE_SUPER_ADMIN';
        }

        return $roles;
    }
}