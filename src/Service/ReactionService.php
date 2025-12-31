<?php

namespace App\Service;

use App\Entity\Thread;
use App\Entity\Post;
use App\Entity\User;
use App\Repository\ReactionRepository;

class ReactionService
{
    public function __construct(
        private ReactionRepository $reactionRepository
    ) {}

    public function getUserReactionForThread(?User $user, Thread $thread): ?array
    {
        if (!$user) {
            return null;
        }

        $reaction = $this->reactionRepository->findOneBy([
            'user' => $user,
            'thread' => $thread,
        ]);

        if (!$reaction) {
            return [
                'isLiked' => false,
                'isDisliked' => false,
            ];
        }

        return [
            'isLiked' => $reaction->isLiked(),
            'isDisliked' => $reaction->isDisliked(),
        ];
    }

    public function getUserReactionForPost(?User $user, Post $post): ?array
    {
        if (!$user) {
            return null;
        }

        $reaction = $this->reactionRepository->findOneBy([
            'user' => $user,
            'post' => $post,
        ]);

        if (!$reaction) {
            return [
                'isLiked' => false,
                'isDisliked' => false,
            ];
        }

        return [
            'isLiked' => $reaction->isLiked(),
            'isDisliked' => $reaction->isDisliked(),
        ];
    }
}