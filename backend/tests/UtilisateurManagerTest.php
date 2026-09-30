<?php

namespace App\Tests;

use App\Entity\Utilisateur;
use App\Service\LdapService;
use App\Service\SiScol\FakeSiScolDataProvider;
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

    public function testMajInscriptionsProjetteSituationSociale(): void
    {
        // La projection situation sociale est alimentée par le
        // SiScolDataProvider. En env de test, AbstractSiScolDataProvider est aliasé
        // sur FakeSiScolDataProvider qui renvoie codeSituationSociale "NO"/"Normal"
        // et boursier=false. On valide que la projection recopie ces champs.
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        // 'demandeur' porte un numeroEtudiant (123456), donc la MAJ scol se déclenche.
        $user = $em->getRepository(Utilisateur::class)->findOneBy(['uid' => 'demandeur']);

        /** @var UtilisateurManager $manager */
        $manager = $container->get(UtilisateurManager::class);

        $debut = new \DateTime('2024-09-01');
        $fin = new \DateTime('2025-08-31');
        $manager->majInscriptionsEtIdentite($user, $debut, $fin);

        $this->assertSame('NO', $user->getCodeSituationSociale());
        $this->assertSame('Normal', $user->getLibelleSituationSociale());
        $this->assertFalse($user->isBoursier(), 'Le code NO ne doit pas dériver boursier');
    }

    public function testMajInscriptionsCodeBoNeDerivePasBoursier(): void
    {
        // La situation sociale est exposée telle quelle, mais n'alimente pas le témoin
        // boursier : le SI scolarité porte son propre indicateur, qui couvre déjà les
        // différentes bourses et peut légitimement contredire le code social (bourse
        // retirée, dossier non validé). On exerce le vrai UtilisateurManager.
        FakeSiScolDataProvider::$boursier = false;
        FakeSiScolDataProvider::$codeSituationSociale = 'BO';
        FakeSiScolDataProvider::$libelleSituationSociale = 'Boursier';

        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $user = $em->getRepository(Utilisateur::class)->findOneBy(['uid' => 'demandeur']);

        /** @var UtilisateurManager $manager */
        $manager = $container->get(UtilisateurManager::class);
        $manager->majInscriptionsEtIdentite($user, new \DateTime('2024-09-01'), new \DateTime('2025-08-31'));

        $this->assertSame('BO', $user->getCodeSituationSociale());
        $this->assertSame('Boursier', $user->getLibelleSituationSociale());
        $this->assertFalse($user->isBoursier(), "Le code social ne doit pas contredire l'indicateur du SI");
    }

    public function testMajInscriptionsTemoinLegacyResteBoursier(): void
    {
        // Rétrocompat : le témoin boursier legacy d'Apogée reste honoré même quand
        // le code situation sociale n'est pas "BO".
        FakeSiScolDataProvider::$boursier = true;
        FakeSiScolDataProvider::$codeSituationSociale = 'NO';
        FakeSiScolDataProvider::$libelleSituationSociale = 'Normal';

        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $user = $em->getRepository(Utilisateur::class)->findOneBy(['uid' => 'demandeur']);

        /** @var UtilisateurManager $manager */
        $manager = $container->get(UtilisateurManager::class);
        $manager->majInscriptionsEtIdentite($user, new \DateTime('2024-09-01'), new \DateTime('2025-08-31'));

        $this->assertTrue($user->isBoursier(), 'Le témoin legacy boursier doit rester honoré');
    }

    public function testMajInscriptionsProjetteAdresse(): void
    {
        // L'adresse Apogée de la dernière inscription est projetée vers
        // Utilisateur::adresse. Ligne2 (AD2) et complément (AD3) sont concaténés
        // sur ligne2 car notre modèle ne porte que deux lignes.
        FakeSiScolDataProvider::$adresseLigne1 = '12 rue des Lilas';
        FakeSiScolDataProvider::$adresseLigne2 = 'Bâtiment B';
        FakeSiScolDataProvider::$adresseComplement = 'Appartement 42';
        FakeSiScolDataProvider::$adresseCodePostal = '33000';
        FakeSiScolDataProvider::$adresseVille = 'Bordeaux';
        FakeSiScolDataProvider::$adressePays = 'FRANCE';

        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $user = $em->getRepository(Utilisateur::class)->findOneBy(['uid' => 'demandeur']);

        /** @var UtilisateurManager $manager */
        $manager = $container->get(UtilisateurManager::class);
        $manager->majInscriptionsEtIdentite($user, new \DateTime('2024-09-01'), new \DateTime('2025-08-31'));

        $adresse = $user->getAdresse();
        $this->assertSame('12 rue des Lilas', $adresse->getLigne1());
        $this->assertSame('Bâtiment B Appartement 42', $adresse->getLigne2());
        $this->assertSame('33000', $adresse->getCodePostal());
        $this->assertSame('Bordeaux', $adresse->getVille());
        $this->assertSame('FRANCE', $adresse->getPays());
    }

    protected function tearDown(): void
    {
        // Réinitialise le mock situation sociale pour ne pas polluer les autres tests.
        FakeSiScolDataProvider::$boursier = false;
        FakeSiScolDataProvider::$codeSituationSociale = 'NO';
        FakeSiScolDataProvider::$libelleSituationSociale = 'Normal';
        // Idem pour l'adresse simulée.
        FakeSiScolDataProvider::$adresseLigne1 = null;
        FakeSiScolDataProvider::$adresseLigne2 = null;
        FakeSiScolDataProvider::$adresseComplement = null;
        FakeSiScolDataProvider::$adresseCodePostal = null;
        FakeSiScolDataProvider::$adresseVille = null;
        FakeSiScolDataProvider::$adressePays = null;
        // Idem pour le mock des données d'étape.
        parent::tearDown();
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
}
