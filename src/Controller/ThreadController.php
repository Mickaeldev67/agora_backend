<?php

namespace App\Controller;

use App\Entity\Thread;
use App\Repository\CommunityRepository;
use App\Repository\ThreadRepository;
use App\Service\ReactionService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class ThreadController extends AbstractController
{
    #[Route('/api/thread/create', name: 'app_thread_create', methods: ['POST'])]
    public function create(EntityManagerInterface $em, Request $request, CommunityRepository $repo): JsonResponse
    {
        
        $data = json_decode($request->getContent(), true);
        $thread = new Thread();
        $thread->setTitle($data['title'] ?? null);
        $thread->setContent($data['content'] ?? null);
        $currentDate = new \DateTimeImmutable();
        $thread->setCreatedAt($currentDate);
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(
                [
                    'error' => 'Non autorisé'
                ],
                Response::HTTP_UNAUTHORIZED
            );
        }
        $thread->setUser($user);
        try {
            $community = $repo->find($data['community_id']) ?? null;
            if (!$community) {
                return $this->json(
                    [
                        'error' => 'Communauté non trouvé !'
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }
            $thread->setCommunity($community);
            
        } catch (\Exception $e) {
            return new JsonResponse(
                ['message' => 'Une erreur avec la communauté est survenu : ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
        
        $em->persist($thread);
        $em->flush();
        return new JsonResponse([
            'message' => 'Thread créé avec succès !',
            'thread' => [
                'id' => $thread->getId(),
                'title' => $thread->getTitle(),
                'content' => $thread->getContent(),
                'created_at' => $thread->getCreatedAt()->format('Y-m-d H:i:s'),
                'user' => [
                    'pseudo' => $thread->getUser()->getPseudo(),
                    'isOwner' => $thread->getUser()->getId() === $this->getUser()?->getId()
                ],
                'community' => [
                    'id' => $thread->getCommunity()->getId(),
                    'name' => $thread->getCommunity()->getName(),
                ],
                'posts' => [],
                'reactions' => [],
            ],
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/thread/update', name: 'app_thread_update', methods: ['PUT'])]
    public function update(EntityManagerInterface $em, ThreadRepository $repo, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $id = $data['id'] ?? null;
        $thread = $repo->find($id);
        if (!$thread) {
            return $this->json(
                [
                    'error' => 'Thread non trouvé !'
                ],
                Response::HTTP_NOT_FOUND
            );
        }
        $title = $data['title'] ?? null;
        $content = $data['content'] ?? null;
        if ($title) {
            $thread->setTitle($title);
        }
        if ($content) {
            $thread->setContent($content);
        }
        $thread->setUpdatedAt(new \DateTime());
        $user = $this->getUser();
        if (!$user) {
            return $this->json(
                [
                    'error' => 'Non autorisé !'
                ],
                Response::HTTP_UNAUTHORIZED
            );
        }

        if ($thread->getUser()->getId() !== $user->getId()) {
            return $this->json(
                [
                    'error' => "Vous n'êtes pas le propriétaire de ce thread !"
                ],
                Response::HTTP_FORBIDDEN
            );
        }

        $em->persist($thread);
        $em->flush();

        // Implementation for updating a thread would go here.
        return $this->json([
            'thread' => $thread,
        ], Response::HTTP_OK, [], ['groups' => 'thread']);
    }

    #[Route('/api/thread/delete/{id}', name: 'app_thread_delete', methods: ['DELETE'])]
    public function delete(EntityManagerInterface $em, ThreadRepository $repo, int $id): JsonResponse
    {
        $thread = $repo->find($id);
        if (!$thread) {
            return $this->json(
                [
                    'error' => 'Thread non trouvé.'
                ],
                Response::HTTP_NOT_FOUND
            );
        }
        $user = $this->getUser();
        if (!$user) {
            return $this->json(
                [
                    'error' => 'Action non autorisé !'
                ],
                Response::HTTP_UNAUTHORIZED
            );
        }

        if ($thread->getUser()->getId() !== $user->getId() && !$user->isAdmin()) {
            return $this->json(
                [
                    'error' => 'Vous ne pouvez pas supprimer ce thread'
                ],
                Response::HTTP_FORBIDDEN
            );
        }

        $em->remove($thread);
        $em->flush();

        return $this->json([
            'message' => 'Le thread a bien été supprimé !',
        ]);
    }


    #[Route('/api/thread/best-reacted', name: 'app_thread_best_reacted', methods: ['GET'])]
    public function getBestReactedThreads(ThreadRepository $repo, ReactionService $reactionService): JsonResponse
    {
        // Fetch all threads and sort them by total reactions (DESC)
        $threads = $repo->findAll();

        $user = $this->getUser();

        // Sort threads by total reactions in descending order
        usort($threads, function (Thread $a, Thread $b) {
            return $b->getTotalReaction() <=> $a->getTotalReaction();
        });

        // Format the response
        $data = array_map(fn(Thread $thread) => [
            'id' => $thread->getId(),
            'title' => $thread->getTitle(),
            'content' => $thread->getContent(),
            'user' => [
                'id' => $thread->getUser()?->getId(),
                'pseudo' => $thread->getUser()?->getPseudo(),
                'isOwner' => $thread->getUser()->getId() === $this->getUser()?->getId(),
                'isAdmin' => $this->getUser()?->isAdmin(),
            ],
            'nbVote' => $thread->getTotalReaction(),
            'createdAt' => $thread->getCreatedAt(),
            'updatedAt' => $thread->getUpdatedAt(),
            'community' => $thread->getCommunity(),
            'nbPost' => $thread->getPosts()->count() ?? 0,
            'reaction' => $reactionService->getUserReactionForThread($user, $thread)
        ], $threads);

        return $this->json(
            $data,
            Response::HTTP_OK,
            [],
            ['groups' => 'thread']
        );
    }

    #[Route('/api/thread/{id}/posts', name: 'app_thread_posts', methods: ['GET'])]
    public function getPosts($id, ThreadRepository $repo, ReactionService $reactionService): JsonResponse
    {
        $user = $this->getUser();
        if (!ctype_digit((string)$id)) {
            return new JsonResponse(
                ['message' => "Thread non trouvé. L'id doit être un entier !"],
                Response::HTTP_NOT_FOUND
            );
        }
        $thread = $repo->find($id);
        if (!$thread) {
            return new JsonResponse(
                ['message' => 'Thread non trouvé.'],
                Response::HTTP_NOT_FOUND
            );
        }

        if (!$thread) {
            return new JsonResponse(
                ['message' => 'Thread non trouvé.'],
                Response::HTTP_NOT_FOUND
            );
        }
        $posts = $thread->getPosts();
        $data = $posts
            ->map(fn($post) => [
                'id' => $post->getId(),
                'content' => $post->getContent(),
                'pseudo' => $post->getUser()?->getPseudo(),
                'nbVote' => $post->getTotalReaction(),
                'createdAt' => $post->getCreatedAt()?->format('Y-m-d H:i:s'),
                'updatedAt' => $post->getUpdatedAt()?->format('Y-m-d H:i:s'),
                'reaction' => $reactionService->getUserReactionForPost($user, $post),
            ])
            ->toArray();
        return $this->json(
            [
                'thread' => [
                    'id' => $thread->getId(),
                    'createdAt' => $thread->getCreatedAt(),
                    'updatedAt' => $thread->getUpdatedAt(),
                    'nbVote' => $thread->getTotalReaction(),
                    'user' => [
                        'id' => $thread->getUser()?->getId(),
                        'pseudo' => $thread->getUser()?->getPseudo(),
                        'isOwner' => $thread->getUser()->getId() === $this->getUser()?->getId()
                    ],
                    'title' => $thread->getTitle(),
                    'content' => $thread->getContent(),
                    'nbPost' => $thread->getPosts()->count(),
                    'community' => [
                        'id' => $thread->getCommunity()->getId(),
                        'name' => $thread->getCommunity()->getName(),
                    ],
                    'reaction' => $reactionService->getUserReactionForThread($user, $thread),
                ],
                'posts' => $data,
            ],
            Response::HTTP_OK,
            [],
            ['groups' => 'post']
        );
    }
}
