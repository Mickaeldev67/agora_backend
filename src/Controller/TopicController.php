<?php

namespace App\Controller;

use App\Entity\Topic;
use App\Repository\CategoryRepository;
use App\Repository\TopicRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class TopicController extends AbstractController
{
    #[Route('/topic', name: 'app_topic')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/TopicController.php',
        ]);
    }

    #[Route('/api/topic/create', name: 'app_topic_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, CategoryRepository $repo): JsonResponse
    {
        // 1. On décode en tableau
        $data = json_decode($request->getContent(), true);

        if (!isset($data['category_id'])) {
            return $this->json(['error' => 'Missing category ID'], 400);
        }

        // 2. Récupération de la catégorie depuis la base
        $category = $repo->find($data['category_id']);

        if (!$category) {
            return $this->json(['error' => 'Category not found'], 404);
        }

        // 3. Création du topic
        $topic = new Topic();
        $topic->setName($data['name'] ?? null);
        $topic->setCategory($category);

        $em->persist($topic);
        $em->flush();

        return $this->json([
            'message' => sprintf(
                'Le topic %s a été créé avec succès pour la catégorie %s.',
                $topic->getName(),
                $category->getName()
            )
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/topic/display', name: 'app_topic_display', methods: ['GET'])]
    function display(TopicRepository $repo):JsonResponse {
        $topics = $repo->findAll();
        if (!$topics) {
            return new JsonResponse(
                ['message' => "Un problème a eu lieu dans la récupération des topics"],
                Response::HTTP_NO_CONTENT
            );
        }
        return $this->json($topics, Response::HTTP_OK, [], ['groups' => 'topic']);
    }
}
