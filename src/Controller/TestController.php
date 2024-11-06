<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Entity\Recipient;
use App\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TestController extends AbstractController
{
    #[Route('/', name: 'app_test')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $project = $entityManager
            ->getRepository(Project::class)
            ->find('01927bbc-c9f7-b286-bed2-422e6cdcda74');

// All three work
//    #uid: "01J9XJZSQTGSBCHHFVC94KKFME"
//    toBase58: "1CG6EnD9cLshSonupdprhb"
//    toRfc4122: "01927b2f-e6fa-8656-c8c5-fb624939be8e"

        dump($project);

        return $this->render('test/index.html.twig');
    }

    #[Route('/insert', name: 'app_test_insert')]
    public function insert(EntityManagerInterface $entityManager): Response
    {
        $contact = new Contact();
        $contact->setName('Mark')->setEmail('mark@example.com')->setPhone('01243 123456')->setPosition('PostgreSQL tester');

        $fundRecipient = new Recipient();
        $fundRecipient
            ->setName('Nottingham Tram')
            ->setLeadContact($contact);

        $project = new Project();
        $project
            ->setName('Name')
            ->setOwner($fundRecipient);

        $entityManager->persist($contact);
        $entityManager->persist($fundRecipient);
        $entityManager->persist($project);
        $entityManager->flush();

        return $this->render('test/index.html.twig');
    }
}
