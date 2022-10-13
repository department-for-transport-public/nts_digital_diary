<?php

namespace App\Entity;

use App\Repository\UserRepository;
use App\Validator\Constraints\EmailOrNoLoginPlaceholder;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\Exception\LogicException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(columns={"username"})})
 * @UniqueEntity(
 *     groups={"wizard.on-boarding.diary-keeper.user-identifier", "admin.interviewer"},
 *     fields={"username"},
 *     message="common.email.already-used"
 * )
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const NO_LOGIN_PLACEHOLDER = "no-login";

    public const ROLE_USER = 'ROLE_USER';
    public const ROLE_INTERVIEWER = 'ROLE_INTERVIEWER';
    public const ROLE_DIARY_KEEPER = 'ROLE_DIARY_KEEPER';

    use IdTrait;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     * @EmailOrNoLoginPlaceholder(groups={"wizard.on-boarding.diary-keeper.user-identifier", "admin.interviewer"}, message="wizard.diary-keeper.user-identifier.email")
     * @Assert\NotBlank(groups={"wizard.on-boarding.diary-keeper.user-identifier", "admin.interviewer"}, message="wizard.diary-keeper.user-identifier.not-blank")
     * @Assert\Length(groups={"wizard.on-boarding.diary-keeper.user-identifier", "admin.interviewer"}, maxMessage="common.string.max-length", max=180)
     */
    private ?string $username;

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

    public function getUserIdentifier(): ?string
    {
        return $this->username ?? null;
    }

    public function getUsername(): ?string
    {
        return $this->username ?? null;
    }

    public function setUserIdentifier(?string $username): self
    {
        $this->username = strtolower($username);
        return $this;
    }

    public function setUsername(?string $username): self
    {
        $this->username = strtolower($username);
        return $this;
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
            throw new LogicException("Cannot set DiaryKeeper for Interviewer User");
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

    public function isValidForLogin(): bool
    {
        $diaryKeeper = $this->getDiaryKeeper();
        $household = $diaryKeeper ? $diaryKeeper->getHousehold() : null;

        $isDiaryKeeper = !!$diaryKeeper;
        $hasOnboardedHousehold = $household && $household->getIsOnboardingComplete();

        return $this->hasValidIdentifierForLogin() && (!$isDiaryKeeper || $hasOnboardedHousehold);
    }

    public function hasValidIdentifierForLogin(): bool
    {
        return $this->getUserIdentifier() && !self::isNoLoginPlaceholder($this->username);
    }

    public static function isNoLoginPlaceholder(?string $userIdentifier): bool
    {
        return strpos($userIdentifier, self::NO_LOGIN_PLACEHOLDER.':') === 0;
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
}
