<?php

namespace App\Service;

use App\Entity\ValeurParametre;
use App\Repository\ParametreRepository;

readonly class ParametreService
{
    public function __construct(
        private ParametreRepository $parametreRepository,
        private array $appEnv,
    ) {}

    public function valeur(string $cle, bool $multiple = false): string|array|null
    {
        //on cherche d'abord dans l'env, puis en base
        if (array_key_exists($cle, $this->appEnv)) {
            return $this->appEnv[$cle];
        }

        $param = $this->parametreRepository->findOneBy([
            'cle' => $cle,
        ]);

        if ($multiple) {
            return array_map(
                fn(ValeurParametre $dest) => $dest->getValeur(),
                $param->getValeurCourante(multiple: true),
            );
        }

        return $param?->getValeurCourante()?->getValeur();
    }

    public function getAppEnv(): array
    {
        return $this->appEnv;
    }
}
