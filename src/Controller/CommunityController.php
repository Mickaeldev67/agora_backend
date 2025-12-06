<?php

namespace App\Controller;

use App\Entity\Community;
use App\Repository\TopicRepository;
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

    #[Route('api/community/create', name: 'app_community_create', methods: ['POST'])]
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
            return $this->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }

        return $this->json([
            'message' => sprintf(
                'La communauté %s a bien été créée.',
                $name
            ),
        ]);
    }
}
