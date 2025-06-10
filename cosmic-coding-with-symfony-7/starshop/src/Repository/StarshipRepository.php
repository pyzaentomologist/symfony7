<?php

namespace App\Repository;

use App\Model\Starship;
use App\Model\StarshipStatusEnum;
use Psr\Log\LoggerInterface;

class StarshipRepository
{
  public function __construct(private LoggerInterface $logger)
  {
  }
  public function findAll(): array
  {
    $this->logger->info('Starship collection retrieved');

    return [
      new Starship(
          1,
          'USS LeafyCruiser (NCC-0001)',
          'Garden',
          'Jean-Luc Pickles',
          StarshipStatusEnum::IN_PROGRESS
      ),
      new Starship(
          2,
          'USS VeggieVoyager (NCC-0002)',
          'Botanical',
          'James T. Tomato',
          StarshipStatusEnum::COMPLETED
      ),
      new Starship(
          3,
          'USS Cosmic Cruiser (NCC-0003)',
          'Interstellar',
          'Spock Lettuce',
          StarshipStatusEnum::WAITING
      ),
    ];
  }
  public function find(int $id): ?Starship
    {
        foreach ($this->findAll() as $starship) {
            if ($starship->getId() === $id) {
                return $starship;
            }
        }
        return null;
    }
}