<?php

namespace App\Entity;

use App\Repository\MessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $content = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?bool $is_view = null;

    #[ORM\ManyToOne(inversedBy: 'messages_sent')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $issuer = null;

    #[ORM\ManyToOne(inversedBy: 'messages_received')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $recipient = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function isView(): ?bool
    {
        return $this->is_view;
    }

    public function setIsView(bool $is_view): static
    {
        $this->is_view = $is_view;

        return $this;
    }

    public function getIssuer(): ?User
    {
        return $this->issuer;
    }

    public function setIssuer(?User $issuer): static
    {
        $this->issuer = $issuer;

        return $this;
    }

    public function getRecipient(): ?User
    {
        return $this->recipient;
    }

    public function setRecipient(?User $recipient): static
    {
        $this->recipient = $recipient;

        return $this;
    }
}
