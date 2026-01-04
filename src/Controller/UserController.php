<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class UserController extends AbstractController
{
    private UserPasswordHasherInterface $hasher;
    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->hasher = $passwordHasher;
    }

    #[Route('/api/register', name: 'app_user_register', methods: ['POST'])]
    public function registerUser(Request $request, EntityManagerInterface $em, ValidatorInterface $validator, SerializerInterface $serializer): JsonResponse
    {
        $content = $request->getContent();
        $user = $serializer->deserialize($content, User::class, "json", []);
        $errors = $validator->validate($user);
        $message = "";
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $message = $message . ' ' . $error->getMessage();
            }
            return new JsonResponse(
                [
                    'message' => $message,
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $password = $user->getPassword();
        if (!$password || strlen($password) < 7) {
            return new JsonResponse(
                [
                    'message' => "Le mot de passe doit faire au moins 7 caractères.",
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $user->setRoles([User::ROLE_USER]);
        $user->setPassword(
            $this->hasher->hashPassword(
                $user,
                $user->getPassword()
            )
        );

        try {
            $em->persist($user);
            $em->flush();
        } catch (UniqueConstraintViolationException $e) {
            // Ici tu peux checker quel champ pose problème
            $message = 'Un utilisateur avec cet email ou pseudo existe déjà.';
            return new JsonResponse(['message' => $message], JsonResponse::HTTP_CONFLICT);
        }

        return $this->json([
            'user' => $user,
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/user/communities', name: 'app_user_communities', methods: ['GET'])]
    public function getCommunitys(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(
                [
                    'error' => 'Unauthorized'
                ],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $userCommunities = $user->getUserCommunities();
        $communities = [];

        foreach ($userCommunities as $community) {
            if ($community->isFavorite()) {
                $communities[] = $community->getCommunity();
            }
        }

        $data = (new ArrayCollection($communities))->map(fn($community) => [
            'id' => $community->getId(),
            'name' => $community->getName(),
            'description' => $community->getDescription(),
        ])->toArray();

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/api/user/me', name: 'api_user_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        return $this->json([
            'id' => $user->getId(),
            'pseudo' => $user->getPseudo(),
            'email' => $user->getEmail(),
        ]);
    }
}
