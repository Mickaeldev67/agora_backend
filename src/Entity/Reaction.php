<?php

namespace App\Entity;

use App\Repository\ReactionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ReactionRepository::class)]
class Reaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(['reaction', 'post', 'thread'])]
    private ?bool $is_liked = null;

    #[ORM\Column]
    #[Groups(['reaction', 'post', 'thread'])]
    private ?bool $is_disliked = null;

    #[ORM\ManyToOne(inversedBy: 'reactions')]
    #[Groups(['reaction'])]
    private ?Post $post = null;

    #[ORM\ManyToOne(inversedBy: 'reactions')]
    #[Groups(['reaction'])]
    private ?Thread $thread = null;

    #[ORM\ManyToOne(inversedBy: 'reactions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isLiked(): ?bool
    {
        return $this->is_liked;
    }

    public function setIsLiked(bool $is_liked): static
    {
        $this->is_liked = $is_liked;

        return $this;
    }

    public function isDisliked(): ?bool
    {
        return $this->is_disliked;
    }

    public function setIsDisliked(bool $is_disliked): static
    {
        $this->is_disliked = $is_disliked;

        return $this;
    }

    public function getPost(): ?Post
    {
        return $this->post;
    }

    public function setPost(?Post $post): static
    {
        $this->post = $post;

        return $this;
    }

    public function getThread(): ?Thread
    {
        return $this->thread;
    }

    public function setThread(?Thread $thread): static
    {
        $this->thread = $thread;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }
}
