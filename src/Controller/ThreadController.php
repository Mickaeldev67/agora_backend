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

    #[Route('api/thread/create', name: 'app_thread_create', methods: ['POST'])]
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
        'total_reactions' => $post->total(),
        'created_at' => $post->getCreatedAt()?->format('Y-m-d H:i:s'),
        'updated_at' => $post->getUpdatedAt()?->format('Y-m-d H:i:s'),
    ])
    ->toArray();
        return $this->json([
            'data' => $data,
        ], Response::HTTP_OK, [], ['groups' => 'post']);
    }
}
