<?php

namespace App\Controller;

use App\Repository\CommunityRepository;
use App\Repository\ThreadRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class SearchController extends AbstractController
{
    #[Route('/search', name: 'app_search', methods: ['Post'])]
    public function index(Request $request, CommunityRepository $communityRepo, ThreadRepository $threadRepo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $query = $data['query'] ?? '';

        $communities = $communityRepo->createQueryBuilder('c')
            ->where('c.name LIKE :query OR c.description LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->getQuery()
            ->getArrayResult();

        $threads = $threadRepo->createQueryBuilder('t')
            ->where('t.title LIKE :query OR t.content LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->getQuery()
            ->getArrayResult();

        return $this->json([
            'communities' => $communities,
            'threads' => $threads,
        ]);
    }
}
