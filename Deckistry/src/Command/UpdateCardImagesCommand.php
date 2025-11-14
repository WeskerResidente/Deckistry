<?php

namespace App\Command;

use App\Repository\CardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-card-images',
    description: 'Met à jour les URLs d\'images manquantes pour les cartes existantes',
)]
class UpdateCardImagesCommand extends Command
{
    public function __construct(
        private CardRepository $cardRepository,
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Mise à jour des images des cartes');

        // Récupérer toutes les cartes sans image
        $cards = $this->cardRepository->findAll();
        $updated = 0;
        $failed = 0;

        $io->progressStart(count($cards));

        foreach ($cards as $card) {
            if (!$card->getImageUri() || !$card->getImageUriSmall()) {
                try {
                    // Récupérer les données depuis Scryfall
                    $scryfallUrl = "https://api.scryfall.com/cards/{$card->getScryfallId()}";
                    $context = stream_context_create([
                        'http' => [
                            'timeout' => 10,
                            'header' => "User-Agent: Deckistry/1.0\r\n"
                        ]
                    ]);
                    
                    $response = @file_get_contents($scryfallUrl, false, $context);
                    
                    if ($response !== false) {
                        $data = json_decode($response, true);
                        
                        if ($data && isset($data['id'])) {
                            // Mettre à jour les URLs d'images
                            if (isset($data['image_uris'])) {
                                $card->setImageUri($data['image_uris']['normal'] ?? $data['image_uris']['large'] ?? null);
                                $card->setImageUriSmall($data['image_uris']['small'] ?? $data['image_uris']['normal'] ?? null);
                                $updated++;
                            } elseif (isset($data['card_faces'][0]['image_uris'])) {
                                $card->setImageUri($data['card_faces'][0]['image_uris']['normal'] ?? $data['card_faces'][0]['image_uris']['large'] ?? null);
                                $card->setImageUriSmall($data['card_faces'][0]['image_uris']['small'] ?? $data['card_faces'][0]['image_uris']['normal'] ?? null);
                                $updated++;
                            }
                        }
                    }
                    
                    // Respecter les limites de l'API Scryfall (100ms entre les requêtes)
                    usleep(100000);
                    
                } catch (\Exception $e) {
                    $io->error("Erreur pour la carte {$card->getName()}: " . $e->getMessage());
                    $failed++;
                }
            }
            
            $io->progressAdvance();
        }

        $io->progressFinish();

        // Sauvegarder toutes les modifications
        $this->em->flush();

        $io->success([
            "Cartes mises à jour: {$updated}",
            "Échecs: {$failed}",
            "Total traité: " . count($cards)
        ]);

        return Command::SUCCESS;
    }
}
