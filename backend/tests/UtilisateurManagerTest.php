<?php

namespace App\Tests;

use App\Entity\Utilisateur;
use App\Service\LdapService;
use App\State\Utilisateur\UtilisateurManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UtilisateurManagerTest extends ApiTestCaseCustom
{
    public function testInitNumeroAnonyme(): void
    {
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $user = $em->getRepository(Utilisateur::class)->findOneBy(['uid' => 'beneficiaire']);
        
        /** @var UtilisateurManager $manager */
        $manager = $container->get(UtilisateurManager::class);
        
        $manager->initNumeroAnonyme($user);
        
        $this->assertNotNull($user->getNumeroAnonyme());
        $this->assertStringStartsWith(date('Y'), (string)$user->getNumeroAnonyme());
    }

    public function testParRole(): void
    {
        $container = static::getContainer();
        /** @var UtilisateurManager $manager */
        $manager = $container->get(UtilisateurManager::class);
        
        $admins = $manager->parRole('ROLE_ADMIN');
        $this->assertNotEmpty($admins);
        // Find 'admin' in the list
        $uids = array_map(fn($u) => $u->getUid(), $admins);
        $this->assertContains('admin', $uids);
    }

    public function testCreerBeneficiairePourDemande(): void
    {
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        
        // On crée une demande pour un type qui n'a qu'un profil (artiste, id 2)
        $typeDemande = $em->getRepository(\App\Entity\TypeDemande::class)->find(2);
        $campagne = $typeDemande->getCampagnes()->first();
        $demandeur = $em->getRepository(Utilisateur::class)->findOneBy(['uid' => 'demandeur2']);
        
        $demande = new \App\Entity\Demande();
        $demande->setCampagne($campagne);
        $demande->setDemandeur($demandeur);
        $demande->setEtat($em->getRepository(\App\Entity\EtatDemande::class)->find(\App\Entity\EtatDemande::RECEPTIONNEE));
        $demande->setDateDepot(new \DateTime());
        
        $em->persist($demande);
        $em->flush();
        
        /** @var UtilisateurManager $manager */
        $manager = $container->get(UtilisateurManager::class);
        
        $beneficiaire = $manager->creerBeneficiairePourDemande($demande, null, 'gestionnaire');
        
        $this->assertNotNull($beneficiaire);
        $this->assertEquals($demandeur, $beneficiaire->getUtilisateur());
        $this->assertEquals(6, $beneficiaire->getProfil()->getId()); // profil6 for artistes
    }

    public function testRecalculRoles(): void
    {
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        /** @var Utilisateur $user */
        $user = $em->getRepository(Utilisateur::class)->findOneBy(['uid' => 'enseignant']);
        $this->assertNotContains(Utilisateur::ROLE_REFERENT_COMPOSANTE, $user->getRoles());

        $composante = $em->getRepository(\App\Entity\Composante::class)->find(1);
        $composante->addReferent($user);

        /** @var UtilisateurManager $manager */
        $manager = $container->get(UtilisateurManager::class);
        $manager->recalculRoles($user);

        $this->assertContains(Utilisateur::ROLE_REFERENT_COMPOSANTE, $user->getRoles());

        $composante->removeReferent($user);
        $manager->recalculRoles($user);

        $this->assertNotContains(Utilisateur::ROLE_REFERENT_COMPOSANTE, $user->getRoles());
    }

    public function testInfosComplementaires(): void
    {
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $user = $em->getRepository(Utilisateur::class)->findOneBy(['uid' => 'demandeur']);

        /** @var UtilisateurManager $manager */
        $manager = $container->get(UtilisateurManager::class);

        $infos = $manager->infosComplementaires($user);
        $this->assertIsArray($infos);
        $this->assertArrayHasKey('someKey', $infos);
        $this->assertSame('someValue', $infos['someKey']);
    }

    public function testPrechargerInfosComplementaires(): void
    {
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $demandeur = $em->getRepository(Utilisateur::class)->findOneBy(['uid' => 'demandeur']);
        $demandeur2 = $em->getRepository(Utilisateur::class)->findOneBy(['uid' => 'demandeur2']);

        /** @var UtilisateurManager $manager */
        $manager = $container->get(UtilisateurManager::class);

        $manager->prechargerInfosComplementaires([$demandeur, $demandeur2]);

        $infos1 = $manager->infosComplementaires($demandeur);
        $infos2 = $manager->infosComplementaires($demandeur2);

        $this->assertSame(['someKey' => 'someValue'], $infos1);
        $this->assertSame(['someKey' => 'someValue'], $infos2);
    }
}
