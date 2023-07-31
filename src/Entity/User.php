<?php

namespace App\Entity;

use App\Repository\UserRepository;
use App\Validator\Constraints\EmailOrNoLoginPlaceholder;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\Exception\LogicException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(columns={"username", "virtual_column_training_interviewer_id"})})
 * @UniqueEntity(
 *     groups={"wizard.on-boarding.diary-keeper.user-identifier", "admin.interviewer", "api.interviewer"},
 *     fields={"username", "trainingInterviewer"},
 *     message="common.email.already-used",
 *     ignoreNull=false
 * )
 * @ORM\HasLifecycleCallbacks()
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const NO_LOGIN_PLACEHOLDER = "no-login";
    public const INTERVIEWER_TRAINING_PLACEHOLDER = "int-trng";

    public const ROLE_USER = 'ROLE_USER';
    public const ROLE_INTERVIEWER = 'ROLE_INTERVIEWER';
    public const ROLE_DIARY_KEEPER = 'ROLE_DIARY_KEEPER';

    use IdTrait;

    /**
     * @ORM\Column(type="string", length=180)
     * @Assert\Email(groups={"admin.interviewer", "api.interviewer"}, message="wizard.diary-keeper.user-identifier.email")
     * @Assert\NotBlank(groups={"admin.interviewer", "api.interviewer"}, message="wizard.diary-keeper.user-identifier.not-blank")
     * @Assert\Length(groups={"wizard.on-boarding.diary-keeper.user-identifier", "admin.interviewer", "api.interviewer"}, maxMessage="common.string.max-length", max=180)
     */
    private ?string $username;

    /**
     * @Assert\Callback(groups="wizard.on-boarding.diary-keeper.user-identifier")
     */
    public function validateIdentity(ExecutionContextInterface $context)
    {
        $hasIdentifier = $this->hasIdentifierForLogin();
        $hasProxier = $this->getDiaryKeeper()->getProxies()->count() > 0;

        $householdAlreadyHasDiaryKeepers = $this->getDiaryKeeper()->getHousehold()->getDiaryKeepers()->count() > 1;

        if (!$hasIdentifier) {
            if (!$hasProxier) {
                // Must have at least one login or proxy
                $errorKey = $householdAlreadyHasDiaryKeepers ? 'at-least-one' : 'enter-email';

                $context->buildViolation("wizard.on-boarding.diary-keeper.identity.{$errorKey}")
                    ->atPath($householdAlreadyHasDiaryKeepers ? "" : "username")
                    ->addViolation();
            } else if ($this->getDiaryKeeper()->isActingAsAProxyForOthers()) {
                $context->buildViolation("wizard.on-boarding.diary-keeper.identity.not-empty-when-acting-as-a-proxy")
                    ->atPath("username")
                    ->setParameters([
                        'names' => join(', ', $this->getDiaryKeeper()->getActingAsAProxyForNames()),
                    ])
                    ->addViolation();
            }
        }
    }

    /**
     * @ORM\OneToOne(targetEntity=Interviewer::class, inversedBy="user", fetch="EAGER")
     */
    private ?Interviewer $interviewer = null;

    /**
     * @ORM\OneToOne(targetEntity=DiaryKeeper::class, inversedBy="user", fetch="EAGER")
     */
    private ?DiaryKeeper $diaryKeeper = null;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $password = null;

    private ?string $plainPassword = null;
    private array $roles;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $passwordResetCode;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * DISABLED: Assert\Expression(
     *     "this.getUserIdentifier() matches '/^no-login:/' || (this.getUserIdentifier() and this.getHasConsented())",
     *     groups={"wizard.on-boarding.diary-keeper.user-identifier"},
     *     message="wizard.diary-keeper.consent.not-null"
     * )
     */
    private ?bool $hasConsented;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTime $emailPurgeDate = null;

    /**
     * @ORM\ManyToOne(targetEntity=Interviewer::class)
     */
    private ?Interviewer $trainingInterviewer;

    /**
     * @ORM\Column(type="string", length=26, insertable=false, updatable=false, generated="ALWAYS", columnDefinition="VARCHAR(26) GENERATED ALWAYS AS (ifNull(training_interviewer_id, 'no-interviewer')) VIRTUAL")
     */
    private ?string $virtualColumnTrainingInterviewerId;

    public function getUserIdentifier(): ?string
    {
        return $this->username ?? null;
    }

    /**
     * @EmailOrNoLoginPlaceholder(groups={"wizard.on-boarding.diary-keeper.user-identifier"}, message="wizard.diary-keeper.user-identifier.email")
     */
    public function getUsername(): ?string
    {
        return $this->getUserIdentifier();
    }

    public function setUserIdentifier(?string $username): self
    {
        $this->username = is_null($username) ? $username : strtolower($username);
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

    public function eraseCredentials()
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
        return [$this->id ?? null, $this->username ?? null, $this->password ?? null, $this->getRoles()];
    }

    public function __unserialize($data)
    {
        [$this->id, $this->username, $this->password, $this->roles] = $data;
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
        return $this->trainingInterviewer;
    }

    public function setTrainingInterviewer(?Interviewer $trainingInterviewer): self
    {
        $this->trainingInterviewer = $trainingInterviewer;

        return $this;
    }

    /**
     * @ORM\PreFlush()
     */
    public function trainingInterviewerPreFlush(PreFlushEventArgs $eventArgs)
    {
        if ($eventArgs->getEntityManager()->getUnitOfWork()->isScheduledForInsert($this)) {
            $this->trainingInterviewer = $this->diaryKeeper?->getHousehold()?->getAreaPeriod()?->getTrainingInterviewer();
        }
    }
}
