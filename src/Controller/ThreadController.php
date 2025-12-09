<?php

namespace App\Controller;

use App\Entity\Thread;
use App\Repository\CommunityRepository;
use App\Repository\ThreadRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints\Json;

final class ThreadController extends AbstractController
{
    #[Route('/thread', name: 'app_thread')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/ThreadController.php',
        ]);
    }

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
                    'error' => 'Unauthorized'
                ],
                Response::HTTP_UNAUTHORIZED
            );
        }
        $thread->setUser($user);
        try {
            $community = $repo->find($data['community_id'] ?? null);
            if (!$community) {
                return $this->json(
                    [
                        'error' => 'Community not found'
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }
            $thread->setCommunity($community);
        } catch (\Exception $e) {
            return $this->json(
                [
                    'error' => 'An error occurred: ' . $e->getMessage()
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
        $em->persist($thread);
        $em->flush();
        return $this->json([
            'message' => 'Thread created successfully!',
            'thread' => $thread,
        ], Response::HTTP_CREATED, [], ['groups' => 'thread']);
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
                    'error' => 'Thread not found'
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
                    'error' => 'Unauthorized'
                ],
                Response::HTTP_UNAUTHORIZED
            );
        }

        if ($thread->getUser()->getId() !== $user->getId()) {
            return $this->json(
                [
                    'error' => 'You are not the owner of this thread'
                ],
                Response::HTTP_FORBIDDEN
            );
        }   

        $em->persist($thread);
        $em->flush();

        // Implementation for updating a thread would go here.
        return $this->json([
            'message' => 'Mise à jour du thread réussie !',
        ]);
    }

    #[Route('/api/thread/delete/{id}', name: 'app_thread_delete', methods: ['DELETE'])]
    public function delete(EntityManagerInterface $em, ThreadRepository $repo, int $id): JsonResponse
    {
        $thread = $repo->find($id);
        if (!$thread) {
            return $this->json(
                [
                    'error' => 'Thread not found'
                ],
                Response::HTTP_NOT_FOUND
            );
        }
        $user = $this->getUser();
        if (!$user) {
            return $this->json(
                [
                    'error' => 'Unauthorized'
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
            'message' => 'Thread deleted successfully!',
        ]);
    }


    #[Route('/thread/best-reacted', name: 'app_thread_best_reacted', methods: ['GET'])]
    public function getBestReactedThreads(ThreadRepository $repo): JsonResponse
    {
        // Fetch all threads and sort them by total reactions (DESC)
        $threads = $repo->findAll();

        // Sort threads by total reactions in descending order
        usort($threads, function (Thread $a, Thread $b) {
            return $b->getTotalReaction() <=> $a->getTotalReaction();
        });

        // Format the response
        $data = array_map(fn(Thread $thread) => [
            'id' => $thread->getId(),
            'title' => $thread->getTitle(),
            'content' => $thread->getContent(),
            'pseudo' => $thread->getUser()?->getPseudo(),
            'nb_vote' => $thread->getTotalReaction(),
            'created_at' => $thread->getCreatedAt()?->format('Y-m-d H:i:s'),
        ], $threads);

        return $this->json([
            'data' => $data,
        ], Response::HTTP_OK, [], ['groups' => 'thread']);
    }

    #[Route('/api/thread/{id}/posts', name: 'app_thread_posts', methods: ['GET'])]
    public function getPosts(int $id, ThreadRepository $repo, SerializerInterface $serializer): JsonResponse
    {
        $thread = $repo->find($id);
        if (!$thread) {
            return $this->json(
                [
                    'error' => 'Thread not found'
                ],
                Response::HTTP_NOT_FOUND
            );
        }
        $posts = $thread->getPosts();

        $data = $posts
    ->map(fn($post) => [
        'id' => $post->getId(),
        'content' => $post->getContent(),
        'pseudo' => $post->getUser()?->getPseudo(),
        'nb_vote' => $post->getTotalReaction(),
        'nb_post' => $post->getThread()?->getPosts()->count(),
        'created_at' => $post->getCreatedAt()?->format('Y-m-d H:i:s'),
        'updated_at' => $post->getUpdatedAt()?->format('Y-m-d H:i:s'),
    ])
    ->toArray();
        return $this->json([
            'data' => $data,
        ], Response::HTTP_OK, [], ['groups' => 'post']);
    }
}
