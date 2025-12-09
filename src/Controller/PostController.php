<?php

namespace App\Controller;

use App\Entity\Post;
 use App\Repository\ThreadRepository;
 use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PostController extends AbstractController
{
    #[Route('/post', name: 'app_post')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/PostController.php',
        ]);
    }
    #[Route('/api/post/create', name: 'app_post_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, ThreadRepository $repo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $content = $data['content'] ?? null;
        $createdAt = new \DateTimeImmutable();
        $user = $this->getUser();
        try {
            $thread = $repo->find($data['thread_id'] ?? null);
            if (!$thread) {
                return $this->json(
                    [
                        'error' => 'Thread not found'
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }
        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        if (!$user) {
            return $this->json(
                [
                    'error' => 'Unauthorized'
                ],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $post = new Post();
        $post->setContent($content);
        $post->setCreatedAt($createdAt);
        $post->setUser($user);
        $post->setThread($thread);
        $em->persist($post);
        $em->flush();

        // Implementation for creating a post would go here.
        return $this->json(
            [
                'message' => 'Le poste à été créé avec succès.',
                'post' => $post,
            ], Response::HTTP_CREATED, [], ['groups' => 'post']);
    }

    #[Route('/api/post/update', name: 'app_post_update', methods: ['PUT'])]
    public function update(Request $request, EntityManagerInterface $em, PostRepository $repo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $id = $data['id'] ?? null;
        $content = $data['content'] ?? null;
        $user = $this->getUser();
        if (!$user) {
            return $this->json(
                [
                    'error' => 'Unauthorized'
                ],
                Response::HTTP_UNAUTHORIZED
            );
        }
        try {
            $post = $repo->find($id);
            if (!$post) {
                return $this->json(
                    [
                        'error' => 'Post non trouvé'
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }
            if ($post->getUser()->getId() !== $user->getId()) {
                return $this->json(
                    [
                        'error' => 'Vous ne pouvez pas modifier ce post !'
                    ],
                    Response::HTTP_FORBIDDEN
                );
            }
            // update content and timestamp
            $post->setContent($content);
            $post->setUpdatedAt(new \DateTime());
            $em->persist($post);
            $em->flush();
        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json(
            [
                'message' => 'Le poste a été mis à jour avec succès.',
                'post' => $post,
            ], Response::HTTP_OK, [], ['groups' => 'post']);
    }

    #[Route('/api/post/delete', name: 'app_post_delete', methods: ['DELETE'])]
    public function delete(Request $request, EntityManagerInterface $em, PostRepository $repo) 
    {
        $data = json_decode($request->getContent(), true);
        $id = $data['id'] ?? null;
        $user = $this->getUser();
        if (!$user) {
            return $this->json(
                [
                    'error' => 'Unauthorized'
                ],
                Response::HTTP_UNAUTHORIZED
            );
        }

        try {
            $post = $repo->find($id);
            if (!$post) {
                return $this->json(
                    [
                        'error' => 'Post non trouvé'
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }
            if ($post->getUser()->getId() !== $user->getId() && !$user->isAdmin()) {
                return $this->json(
                    [
                        'error' => 'Vous ne pouvez pas modifier ce post !'
                    ],
                    Response::HTTP_FORBIDDEN
                );
            }
            $em->remove($post);
            $em->flush();
        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json(
            [
                'message' => 'Le poste a été supprimer avec succès !',
            ], Response::HTTP_OK, [], ['groups' => 'post']);
    }
}
