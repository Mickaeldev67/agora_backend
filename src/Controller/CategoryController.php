<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class CategoryController extends AbstractController
{
    #[Route('/api/categories', name: 'app_category')]
    public function index(CategoryRepository $repo): JsonResponse
    {
        $categories = $repo->findAll();
        return $this->json($categories, Response::HTTP_OK, [], ['groups' => 'category']);
    }

    #[Route('/api/category/create', name: 'app_category_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $content = $request->getContent();
        $category = $serializer->deserialize($content, Category::class, "json", []);
        $em->persist($category);
        $em->flush();
        return $this->json([
            'message' => sprintf(
                'La catégorie %s a été créée avec succès.',
                $category->getName()
            ),
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/category/display', name: 'app_category_display', methods: ['GET'])]
    public function display(CategoryRepository $repo): JsonResponse
    {
        $categories = $repo->findAll();
        return $this->json($categories, Response::HTTP_OK, [], ['groups' => 'category']);
    }
}
