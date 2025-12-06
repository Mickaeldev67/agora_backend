<?php

namespace App\Controller;

use App\Entity\Thread;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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
    public function create(EntityManagerInterface $em, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $thread = new Thread();
        $thread->setTitle($data['title'] ?? null);
        $thread->setContent($data['content'] ?? null);
        $currentDate = new \DateTimeImmutable();
        $thread->setCreatedAt($currentDate);
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }
        $thread->setUser($user);
        $em->persist($thread);
        $em->flush();
        return $this->json([
            'message' => 'Thread created successfully!',
            'thread' => $thread,
        ], Response::HTTP_CREATED, [], ['groups' => 'thread']);
    }
}
