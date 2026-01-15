<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Community;
use App\Entity\Message;
use App\Entity\Post;
use App\Entity\Thread;
use App\Entity\Topic;
use App\Entity\User;
use App\Entity\UserCommunity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);
        $userAdmin = $this->createUser('user@user.com', 'Admin67', "user123", [User::ROLE_ADMIN]);
        $manager->persist($userAdmin);

        $userTenet = $this->createUser('user1@user.com', 'TenetG0R', "user123", [User::ROLE_USER]);
        $manager->persist($userTenet);

        $categoryArts = $this->createCategory("Arts et spectacles");
        $manager->persist($categoryArts);

        $categoryBatiment = $this->createCategory("Bâtiment");
        $manager->persist($categoryBatiment);

        $regisseurSon = $this->createTopic("Régisseur son", $categoryArts);
        $regisseurLumiere = $this->createTopic("Régisseur lumière", $categoryArts);
        $acteur = $this->createTopic("Acteur", $categoryArts);
        $carleur = $this->createTopic("Carleur", $categoryBatiment);
        $peintre = $this->createTopic("Peintre", $categoryBatiment);
        $tapissier = $this->createTopic("Tapissier", $categoryBatiment);

        $manager->persist($regisseurSon);
        $manager->persist($regisseurLumiere);
        $manager->persist($acteur);
        $manager->persist($carleur);
        $manager->persist($peintre);
        $manager->persist($tapissier);

        $stanislavsky = $this->createCommunity('Les acteurs','Communauté sur les techniques de l\'acting. ', [$acteur]);
        $batiment = $this->createCommunity('Les bêtes du bâtiments','Astuces à propos des métiers du bâtiments', [$carleur, $peintre, $tapissier]);
        $manager->persist($stanislavsky);
        $manager->persist($batiment);

        $threadBooks = $this->createThread('Quels livres me conseillez vous ?','J\'aimerais travailler mon jeu d\'acteur, quels livres me conseillez vous ?', $stanislavsky, $userTenet);
        $threadBatiment = $this->createThread('Quels métiers est le moins fatiguant ?','Les métiers du batiment sont dure physiquement, lequel est le meilleur pour garder un corps en bonne santé ?', $batiment, $userAdmin);

        $manager->persist($threadBooks);
        $manager->persist($threadBatiment);

        $postBooks = $this->createPost('Pour commencer tu peux prendre la formation de l\'acteur et la construction de personnage de Stanislavsky', $threadBooks, $userAdmin);
        $postBatiment = $this->createPost('Je n\'ai pas touché à tous les métiers mais il me semble que carleur est pas mal, physique mais il faut utilisé les techniques de la formation PRAPS.', $threadBatiment, $userTenet);

        $manager->persist($postBooks);
        $manager->persist($postBatiment);

        $userCommunityTenet = new UserCommunity();
        $userCommunityTenet->setCommunity($stanislavsky);
        $userCommunityTenet->setIsFavorite(true);
        $userCommunityTenet->setUser($userTenet);

        $manager->persist($userCommunityTenet);

        $userCommunityBatiment = new UserCommunity();
        $userCommunityBatiment->setCommunity($batiment);
        $userCommunityBatiment->setIsFavorite(true);
        $userCommunityBatiment->setUser($userAdmin);

        $manager->persist($userCommunityBatiment);

        $question = $this->createMessage('Bonjour, on peut se rencontrer ?', $userTenet, $userAdmin);
        $response = $this->createMessage('Oui, bien sûr !', $userAdmin, $userTenet);

        $manager->persist($question);
        $manager->persist($response);

        $manager->flush();
    }

    public function createUser($email, $pseudo, $pwd, array $roles): User {
        $user = new User();
        $user->setEmail($email);
        $user->setPseudo($pseudo);
        $user->setRoles($roles);
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $pwd
        );
        $user->setPassword($hashedPassword);
        return $user;
    }

    public function createCategory($name): Category {
        $category = new Category();
        $category->setName($name);
        return $category;
    }

    public function createTopic($name, $category): Topic {
        $topic = new Topic();
        $topic->setCategory($category);
        $topic->setName($name);
        return $topic;
    }

    public function createCommunity($name, $description, array $topics): Community {
        $community = new Community();
        $community->setDescription($description);
        $community->setName($name);  
        foreach ($topics as $topic) {
            $topic->addCommunity($community);
            $community->addTopic($topic);
        }
        
        return $community;
    }

    public function createThread($title, $content, $community, $user): Thread {
        $thread = new Thread();
        $thread->setCommunity($community);
        $thread->setContent($content);
        $thread->setCreatedAt(new \DateTimeImmutable());
        $thread->setTitle($title);
        $thread->setUser($user);
        return $thread;
    }

    public function createPost($content, $thread, $user): Post {
        $post = new Post();
        $post->setContent($content);
        $post->setCreatedAt(new \DateTimeImmutable());
        $post->setThread($thread);
        $post->setUser($user);
        return $post;
    }

    public function createMessage(string $content, User $issuer, User $recipient): Message {
        $message = new Message();
        $message->setContent($content);
        $message->setCreatedAt(new \DateTimeImmutable());
        $message->setIssuer($issuer);
        $message->setRecipient($recipient);
        $message->setIsView(true);
        return $message;
    }
}
