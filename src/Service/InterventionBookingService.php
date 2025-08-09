<?php

namespace App\Service;

use App\Entity\Intervention;
use App\Entity\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class InterventionBookingService
{
    /**
     * Book an intervention for a client.
     *
     * @param Intervention $intervention
     * @param User $client
     * @param array $data
     * @param UploadedFile|null $photo
     * @param string $uploadDir Directory to upload the photo
     *
     * @throws \DomainException if required data is missing or invalid
     */
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
            $mime = $photo->getMimeType() ?? '';

            if (!in_array($mime, $allowedMimeTypes, true)) {
                throw new \DomainException('Format de fichier non autorisé.');
            }

            if ($photo->getSize() > 5 * 1024 * 1024) {
                throw new \DomainException("Fichier trop volumineux (max 5 Mo)");
            }

            // Récupération de l'ancienne photo
            $ancienne = $intervention->getPhoto();

            // Génération d'un nom de fichier unique
            $fileExtension = $photo->guessExtension() ?: 'bin';
            $fileName = bin2hex(random_bytes(16)) . '.' . $fileExtension;

            // Déplacement du nouveau fichier
            try {
                $photo->move($uploadDir, $fileName);
            } catch (\Throwable $e) {
                throw new \RuntimeException('Échec de l’upload du fichier.');
            }
            
            $intervention->setPhoto($fileName);

            // Suppression de l'ancienne photo si elle existe
            if ($ancienne) {
                $ancienChemin = $uploadDir . '/' . $ancienne;
                if (file_exists($ancienChemin)) {
                    @unlink($ancienChemin);
                }
            }
        }
    }
}
