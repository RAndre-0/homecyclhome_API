<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Intervention;
use App\Entity\TypeIntervention;
use App\Repository\TypeInterventionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\InterventionRepository;
use Symfony\Contracts\Cache\ItemInterface;
use App\Repository\ModelePlanningRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\MimeTypeGuesserInterface;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Bundle\SecurityBundle\Security;
use App\Service\InterventionBookingService;
use App\Service\InterventionBatchGenerator;


class InterventionController extends AbstractController
{
    /* Renvoie tous les interventions */
    #[Route('/api/interventions', name: 'get_interventions', methods: ["GET"])]
    public function get_interventions(InterventionRepository $interventionRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        try {
            $idCache = "interventions_cache";
            $cache->invalidateTags(["interventions_cache"]);
            $listeInterventions = $cache->get($idCache, function (ItemInterface $item) use ($interventionRepository, $serializer) {
                $item->tag("interventions_cache");
                $listeInterventions = $interventionRepository->findAll();
                return $serializer->serialize($listeInterventions, "json", ["groups" => "get_interventions"]);
            });
            return new JsonResponse($listeInterventions, Response::HTTP_OK, [], true);
        } catch (\Exception $e) {
            return new JsonResponse(["error" => "Une erreur s'est produite"], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /* Renvoie les interventions d'un technicien */
    #[Route('/api/interventions/technicien/{id}', name: 'get_interventions_technicien', methods: ["GET"])]
    public function get_interventions_by_technicien(
        int $id,
        Request $request,
        InterventionRepository $interventionRepository,
        SerializerInterface $serializer
    ): JsonResponse 
    {
        // Récupération du paramètre reservedOnly avec validation, false par défaut
        $reservedOnly = filter_var($request->query->get('reservedOnly', false), FILTER_VALIDATE_BOOLEAN);

        $interventions = $interventionRepository->findByTechnicienWithFilter($id, $reservedOnly);

        $interventionsJson = $serializer->serialize($interventions, 'json', ['groups' => 'get_interventions']);
        return new JsonResponse($interventionsJson, Response::HTTP_OK, [], true);
    }

    /* Renvoie les interventions d'un client */
    #[Route('/api/interventions/client/{id}', name: 'get_interventions_client', methods: ["GET"])]
    public function get_interventions_by_client(
        int $id,
        InterventionRepository $interventionRepository,
        SerializerInterface $serializer
    ): JsonResponse 
    {
        $interventions = $interventionRepository->findBy(['client' => $id]);

        if (!$interventions) {
            return new JsonResponse([], Response::HTTP_NOT_FOUND);
        }

        $interventionsJson = $serializer->serialize($interventions, 'json', ['groups' => 'get_interventions']);
        return new JsonResponse($interventionsJson, Response::HTTP_OK, [], true);
    }

    /* Renvoie les interventions d'un client authentifié */
    #[Route('/api/interventions/client', name: 'get_interventions_authenticated_client', methods: ["GET"])]
    public function get_interventions_authenticated_client(
        Security $security,
        InterventionRepository $interventionRepository,
        SerializerInterface $serializer
    ): JsonResponse {
        $user = $security->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $interventions = $interventionRepository->findBy(['client' => $user->getId()]);
        $interventionsJson = $serializer->serialize($interventions, 'json', ['groups' => 'get_interventions']);

        return new JsonResponse($interventionsJson, Response::HTTP_OK, [], true);
    }


    /* Renvoie le nombre d'interventions par type et par mois pour les 12 derniers mois */
    #[Route('/api/interventions/stats', name: 'get_interventions_stats', methods: ['GET'])]
    public function interventionsStats(InterventionRepository $interventionRepository): JsonResponse
    {
        try {
            // Récupére les statistiques des interventions
            $data = $interventionRepository->interventionsByTypeLastTwelveMonths();
            if (empty($data)) {
                return new JsonResponse(["message" => "Aucune donnée trouvée"], JsonResponse::HTTP_NO_CONTENT);
            }

            // Retourner les données sous forme de JSON
            return $this->json($data);
        } catch (\Exception $e) {
            // Envoi d'une réponse JSON avec erreur
            return new JsonResponse(["error" => "Une erreur s'est produite lors de la récupération des statistiques."], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /* Renvoie les prochaines interventions */
    #[Route('/api/interventions/next-interventions', name: 'get_next_interventions', methods: ["GET"])]
    public function get_next_interventions(InterventionRepository $interventionRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        try {
            // Récupére les prochaines interventions
            $data = $interventionRepository->getNextInterventions(10);
            if (empty($data)) {
                return new JsonResponse(["message" => "Aucune donnée trouvée"], JsonResponse::HTTP_NO_CONTENT);
            }
            return $this->json($data);
        } catch (\Exception $e) {
            return new JsonResponse(["error" => "Une erreur s'est produite lors de la récupération des prochaines interventions."], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /* Retourne une intervention */
    #[Route('/api/interventions/{id}', name: 'get_intervention', methods: ["GET"])]
    public function get_intervention(Intervention $intervention, SerializerInterface $serializer): JsonResponse
    {
        $interventionJson = $serializer->serialize($intervention, "json", ["groups" => "get_intervention"]);
        return new JsonResponse($interventionJson, Response::HTTP_OK, [], true);
    }

    /* Créé une nouvelle intervention */
    #[Route("/api/interventions", name: "create_intervention", methods: ["POST"])]
    public function new_intervention(
        Request $request,
        EntityManagerInterface $em,
        SerializerInterface $serializer,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator,
        TagAwareCacheInterface $cache
    ): JsonResponse 
    {
        // Désérialisation partielle pour obtenir les données brutes
        $data = json_decode($request->getContent(), true);

        // Récupérer les entités associées
        $typeIntervention = $em->getRepository(TypeIntervention::class)->find(intval($data["type_intervention"]));
        $technicien = $em->getRepository(User::class)->find(intval($data["technicien"]));

        if (!$typeIntervention || !$technicien) {
            return new JsonResponse($serializer->serialize("Type d'intervention ou technicien non trouvé.", "json"), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        unset($data["type_intervention"], $data["technicien"]);

        // Désérialisation des données JSON en un objet intervention
        $intervention = $serializer->deserialize(json_encode($data), Intervention::class, "json");

        // Associer le technicien et le type d'intervention
        $intervention->setTypeIntervention($typeIntervention);
        $intervention->setTechnicien($technicien);

        // Validation des données
        $errors = $validator->validate($intervention);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, "json"), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $em->persist($intervention);
        $em->flush();

        // Suppression du cache lié aux interventions
        $cache->invalidateTags(["interventions_cache"]);

        // Génération de l'URL de la ressource créée
        $location = $urlGenerator->generate("get_intervention", ["id" => $intervention->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        // Sérialisation de l'intervention créée
        $interventionsJson = $serializer->serialize($intervention, "json", [
            AbstractNormalizer::IGNORED_ATTRIBUTES => [
                "typeIntervention",
                "technicien"
            ]
        ]);

        return new JsonResponse($interventionsJson, JsonResponse::HTTP_CREATED, ["location" => $location], true);
    }

    /* Modifie une intervention */
    #[Route("/api/interventions/{id}/edit", name: "update_intervention", methods: ["PUT", "PATCH"])]
    public function edit_intervention(Request $request, Intervention $intervention, EntityManagerInterface $em, TagAwareCacheInterface $cache, SerializerInterface $serializer): JsonResponse
    {
        $interventionModifiee = $serializer->deserialize($request->getContent(), Intervention::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $intervention]);
        $em->persist($interventionModifiee);
        $em->flush();
        $cache->invalidateTags(["interventions_cache"]);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /* Supprime les interventions dans une plage de dates pour les techniciens listés */
    #[Route('/api/interventions/delete', name: 'delete_interventions', methods: ["DELETE"])]
    #[IsGranted("ROLE_ADMIN", message: "Droits insuffisants.")]
    public function deleteInterventions(
        Request $request,
        UserRepository $userRepository,
        InterventionRepository $interventionRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse 
    {
        // Récupérer les données de la requête
        $data = json_decode($request->getContent(), true);

        // Valider les techniciens
        if (!isset($data['technicians']) || !is_array($data['technicians'])) {
            return new JsonResponse(['error' => 'Les techniciens doivent être spécifiés sous forme de tableau d\'identifiants.'], 400);
        }

        $technicians = [];
        foreach ($data['technicians'] as $technicianId) {
            $technician = $userRepository->find($technicianId);
            if (!$technician) {
                return new JsonResponse(['error' => "Technicien introuvable avec l'identifiant $technicianId."], 404);
            }
            $technicians[] = $technician;
        }

        // Valider les dates "from" et "to"
        if (!isset($data['from'], $data['to'])) {
            return new JsonResponse(['error' => 'Les paramètres "from" et "to" doivent être spécifiés.'], 400);
        }

        $from = \DateTime::createFromFormat('Y-m-d', $data['from']);
        $to = \DateTime::createFromFormat('Y-m-d', $data['to']);

        if (!$from || !$to || $from > $to) {
            return new JsonResponse(['error' => 'Les dates "from" et "to" doivent être valides et "from" ne peut pas être après "to".'], 400);
        }

        // Supprimer les interventions pour chaque technicien dans la plage de dates
        foreach ($technicians as $technician) {
            $interventions = $interventionRepository->findNonReservedInterventionsByTechnicianAndDateRange(
                $technician,
                $from,
                $to
            );

            foreach ($interventions as $intervention) {
                $entityManager->remove($intervention);
            }
        }

        // Sauvegarde des modifications
        $entityManager->flush();

        return new JsonResponse(['success' => 'Interventions supprimées avec succès.'], 200);
    }


    /* Supprime une intervention */
    #[Route('/api/interventions/{id}', name: 'delete_intervention', methods: ["DELETE"])]
    #[IsGranted("ROLE_ADMIN", message: "Droits insuffisants.")]
    public function delete_user(Intervention $intervention, EntityManagerInterface $em, TagAwareCacheInterface $cache): JsonResponse
    {
        $produits = $intervention->getInterventionProduit();
        foreach ($produits as $produit) {
            $em->remove($produit);
        }
        $em->remove($intervention);
        $em->flush();
        $cache->invalidateTags(["interventions_cache"]);
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /* Créé des interventions à partir d'un modèle */
    #[Route('/api/new-interventions', name: 'create_interventions_from_modele', methods: ["POST"])]
    #[IsGranted("ROLE_ADMIN", message: "Droits insuffisants.")]
    public function createInterventionsFromModele(
        Request $request,
        ModelePlanningRepository $modelePlanningRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        InterventionBatchGenerator $generator
    ): JsonResponse 
    {
        $data = json_decode($request->getContent(), true);

        // Récupération du modèle de planning à partir de son identifiant
        $idModele = $data["model"] ?? null;
        $modelePlanning = $modelePlanningRepository->find((int)$idModele);
        if (!$modelePlanning) {
            return new JsonResponse(['error' => 'Modèle de planning introuvable.'], 404);
        }

        // Validation et récupération des techniciens à partir de leurs identifiants
        if (!isset($data['technicians']) || !is_array($data['technicians'])) {
            return new JsonResponse(['error' => 'Les techniciens doivent être spécifiés sous forme de tableau d\'identifiants.'], 400);
        }

        $technicians = [];
        foreach ($data['technicians'] as $technicianId) {
            $technician = $userRepository->find($technicianId);
            if (!$technician) {
                return new JsonResponse(['error' => "Technicien introuvable avec l'identifiant $technicianId."], 404);
            }
            $technicians[] = $technician;
        }

        // Validation et parsing des dates de début et de fin de la période à générer
        if (!isset($data['from'], $data['to'])) {
            return new JsonResponse(['error' => 'Les paramètres "from" et "to" doivent être spécifiés.'], 400);
        }

        $from = \DateTime::createFromFormat('Y-m-d', $data['from']);
        $to = \DateTime::createFromFormat('Y-m-d', $data['to']);

        if (!$from || !$to || $from > $to) {
            return new JsonResponse(['error' => 'Les dates "from" et "to" doivent être valides et "from" ne peut pas être après "to".'], 400);
        }

        // Génération d’un intervalle de dates (quotidien) sur la période donnée
        $interval = new \DateInterval('P1D');
        $dateRange = new \DatePeriod($from, $interval, (clone $to)->modify('+1 day'));

        // Appel au service pour générer la liste des interventions selon le modèle, les techniciens et les dates
        $interventions = $generator->generateInterventions($modelePlanning, $technicians, $dateRange);

        // Persistance en base des interventions générées
        foreach ($interventions as $intervention) {
            $entityManager->persist($intervention);
        }

        $entityManager->flush();

        // Retour d’une réponse JSON indiquant le succès de l’opération
        return new JsonResponse(['success' => 'Interventions créées avec succès.'], 201);
    }


    #[Route('/api/interventions/available/{technicienId}', name: 'get_available_slots', methods: ['GET'])]
    public function getAvailableSlots(
        int $technicienId,
        Request $request,
        InterventionRepository $repo,
        TypeInterventionRepository $typeRepo,
        SerializerInterface $serializer
    ): JsonResponse {
        $tomorrow = (new \DateTimeImmutable())->modify('+1 day')->setTime(0, 0);
        $typeId = $request->query->get('typeId');

        $qb = $repo->createQueryBuilder('i')
            ->where('i.technicien = :techId')
            ->andWhere('i.client IS NULL')
            ->andWhere('i.debut >= :tomorrow')
            ->setParameter('techId', $technicienId)
            ->setParameter('tomorrow', $tomorrow)
            ->orderBy('i.debut', 'ASC');

        if ($typeId) {
            $type = $typeRepo->find($typeId);
            if (!$type) {
                return new JsonResponse(['error' => 'Type d\'intervention introuvable.'], JsonResponse::HTTP_BAD_REQUEST);
            }

            $qb->andWhere('i.typeIntervention = :type')
            ->setParameter('type', $type);
        }

        $slots = $qb->getQuery()->getResult();

        return new JsonResponse(
            $serializer->serialize($slots, 'json', ['groups' => 'get_intervention']),
            JsonResponse::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/api/interventions/{id}/book', name: 'book_intervention', methods: ['POST'])]
    public function bookIntervention(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepo,
        InterventionBookingService $bookingService
    ): JsonResponse {
        $intervention = $em->getRepository(Intervention::class)->find($id);
        if (!$intervention) {
            return new JsonResponse(['error' => 'Intervention introuvable'], 404);
        }

        $clientId = $request->request->get('clientId');
        $client = $userRepo->find($clientId);
        if (!$client) {
            return new JsonResponse(['error' => 'Client introuvable'], 404);
        }

        $uploadDir = $this->getParameter('upload_directory');
        $data = $request->request->all();
        $photo = $request->files->get('photo');

        try {
            $bookingService->bookIntervention($intervention, $client, $data, $photo, $uploadDir);
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            // Erreur non gérée par le service (erreur système, etc.)
            return new JsonResponse(['error' => "Une erreur s'est produite."], 500);
        }

        $em->flush();

        return new JsonResponse(['message' => 'Intervention réservée'], 200);
    }
}
