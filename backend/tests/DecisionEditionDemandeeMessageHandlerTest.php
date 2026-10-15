<?php

namespace App\Tests;

use App\Entity\DecisionAmenagementExamens;
use App\Message\DecisionEditionDemandeeMessage;
use App\MessageHandler\DecisionEditionDemandeeMessageHandler;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DecisionEditionDemandeeMessageHandlerTest extends KernelTestCase
{
    public function testInvoke(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $handler = $container->get(DecisionEditionDemandeeMessageHandler::class);
        $em = $container->get('doctrine')->getManager();

        // On cherche une décision existante pour le test
        $decision = $em->getRepository(DecisionAmenagementExamens::class)->findOneBy([]);
        
        if (null === $decision) {
            $this->markTestSkipped('No decision found in DB');
        }

        $message = new DecisionEditionDemandeeMessage($decision->getId(), 'admin');

        // On appelle le handler
        // Attention: cela va envoyer un mail et générer un PDF si les services ne sont pas mockés.
        // En environnement de test, le mailer est normalement mocké par Symfony.
        $handler->__invoke($message);

        $this->assertEquals(DecisionAmenagementExamens::ETAT_EDITE, $decision->getEtat());
    }
}
