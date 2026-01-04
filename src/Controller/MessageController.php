<?php

namespace App\Controller;

use App\Entity\Message;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

final class MessageController extends AbstractController
{
    #[Route('/message', name: 'app_message')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/MessageController.php',
        ]);
    }

    #[Route('/api/message/send/{id}', name: 'app_message_send', methods: ['POST'])]
    public function sendMessage(Request $request, EntityManagerInterface $em, int $id, UserRepository $repo, HubInterface $hub): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $content = $data['content'] ?? null;
        $recipient = $repo->find($id);
        if (!$recipient) {
            return $this->json(
                [
                    'error' => 'Expéditeur introuvable'
                ],
                Response::HTTP_BAD_REQUEST
            );
        }
        $issuer = $this->getUser();
        if (!$issuer) {
            return $this->json(
                [
                    'error' => 'Unauthorized'
                ],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $message = new Message();
        $message->setContent($content);
        $message->setIssuer($issuer);
        $message->setRecipient($recipient);
        $message->setIsView(false);
        $message->setCreatedAt(new \DateTimeImmutable());
        $em->persist($message);
        $em->flush();

        $update = new Update(
            // topic : chaque conversation peut avoir un topic unique, ici par exemple "conversation-1-2"
            sprintf('conversation-%d-%d', min($issuer->getId(), $recipient->getId()), max($issuer->getId(), $recipient->getId())),
            json_encode([
                'id' => $message->getId(),
                'content' => $message->getContent(),
                'issuer' => [
                    'id' => $issuer->getId(),
                    'pseudo' => $issuer->getPseudo(),
                ],
                'recipient' => [
                    'id' => $recipient->getId(),
                    'pseudo' => $recipient->getPseudo(),
                ],
                'is_view' => $message->isView(),
                'created_at' => $message->getCreatedAt()->format('Y-m-d H:i:s'),
            ])
        );

        $hub->publish($update);

        return $this->json([
            'id' => $message->getId(),
            'content' => $message->getContent(),
            'issuer' => [
                'id' => $issuer->getId(),
                'pseudo' => $issuer->getPseudo(),
            ],
            'recipient' => [
                'id' => $recipient->getId(),
                'pseudo' => $recipient->getPseudo(),
            ],
            'is_view' => $message->isView(),
            'created_at' => $message->getCreatedAt()->format('Y-m-d H:i:s'),
        ], Response::HTTP_OK);
    }

    #[Route('/api/message/pseudos', name: 'app_message_pseudos', methods: ['GET'])]
    public function getPseudos(
        \App\Repository\MessageRepository $messageRepo
    ): JsonResponse {
        $issuer = $this->getUser();
        if (!$issuer) {
            return $this->json([
                'error' => 'Unauthorized'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Query messages involving the user and compute the last message date per correspondent
        $qb = $messageRepo->createQueryBuilder('m')
            ->select(
                "CASE WHEN m.issuer = :user THEN IDENTITY(m.recipient) ELSE IDENTITY(m.issuer) END AS userId",
                "CASE WHEN m.issuer = :user THEN recipient.pseudo ELSE sender.pseudo END AS pseudo",
                'MAX(m.created_at) AS lastAt'
            )
            ->leftJoin('m.issuer', 'sender')
            ->leftJoin('m.recipient', 'recipient')
            ->where('m.issuer = :user OR m.recipient = :user')
            ->setParameter('user', $issuer)
            ->groupBy('userId', 'pseudo')
            ->orderBy('lastAt', 'DESC');

        $rows = $qb->getQuery()->getArrayResult();

        $pseudos = array_map(function ($row) {

            return [
                'id' => (int) $row['userId'],
                'pseudo' => $row['pseudo']
            ];
        }, $rows);

        return $this->json($pseudos, Response::HTTP_OK);
    }

    #[Route('/api/message/{id}', name: 'app_message_conversation', methods: ['GET'])]
    public function getMessagesWithUser(
        MessageRepository $repo,
        int $id,
        EntityManagerInterface $em
    ): JsonResponse {
        $issuer = $this->getUser();
        if (!$issuer) {
            return $this->json([
                'error' => 'Unauthorized'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Mark the last message sent by the other user (the "issuer" in that message)
        // as viewed when the current user is the recipient.
        $lastFromOther = $repo->createQueryBuilder('m')
            ->where('m.issuer = :other AND m.recipient = :me')
            ->setParameter('other', $repo->getEntityManager()->getReference('\App\\Entity\\User', $id))
            ->setParameter('me', $issuer)
            ->orderBy('m.created_at', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($lastFromOther && !$lastFromOther->isView()) {
            $lastFromOther->setIsView(true);
            $em->persist($lastFromOther);
            $em->flush();
        }

        $messages = $repo->createQueryBuilder('m')
            ->where('(m.issuer = :issuer AND IDENTITY(m.recipient) = :recipient) OR (m.recipient = :issuer AND IDENTITY(m.issuer) = :recipient)')
            ->setParameter('issuer', $issuer)
            ->setParameter('recipient', $id)
            ->orderBy('m.created_at', 'ASC')
            ->getQuery()
            ->getResult();

        $data = array_map(function (Message $message) {
            return [
                'id' => $message->getId(),
                'content' => $message->getContent(),
                'issuer' => [
                    'id' => $message->getIssuer()->getId(),
                    'pseudo' => $message->getIssuer()->getPseudo(),
                ],
                'recipient' => [
                    'id' => $message->getRecipient()->getId(),
                    'pseudo' => $message->getRecipient()->getPseudo(),
                ],
                'is_view' => $message->isView(),
                'created_at' => $message->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }, $messages);

        return $this->json($data, Response::HTTP_OK);
    }
}
