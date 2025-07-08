<?php

namespace App\Service;

use App\Entity\Intervention;
use App\Entity\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class InterventionBookingService
{
    public function bookIntervention(
        Intervention $intervention,
        User $client,
        array $data,
        ?UploadedFile $photo,
        string $uploadDir
    ): void {
        // Champs obligatoires
        $required = ['adresse', 'marqueVelo', 'modeleVelo'];
        foreach ($required as $key) {
            if (empty($data[$key])) {
                throw new \DomainException("Données incomplètes.");
            }
        }

        $intervention
            ->setClient($client)
            ->setVeloMarque($data['marqueVelo'])
            ->setVeloModele($data['modeleVelo'])
            ->setVeloElectrique((bool)$data['electrique'])
            ->setCommentaireClient($data['commentaire'] ?? null)
            ->setAdresse($data['adresse']);

        if ($photo instanceof UploadedFile) {
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];

            if (!in_array($photo->getMimeType(), $allowedMimeTypes)) {
                throw new \DomainException("Format de fichier non autorisé");
            }

            if ($photo->getSize() > 5 * 1024 * 1024) {
                throw new \DomainException("Fichier trop volumineux (max 5 Mo)");
            }

            // Suppression de l'ancienne photo si elle existe
            $ancienne = $intervention->getPhoto();
            $filename = uniqid('velo_') . '.' . $photo->guessExtension();
            $photo->move($uploadDir, $filename);
            $intervention->setPhoto($filename);

            if ($ancienne) {
                $ancienChemin = $uploadDir . '/' . $ancienne;
                if (file_exists($ancienChemin)) {
                    @unlink($ancienChemin);
                }
            }
        }
    }
}
