<?php

namespace App\Controller;

use App\Entity\Community;
use App\Entity\UserCommunity;
use App\Repository\CommunityRepository;
use App\Repository\TopicRepository;
use App\Repository\UserCommunityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CommunityController extends AbstractController
{
    #[Route('/community', name: 'app_community')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/CommunityController.php',
        ]);
    }

    #[Route('/api/community/create', name: 'app_community_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, TopicRepository $repo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $topics = $data['topics'] ?? [];
        $description = $data['description'] ?? null;
        $name = $data['name'] ?? null;
        $community = new Community();
        $community->setName($name);
        $community->setDescription($description);
        try {
            foreach ($topics as $topicId) {
                
                $topic = $repo->find($topicId);
                if (!$topic) {
                    return $this->json(
                        [
                            'error' => "Topic with ID $topicId not found"
                        ],
                        Response::HTTP_BAD_REQUEST
                    );
                }
                $community->addTopic($topic);
            }

            // Here you would create the Community entity, set its properties,
            // associate the topics, persist it and flush.
            
            $em->persist($community);
            $em->flush();
        } catch (\Exception $e) {
            return $this->json(
                [
                    'error' => 'An error occurred: ' . $e->getMessage()
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return $this->json([
            'message' => sprintf(
                'La communauté %s a bien été créée.',
                $name
            ),
        ]);
    }

    #[Route('/community/{id}/threads', name: 'app_community_threads', methods: ['GET'])]
    public function getThreads($id, CommunityRepository $repo): JsonResponse
    {
        if (!ctype_digit((string)$id)) {
            return new JsonResponse(
                ['message' => "Communauté non trouvé. L'id doit être un entier !"],
                Response::HTTP_NOT_FOUND
            );
        }
        $community = $repo->find($id);

        if (!$community) {
            return $this->json([
                'message' => sprintf("Communauté avec l'ID %s introuvable", $id)
            ], Response::HTTP_NOT_FOUND);
        }

        $threads = $community->getThreads();

        $data = $threads->map(fn($thread) => [
            'id' => $thread->getId(),
            'title' => $thread->getTitle(),
            'created_at' => $thread->getCreatedAt()?->format('Y-m-d H:i:s'),
            'content' => $thread->getContent(),
            'user' => [
                'id' => $thread->getUser()?->getId(),
                'pseudo' => $thread->getUser()?->getPseudo(),
            ],
            'Reactions' => [
                'likes' => $thread->getReactions()->filter(fn($reaction) => $reaction->isLiked())->count(),
                'dislikes' => $thread->getReactions()->filter(fn($reaction) => $reaction->isDisliked())->count(),
                'total' => $thread->getTotalReaction(),
            ],
        ])->toArray();

        return $this->json([
            'community' => $community, 
            'threads' => $data,
        ], Response::HTTP_OK, [], ['groups' => 'community']);
    }

    #[Route('/api/community/{id}/add-favorite', name: 'app_community_add_favorite', methods: ['POST'])]
    public function addfavoriteCommunity(Request $request, EntityManagerInterface $em, CommunityRepository $repo, int $id): JsonResponse
    {
        // Implementation for adding a favorite community would go here.
        $request = json_decode($request->getContent(), true);
        $user = $this->getUser();
        $communityId = $request['community_id'] ?? null;
        if (!$user) {
            return $this->json(
                [
                    'error' => 'Unauthorized'
                ],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $community = $repo->find($communityId);
        if (!$community) {
            return $this->json(
                [
                    'error' => 'Community not found'
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $userCommunity = new UserCommunity();
        $userCommunity->setUser($user);
        $userCommunity->setCommunity($community);
        $userCommunity->setIsFavorite(true);
        $em->persist($userCommunity);
        $em->flush();

        return $this->json([
            'message' => 'La communauté a été ajoutée aux favoris avec succès.',
        ]);
    }

    #[Route('/api/community/{id}/delete-favorite', name: 'app_community_delete_favorite', methods: ['DELETE'])]
    public function deleteFavoriteCommunity(int $id, EntityManagerInterface $em, UserCommunityRepository $repo): JsonResponse
    {
        // Implementation for deleting a favorite community would go here.
        $user = $this->getUser();
        if (!$user) {
            return $this->json(
                [
                    'error' => 'Unauthorized'
                ],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $userCommunity = $repo->findOneBy([
            'user' => $user,
            'community' => $id,
            'isFavorite' => true,
        ]);

        if (!$userCommunity) {
            return $this->json(
                [
                    'error' => 'Erreur lors de la suppression de la communauté des favoris'
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $em->remove($userCommunity);
        $em->flush();

        return $this->json([
            'message' => 'La communauté a été supprimée des favoris avec succès.',
        ]);
    }
}
