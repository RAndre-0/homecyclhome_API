<?php

namespace App\Tests\Service;

use App\Entity\Intervention;
use App\Entity\User;
use App\Service\InterventionBookingService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class InterventionBookingServiceTest extends TestCase
{
    public function testBookInterventionWithoutPhoto()
    {
        // Prepare data
        $intervention = new Intervention();
        $client = new User();
        $data = [
            'adresse' => '123 Test Street',
            'marqueVelo' => 'Giant',
            'modeleVelo' => 'Escape 3',
            'electrique' => true,
            'commentaire' => 'Please hurry'
        ];
        $uploadDir = '/tmp';

        // Appel au service
        $service = new InterventionBookingService();
        $service->bookIntervention($intervention, $client, $data, null, $uploadDir);

        // Assertion sur l'assignation correcte des valeurs
        $this->assertSame($client, $intervention->getClient());
        $this->assertSame('Giant', $intervention->getVeloMarque());
        $this->assertSame('Escape 3', $intervention->getVeloModele());
        $this->assertTrue($intervention->isVeloElectrique());
        $this->assertSame('Please hurry', $intervention->getCommentaireClient());
        $this->assertSame('123 Test Street', $intervention->getAdresse());
        $this->assertNull($intervention->getPhoto());
    }

    public function testBookInterventionThrowsExceptionIfMissingFields()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Données incomplètes.');

        $service = new InterventionBookingService();
        $intervention = new Intervention();
        $client = new User();

        $service->bookIntervention($intervention, $client, [], null, '/tmp');
    }

    public function testBookInterventionRejectsInvalidFileType()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Format de fichier non autorisé');

        $intervention = new Intervention();
        $client = new User();
        $data = [
            'adresse' => 'Test',
            'marqueVelo' => 'Trek',
            'modeleVelo' => 'FX',
            'electrique' => false,
        ];

        // Créé un mock d'un fichier avec un type MIME invalide
        $photo = $this->createMock(UploadedFile::class);
        $photo->method('getMimeType')->willReturn('application/pdf');
        $photo->method('getSize')->willReturn(100000);

        $service = new InterventionBookingService();
        $service->bookIntervention($intervention, $client, $data, $photo, '/tmp');
    }

    public function testBookInterventionRejectsTooLargeFile()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Fichier trop volumineux (max 5 Mo)');

        $intervention = new Intervention();
        $client = new User();
        $data = [
            'adresse' => 'Test',
            'marqueVelo' => 'Trek',
            'modeleVelo' => 'FX',
            'electrique' => false,
        ];

        // Créé un mock d'un fichier trop volumineux
        $photo = $this->createMock(UploadedFile::class);
        $photo->method('getMimeType')->willReturn('image/jpeg');
        $photo->method('getSize')->willReturn(6 * 1024 * 1024); // 6MB

        $service = new InterventionBookingService();
        $service->bookIntervention($intervention, $client, $data, $photo, '/tmp');
    }
}
