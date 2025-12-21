<?php

namespace App\Entity;

use App\Repository\CommunityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: CommunityRepository::class)]
class Community
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['thread', 'community'])]
    private ?int $id = null;

    #[Groups(['thread', 'community'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[Groups(['community'])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * @var Collection<int, Topic>
     */
    #[ORM\ManyToMany(targetEntity: Topic::class, inversedBy: 'communities', cascade: ['persist', 'remove'])]
    #[ORM\JoinTable(name: 'community_topic')]
    #[Groups(['community'])]
    private Collection $topics;

    /**
     * @var Collection<int, Thread>
     */
    #[ORM\OneToMany(targetEntity: Thread::class, mappedBy: 'community', orphanRemoval: true)]
    private Collection $threads;

    /**
     * @var Collection<int, UserCommunity>
     */
    #[ORM\OneToMany(targetEntity: UserCommunity::class, mappedBy: 'community', orphanRemoval: true)]
    private Collection $users;

    public function __construct()
    {
        $this->topics = new ArrayCollection();
        $this->threads = new ArrayCollection();
        $this->users = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, Topic>
     */
    public function getTopics(): Collection
    {
        return $this->topics;
    }

    public function addTopic(Topic $topic): static
    {
        if (!$this->topics->contains($topic)) {
            $this->topics->add($topic);
            $topic->addCommunity($this);
        }

        return $this;
    }

    public function removeTopic(Topic $topic): static
    {
        if ($this->topics->removeElement($topic)) {
            $topic->removeCommunity($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Thread>
     */
    public function getThreads(): Collection
    {
        return $this->threads;
    }

    public function addThread(Thread $thread): static
    {
        if (!$this->threads->contains($thread)) {
            $this->threads->add($thread);
            $thread->setCommunity($this);
        }

        return $this;
    }

    public function removeThread(Thread $thread): static
    {
        if ($this->threads->removeElement($thread)) {
            // set the owning side to null (unless already changed)
            if ($thread->getCommunity() === $this) {
                $thread->setCommunity(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, UserCommunity>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(UserCommunity $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setCommunity($this);
        }

        return $this;
    }

    public function removeUser(UserCommunity $user): static
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getCommunity() === $this) {
                $user->setCommunity(null);
            }
        }

        return $this;
    }
}
