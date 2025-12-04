<?php

namespace App\Controller;

use App\Entity\User;
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
        if (count($errors) > 0) {
            return $this->json([
                'errors' => (string) $errors
            ], Response::HTTP_BAD_REQUEST);
        }

        $password = $user->getPassword();
        if (!$password || strlen($password) < 7) {
            return $this->json([
                'errors' => 'Le mot de passe doit faire au moins 7 caractères.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $user->setRoles([User::ROLE_USER]);
        $user->setPassword(
            $this->hasher->hashPassword(
                $user,
                $user->getPassword()
            )
        );

        $em->persist($user);
        $em->flush();

        return $this->json([
            'user' => $user,
        ], Response::HTTP_CREATED);
    }
}
