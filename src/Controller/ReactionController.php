<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Reaction;
use App\Repository\PostRepository;
use App\Repository\ReactionRepository;
use App\Repository\ThreadRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ReactionController extends AbstractController
{
    #[Route('/reaction', name: 'app_reaction', methods: ['POST'])]
    public function index(): JsonResponse
    {

        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/ReactionController.php',
        ]);
    }

    #[Route('/api/reaction/like', name: 'app_reaction_like', methods: ['POST'])]
    public function like(
        Request $request, 
        ReactionRepository $reactionRepo, 
        PostRepository $postRepository, 
        ThreadRepository $threadRepository, 
        EntityManagerInterface $em
        ): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json([
                'error' => 'Unauthorized'
            ], Response::HTTP_UNAUTHORIZED);
        }
        $data = json_decode($request->getContent(), true);
        $type = $data['type'] ?? null;
        $id = $data['id'] ?? null;
        $isLiked = $data['isLiked'];
        $post = null;
        $thread = null;
        if ($type == "post") {
            $post = $postRepository->find($id);
            if (!$post) {
                return $this->json([
                    'error' => 'Post not found'
                ], Response::HTTP_BAD_REQUEST); 
            }
        }

        if ($type == "thread") {
            $thread = $threadRepository->find($id);
            if (!$thread) {
                return $this->json([
                    'error' => 'Thread not found'
                ], Response::HTTP_BAD_REQUEST); 
            }
        }

        if ($type !== "post" && $type !== "thread") {
            return $this->json([
                'error' => 'Invalid type'
            ], Response::HTTP_BAD_REQUEST); 
        }

        if (isset($isLiked) && $isLiked) {
            $existingReaction = $reactionRepo->findOneBy([
                'user' => $user,
                'post' => $post,
                'thread' => $thread
            ]);
            if ($existingReaction)
            {
                if ($existingReaction->isLiked() === true) {
                    $em->remove($existingReaction);
                } else {
                    $existingReaction->setIsLiked(true);
                    $existingReaction->setIsDisliked(false);
                    $em->persist($existingReaction);
                    
                }
            } else {
                $reaction = new Reaction();
                $reaction->setIsLiked(true);
                $reaction->setIsDisliked(false);
                $reaction->setUser($user);
                $reaction->setPost($post);
                $reaction->setThread($thread);
                $em->persist($reaction);
            }
        } elseif (isset($isLiked) && !$isLiked) {
            $existingReaction = $reactionRepo->findOneBy([
                'user' => $user,
                'post' => $post,
                'thread' => $thread
            ]);
            if ($existingReaction)
            {
                if ($existingReaction->isLiked() === false) {
                    $em->remove($existingReaction);
                } else {
                    $existingReaction->setIsDisliked(true);
                    $existingReaction->setIsLiked(false);
                    $em->persist($existingReaction);
                    
                }
            } else {
                $reaction = new Reaction();
                $reaction->setIsLiked(false);
                $reaction->setIsDisliked(true);
                $reaction->setUser($user);
                $reaction->setPost($post);
                $reaction->setThread($thread);
                $em->persist($reaction);
            }
        } 

        $em->flush();

        if (isset($reaction)) {

            return $this->json([
                'message' => $reaction,
            ], Response::HTTP_CREATED, [], ['groups' => 'reaction']);
        } else {
            return $this->json([
                'message' => $existingReaction,
            ], Response::HTTP_OK, [], ['groups' => 'reaction']);
        }
    }

    #[Route('/api/reaction/dislike', name: 'app_reaction_dislike', methods: ['POST'])]
    public function dislike(
        Request $request, 
        ReactionRepository $reactionRepo, 
        PostRepository $postRepository, 
        ThreadRepository $threadRepository, 
        EntityManagerInterface $em
    ): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json([
                'error' => 'Unauthorized'
            ], Response::HTTP_UNAUTHORIZED);
        }
        $data = json_decode($request->getContent(), true);
        $type = $data['type'] ?? null;
        $id = $data['id'] ?? null;
        $isDisliked = $data['isDisliked'];
        $post = null;
        $thread = null;
        if ($type == "post") {
            $post = $postRepository->find($id);
            if (!$post) {
                return $this->json([
                    'error' => 'Post not found'
                ], Response::HTTP_BAD_REQUEST); 
            }
        }

        if ($type == "thread") {
            $thread = $threadRepository->find($id);
            if (!$thread) {
                return $this->json([
                    'error' => 'Thread not found'
                ], Response::HTTP_BAD_REQUEST); 
            }
        }

        if ($type !== "post" && $type !== "thread") {
            return $this->json([
                'error' => 'Invalid type'
            ], Response::HTTP_BAD_REQUEST); 
        }

        if (isset($isDisliked) && $isDisliked) {
            $existingReaction = $reactionRepo->findOneBy([
                'user' => $user,
                'post' => $post,
                'thread' => $thread
            ]);
            if ($existingReaction)
            {
                if ($existingReaction->isDisliked() === true) {
                    $em->remove($existingReaction);
                } else {
                    $existingReaction->setIsLiked(false);
                    $existingReaction->setIsDisliked(true);
                    $em->persist($existingReaction);
                    
                }
            } else {
                $reaction = new Reaction();
                $reaction->setIsLiked(false);
                $reaction->setIsDisliked(true);
                $reaction->setUser($user);
                $reaction->setPost($post);
                $reaction->setThread($thread);
                $em->persist($reaction);
            }
        } elseif (isset($isDisliked) && !$isDisliked) {
            $existingReaction = $reactionRepo->findOneBy([
                'user' => $user,
                'post' => $post,
                'thread' => $thread
            ]);
            if ($existingReaction)
            {
                if ($existingReaction->isDisliked() === false) {
                    $em->remove($existingReaction);
                } else {
                    $existingReaction->setIsDisliked(false);
                    $existingReaction->setIsLiked(true);
                    $em->persist($existingReaction);
                    
                }
            } else {
                $reaction = new Reaction();
                $reaction->setIsLiked(true);
                $reaction->setIsDisliked(false);
                $reaction->setUser($user);
                $reaction->setPost($post);
                $reaction->setThread($thread);
                $em->persist($reaction);
            }
        } 

        $em->flush();

        if (isset($reaction)) {

            return $this->json([
                'reaction' => $reaction,
            ], Response::HTTP_CREATED, [], ['groups' => 'reaction']);
        } else {
            return $this->json([
                'message' => 'Réaction modifié avec succès !',
            ], Response::HTTP_OK);
        }
    }
}
