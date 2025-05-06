<?php

namespace App\State\TauxHoraire;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\TauxHoraire;
use App\State\TransformerService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class TauxHoraireCollectionProvider implements ProviderInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.collection_provider')] private ProviderInterface $collectionProvider,
        private TransformerService                                                                            $transformerService
    )
    {

    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $data = $this->collectionProvider->provide($operation, $uriVariables, $context);

        return array_map(fn($taux) => $this->transformerService->transform($taux, TauxHoraire::class), iterator_to_array($data));
    }
}