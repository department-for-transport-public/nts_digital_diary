<?php

namespace App\Entity;

use App\Entity\Utility\NotifyApiResponse;
use App\Entity\Utility\NotifyApiResponseTrait;
use App\Messenger\AlphagovNotify\ApiResponseInterface;
use App\Repository\UserRepository;
use App\Validator\Constraints\EmailOrNoLoginPlaceholder;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\Exception\LogicException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[UniqueEntity(fields: ['username', 'trainingInterviewer'], message: 'common.email.already-used', ignoreNull: false, groups: ['wizard.on-boarding.diary-keeper.user-identifier', 'admin.interviewer', 'api.interviewer'])]
#[UniqueEntity(fields: ['username', 'diaryKeeper'], message: 'common.email.already-used', repositoryMethod: 'findByForDirectOnboarding', ignoreNull: false, groups: ['direct-onboarding.logins'])]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table]
#[ORM\UniqueConstraint(columns: ['username', 'virtual_column_training_interviewer_id'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface, PreferenceUserInterface, ApiResponseInterface
{
    public const string NO_LOGIN_PLACEHOLDER = "no-login";
    public const string INTERVIEWER_TRAINING_PLACEHOLDER = "int-trng";

    public const string ROLE_USER = 'ROLE_USER';
    public const string ROLE_INTERVIEWER = 'ROLE_INTERVIEWER';
    public const string ROLE_DIARY_KEEPER = 'ROLE_DIARY_KEEPER';

    use IdTrait, NotifyApiResponseTrait;

    #[Assert\Email(message: 'wizard.diary-keeper.user-identifier.email', groups: ['admin.interviewer', 'api.interviewer'])]
    #[Assert\NotBlank(message: 'wizard.diary-keeper.user-identifier.not-blank', groups: ['admin.interviewer', 'api.interviewer'])]
    #[Assert\Length(max: 180, maxMessage: 'common.string.max-length', groups: ['wizard.on-boarding.diary-keeper.user-identifier', 'admin.interviewer', 'api.interviewer', 'direct-onboarding.logins'])]
    #[EmailOrNoLoginPlaceholder(message: "wizard.diary-keeper.user-identifier.email", groups: ["wizard.on-boarding.diary-keeper.user-identifier", "direct-onboarding.logins"])]
    #[ORM\Column(type: 'string', length: 180)]
    private string $username = '';

    /**
     * @var Collection<int, NotifyApiResponse>
     */
    #[ORM\ManyToMany(targetEntity: NotifyApiResponse::class, cascade: ['remove'])]
    protected Collection $apiResponses;

    #[Assert\Callback(groups: ['wizard.on-boarding.direct.interviewer-training'])]
    public function validateDirectOnboardingTrainingDuplicateUsernames(ExecutionContextInterface $context): void
    {
        // special case during direct onboarding training - because entities are not persisted
        $otherDiaryKeepers = $this->getDiaryKeeper()->getHousehold()->getDiaryKeepers()
            ->filter(fn($dk) => $dk != $this->getDiaryKeeper())
            ->toArray();
        foreach ($otherDiaryKeepers as $diaryKeeper) {
            if ($diaryKeeper->getUser()->getUserIdentifier() === $this->getUserIdentifier()) {
                $context->buildViolation("common.email.already-used")
                    ->atPath("username")
                    ->addViolation();
            }
        }
    }

    #[Assert\Callback(groups: ['wizard.on-boarding.diary-keeper.user-identifier', 'direct-onboarding.logins'])]
    public function validateIdentity(ExecutionContextInterface $context): void
    {
        $hasIdentifier = $this->hasIdentifierForLogin();
        $hasProxier = $this->getDiaryKeeper()->getProxies()->count() > 0;

        $householdAlreadyHasDiaryKeepers = $this->getDiaryKeeper()->getHousehold()->getDiaryKeepers()->count() > 1;

        if (!$hasIdentifier) {
            if (!$hasProxier) {
                // more validation on DiaryKeeper/proxies
                if (!$householdAlreadyHasDiaryKeepers) {
                    $context->buildViolation("wizard.on-boarding.diary-keeper.identity.enter-email")
                        ->atPath("username")
                        ->addViolation();
                }
            } else if ($this->getDiaryKeeper()->isActingAsAProxyForOthers()) {
                $context->buildViolation("wizard.on-boarding.diary-keeper.identity.not-empty-when-acting-as-a-proxy")
                    ->atPath("username")
                    ->setParameter('names', join(', ', $this->getDiaryKeeper()->getActingAsAProxyForNames()))
                    ->addViolation();
            }
        }
    }

    #[ORM\OneToOne(targetEntity: Interviewer::class, inversedBy: 'user', fetch: 'EAGER')]
    private ?Interviewer $interviewer = null;

    #[Assert\Valid(groups: ['wizard.on-boarding.diary-keeper.user-identifier'])]
    #[ORM\OneToOne(targetEntity: DiaryKeeper::class, inversedBy: 'user', fetch: 'EAGER')]
    private ?DiaryKeeper $diaryKeeper = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $password = null;

    private ?string $plainPassword = null;
    private array $roles;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $passwordResetCode = null;

    #[ORM\Column(type: 'boolean', nullable: true)] // DISABLED: Assert\Expression(
    private ?bool $hasConsented = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $emailPurgeDate = null;

    #[ORM\ManyToOne(targetEntity: Interviewer::class)]
    private ?Interviewer $trainingInterviewer = null;

    #[ORM\Column(type: 'string', length: 26, nullable: true, insertable: false, updatable: false, columnDefinition: "VARCHAR(26) GENERATED ALWAYS AS (ifNull(training_interviewer_id, 'no-interviewer')) VIRTUAL", generated: 'ALWAYS')]
    private ?string $virtualColumnTrainingInterviewerId = null;

    #[ORM\Column(type: 'boolean')]
    private bool $hasPendingUsernameChange = false;

    #[ORM\OneToOne(targetEntity: UserPreferences::class, inversedBy: 'user', cascade: ['persist', 'remove'], fetch: 'EAGER')]
    private ?UserPreferences $preferences = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $hasLoggedIn = false;

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUserIdentifier(?string $username): self
    {
        $this->username = strtolower($username ?? '');
        return $this;
    }

    public function setUsername(?string $username): self
    {
        return $this->setUserIdentifier($username);
    }

    public function getRoles(): array
    {
        if (!isset($this->roles)) {
            $this->roles = [];

            if ($this->interviewer !== null) {
                $this->roles[] = self::ROLE_INTERVIEWER;
            } else if ($this->diaryKeeper !== null) {
                $this->roles[] = self::ROLE_DIARY_KEEPER;
            }

            $this->roles = array_unique($this->roles);
        }

        return $this->roles;
    }

    public function hasRole($role): bool {
        return in_array($role, $this->getRoles());
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function clearPlainPassword(): self
    {
        $this->plainPassword = null;
        return $this;
    }

    public function setPlainPassword(?string $plainPassword): self
    {
        $this->password = mt_rand();
        $this->plainPassword = $plainPassword;
        return $this;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function eraseCredentials(): void
    {
    }

    public function getInterviewer(): ?Interviewer
    {
        return $this->interviewer ?? null;
    }

    public function setInterviewer(?Interviewer $interviewer): self
    {
        if ($this->getDiaryKeeper()) {
            throw new LogicException("Cannot set Interviewer for DiaryKeeper User");
        }

        $this->interviewer = $interviewer;
        return $this;
    }

    public function getDiaryKeeper(): ?DiaryKeeper
    {
        return $this->diaryKeeper ?? null;
    }

    public function setDiaryKeeper(?DiaryKeeper $diaryKeeper): self
    {
        if ($this->getInterviewer()) {
            throw new \LogicException("Cannot set DiaryKeeper for Interviewer User");
        }

        $this->diaryKeeper = $diaryKeeper;
        return $this;
    }

    public function getPasswordResetCode(): ?string
    {
        return $this->passwordResetCode;
    }

    public function setPasswordResetCode(?string $passwordResetCode): self
    {
        $this->passwordResetCode = $passwordResetCode;
        return $this;
    }

    /**
     * we need serialize and unserialize to prevent the whole entity tree being
     * dumped in the session
     */
    public function __serialize(): array
    {
        return [$this->id ?? null, $this->username ?? null, $this->password ?? null, $this->getRoles(), $this->getPreferences()];
    }

    public function __unserialize($data)
    {
        // This special case is needed to cope with users who are already logged in when we deploy the user preferences
        // changes. Shortly after that has been deployed, we can remove everything except the default case below.

        /** @noRector \Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector */
        switch(count($data)) {
            case 4:
                [$this->id, $this->username, $this->password, $this->roles] = $data;
                break;
            default:
                [$this->id, $this->username, $this->password, $this->roles, $this->preferences] = $data;
        }
    }

    public function hasIdentifierForLogin(): bool
    {
        return $this->getUserIdentifier() && !self::isNoLoginPlaceholder($this->username);
    }

    public static function isNoLoginPlaceholder(?string $userIdentifier): bool
    {
        return $userIdentifier !== null
            && str_starts_with($userIdentifier, self::NO_LOGIN_PLACEHOLDER . ':');
    }

    public static function generateNoLoginPlaceholder(): string
    {
        return User::NO_LOGIN_PLACEHOLDER.':'.(new Ulid());
    }

    public function getHasConsented(): ?bool
    {
        return $this->hasConsented ?? null;
    }

    public function setHasConsented(?bool $hasConsented): self
    {
        $this->hasConsented = $hasConsented;
        return $this;
    }

    public function getEmailPurgeDate(): ?\DateTimeInterface
    {
        return $this->emailPurgeDate;
    }

    public function setEmailPurgeDate(?\DateTimeInterface $emailPurgeDate): self
    {
        $this->emailPurgeDate = $emailPurgeDate;
        return $this;
    }

    public function getTrainingInterviewer(): ?Interviewer
    {
        return $this->trainingInterviewer ?? null;
    }

    public function setTrainingInterviewer(?Interviewer $trainingInterviewer): self
    {
        $this->trainingInterviewer = $trainingInterviewer;

        return $this;
    }

    #[ORM\PreFlush]
    public function trainingInterviewerPreFlush(PreFlushEventArgs $eventArgs): void
    {
        if (
            $eventArgs->getObjectManager()->getUnitOfWork()->isScheduledForInsert($this) &&
            // Typically trainingInterviewer will always be null, but this exclusion allows
            // unit test fixtures to be used and not overwritten.
            !isset($this->trainingInterviewer)
        ) {
            $this->trainingInterviewer = $this->diaryKeeper?->getHousehold()?->getAreaPeriod()?->getTrainingInterviewer();
        }
    }

    public function getHasPendingUsernameChange(): bool
    {
        return $this->hasPendingUsernameChange;
    }

    public function setHasPendingUsernameChange(bool $hasPendingUsernameChange): self
    {
        $this->hasPendingUsernameChange = $hasPendingUsernameChange;

        return $this;
    }

    public function getHasLoggedIn(): ?bool
    {
        return $this->hasLoggedIn;
    }

    public function setHasLoggedIn(?bool $hasLoggedIn): static
    {
        $this->hasLoggedIn = $hasLoggedIn;
        return $this;
    }

    public function getPreferences(): UserPreferences
    {
        if (!isset($this->preferences)) {
            $this->preferences = new UserPreferences();
        }
        return $this->preferences;
    }

    public function getNotifyApiResponses(): Collection
    {
        return $this->apiResponses;
    }

    public function addNotifyApiResponse(NotifyApiResponse $notifyApiResponse): static
    {
        if (!$this->apiResponses->contains($notifyApiResponse)) {
            $this->apiResponses[] = $notifyApiResponse;
        }

        return $this;
    }
}
