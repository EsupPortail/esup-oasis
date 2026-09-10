<?php

namespace App\Tests\Entity;

use App\Entity\Adresse;
use PHPUnit\Framework\TestCase;

final class AdresseTest extends TestCase
{
    public function testFreshlyConstructedAdresseIsEmpty(): void
    {
        self::assertTrue((new Adresse())->isEmpty());
    }

    public function testIsEmptyReturnsFalseAsSoonAsAnyFieldIsSet(): void
    {
        $cases = [
            'ligne1' => fn(Adresse $a) => $a->setLigne1('1 rue Principale'),
            'ligne2' => fn(Adresse $a) => $a->setLigne2('Bat A'),
            'codePostal' => fn(Adresse $a) => $a->setCodePostal('33000'),
            'ville' => fn(Adresse $a) => $a->setVille('Bordeaux'),
            'pays' => fn(Adresse $a) => $a->setPays('FRANCE'),
        ];

        foreach ($cases as $field => $apply) {
            $a = new Adresse();
            $apply($a);
            self::assertFalse($a->isEmpty(), "isEmpty must be false when only {$field} is set");
        }
    }

    public function testSettersAreFluentAndPreserveValues(): void
    {
        $a = (new Adresse())
            ->setLigne1('1 rue de Bordeaux')
            ->setLigne2('Bat A esc 2')
            ->setCodePostal('33000')
            ->setVille('Bordeaux')
            ->setPays('FRANCE');

        self::assertSame('1 rue de Bordeaux', $a->getLigne1());
        self::assertSame('Bat A esc 2', $a->getLigne2());
        self::assertSame('33000', $a->getCodePostal());
        self::assertSame('Bordeaux', $a->getVille());
        self::assertSame('FRANCE', $a->getPays());
    }

    public function testSettersAcceptNullToReset(): void
    {
        $a = (new Adresse())->setVille('Bordeaux')->setVille(null);

        self::assertNull($a->getVille());
        self::assertTrue($a->isEmpty());
    }
}
