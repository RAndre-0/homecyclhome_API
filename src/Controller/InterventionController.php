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
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

class InterventionController extends AbstractController
{
    /* Renvoie tous les interventions */
    #[Route('/api/interventions', name: 'get_interventions', methods: ["GET"])]
    #[OA\Get(
        summary: "Lister toutes les interventions",
        tags: ["Interventions"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des interventions",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        ref: new Model(type: \App\Entity\Intervention::class, groups: ['get_interventions'])
                    )
                )
            ),
            new OA\Response(response: 500, description: "Erreur serveur")
        ]
    )]
    public function getInterventions(InterventionRepository $interventionRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
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
    #[OA\Get(
        summary: "Lister les interventions d’un technicien (par ID)",
        tags: ["Interventions"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "reservedOnly", in: "query", required: false, schema: new OA\Schema(type: "boolean"), example: false),
            new OA\Parameter(name: "date", in: "query", required: false, description: "YYYY-MM-DD", schema: new OA\Schema(type: "string", format: "date"))
        ],
        responses: [
            new OA\Response(
            response: 200,
            description: "OK",
            content: new OA\JsonContent(
                type: "array",
                items: new OA\Items(ref: new Model(type: \App\Entity\Intervention::class, groups: ["get_interventions"]))
            )
            ),
            new OA\Response(response: 400, description: "Paramètres invalides")
        ]
    )]
    public function getInterventionsByTechnicien(
        int $id,
        Request $request,
        InterventionRepository $interventionRepository,
        SerializerInterface $serializer
    ): JsonResponse 
    {
        // Récupérer les interventions réservées uniquement ou non
        $reservedOnly = filter_var($request->query->get('reservedOnly', false), FILTER_VALIDATE_BOOLEAN);

        // Récupérer les interventions d'un jour spécifique si fourni
        $dateParam = $request->query->get('date');
        $date = null;
        if ($dateParam) {
            $date = \DateTime::createFromFormat('Y-m-d', $dateParam);
            if (!$date) {
                return new JsonResponse(['error' => 'Format de date invalide. Utilisez YYYY-MM-DD.'], JsonResponse::HTTP_BAD_REQUEST);
            }
        }

        $interventions = $interventionRepository->findByTechnicienWithFilter($id, $reservedOnly, $date);

        $interventionsJson = $serializer->serialize($interventions, 'json', ['groups' => 'get_interventions']);
        return new JsonResponse($interventionsJson, Response::HTTP_OK, [], true);
    }

    // Renvoie les interventions d'un technicien authentifié
    #[Route('/api/interventions/technicien', name: 'get_my_interventions', methods: ['GET'])]
    #[IsGranted("ROLE_TECHNICIEN")]
    #[OA\Get(
        summary: "Lister MES interventions (technicien connecté)",
        tags: ["Interventions"],
        security: [["Bearer" => []]],
        parameters: [
            new OA\Parameter(name: "date", in: "query", required: false, description: "YYYY-MM-DD", schema: new OA\Schema(type: "string", format: "date"))
        ],
        responses: [
            new OA\Response(response: 200, description: "OK",
            content: new OA\JsonContent(type: "array", items: new OA\Items(ref: new Model(type: \App\Entity\Intervention::class, groups: ["get_interventions"])))
            ),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 400, description: "Paramètres invalides")
        ]
    )]
    public function getMyInterventions(
        Request $request,
        InterventionRepository $interventionRepository,
        SerializerInterface $serializer
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non authentifié'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $dateParam = $request->query->get('date');
        $date = $dateParam ? \DateTime::createFromFormat('Y-m-d', $dateParam) : null;

        if ($dateParam && !$date) {
            return new JsonResponse(['error' => 'Format de date invalide (attendu : Y-m-d)'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $interventions = $interventionRepository->findByTechnicienWithFilter($user->getId(), true, $date);

        $json = $serializer->serialize($interventions, 'json', ['groups' => 'get_interventions']);
        return new JsonResponse($json, JsonResponse::HTTP_OK, [], true);
    }

    /* Renvoie les interventions d'un client */
    #[Route('/api/interventions/client/{id}', name: 'get_interventions_client', methods: ["GET"])]
    #[OA\Get(
        summary: "Lister les interventions d’un client (par ID)",
        tags: ["Interventions"],
        parameters: [ new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")) ],
        responses: [
            new OA\Response(response: 200, description: "OK",
            content: new OA\JsonContent(type: "array", items: new OA\Items(ref: new Model(type: \App\Entity\Intervention::class, groups: ["get_interventions"]))))
        ]
    )]
    public function getInterventionsByClient(
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
    #[OA\Get(
        summary: "Lister MES interventions (client connecté)",
        tags: ["Interventions"],
        security: [["Bearer" => []]],
        responses: [
            new OA\Response(response: 200, description: "OK",
            content: new OA\JsonContent(type: "array", items: new OA\Items(ref: new Model(type: \App\Entity\Intervention::class, groups: ["get_interventions"])))
            ),
            new OA\Response(response: 401, description: "Non authentifié")
        ]
    )]
    public function getInterventionsAuthenticatedClient(
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
    #[IsGranted("ROLE_ADMIN", message: "Droits insuffisants.")]
    #[OA\Get(
        summary: "Statistiques interventions (12 derniers mois, par type)",
        tags: ["Interventions"],
        security: [["Bearer" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "typeId", type: "integer", example: 3),
                            new OA\Property(property: "typeLibelle", type: "string", example: "Révision"),
                            new OA\Property(property: "month", type: "string", example: "2025-07", description: "AAAA-MM"),
                            new OA\Property(property: "count", type: "integer", example: 12)
                        ]
                    )
                )
            ),
            new OA\Response(response: 403, description: "Interdit")
        ]
    )]
    public function interventionsStats(InterventionRepository $interventionRepository): JsonResponse
    {
        try {
            // Récupère les statistiques des interventions
            $data = $interventionRepository->interventionsByTypeLastTwelveMonths();
            // Retourner les données sous forme de JSON
            return $this->json($data);
        } catch (\Exception $e) {
            // Envoi d'une réponse JSON avec erreur
            return new JsonResponse(["error" => "Une erreur s'est produite lors de la récupération des statistiques."], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /* Renvoie les prochaines interventions */
    #[Route('/api/interventions/next-interventions', name: 'get_next_interventions', methods: ["GET"])]
    #[OA\Get(
        summary: "Prochaines interventions (limite interne, ex: 10)",
        tags: ["Interventions"],
        responses: [
            new OA\Response(response: 200, description: "OK",
            content: new OA\JsonContent(type: "array", items: new OA\Items(ref: new Model(type: \App\Entity\Intervention::class, groups: ["get_interventions"]))))
        ]
    )]
    public function getNextInterventions(InterventionRepository $interventionRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        try {
            // Récupére les prochaines interventions
            $data = $interventionRepository->getNextInterventions(10);
            return $this->json($data);
        } catch (\Exception $e) {
            return new JsonResponse(["error" => "Une erreur s'est produite lors de la récupération des prochaines interventions."], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /* Retourne une intervention */
    #[Route('/api/interventions/{id}', name: 'get_intervention', methods: ["GET"])]
        #[OA\Get(
        summary: "Détail d’une intervention",
        tags: ["Interventions"],
        parameters: [ new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")) ],
        responses: [
            new OA\Response(response: 200, description: "OK", content: new OA\JsonContent(ref: new Model(type: \App\Entity\Intervention::class, groups: ["get_intervention"]))),
            new OA\Response(response: 404, description: "Introuvable")
        ]
    )]
    public function getIntervention(Intervention $intervention, SerializerInterface $serializer): JsonResponse
    {
        $interventionJson = $serializer->serialize($intervention, "json", ["groups" => "get_intervention"]);
        return new JsonResponse($interventionJson, Response::HTTP_OK, [], true);
    }

    /* Créé une nouvelle intervention */
    #[Route("/api/interventions", name: "create_intervention", methods: ["POST"])]
    #[OA\Post(
        summary: "Créer une intervention",
        tags: ["Interventions"],
        security: [["Bearer" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                required: ["type_intervention","technicien","debut","fin"],
                properties: [
                    new OA\Property(property: "type_intervention", type: "integer", example: 3, description: "ID du TypeIntervention"),
                    new OA\Property(property: "technicien", type: "integer", example: 42, description: "ID du technicien"),
                    new OA\Property(property: "debut", type: "string", format: "date-time", example: "2025-09-10T09:00:00+02:00"),
                    new OA\Property(property: "fin", type: "string", format: "date-time", example: "2025-09-10T10:00:00+02:00"),
                    new OA\Property(property: "adresse", type: "string", example: "12 rue de Paris, 75003"),
                    new OA\Property(property: "veloCategorie", type: "string", example: "VTC"),
                    new OA\Property(property: "veloElectrique", type: "boolean", example: false),
                    new OA\Property(property: "veloMarque", type: "string", example: "Decathlon"),
                    new OA\Property(property: "veloModele", type: "string", example: "Riverside 500"),
                    new OA\Property(property: "commentaireClient", type: "string", example: "Clic-clic au pédalier")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Créée",
                content: new OA\JsonContent(ref: new Model(type: \App\Entity\Intervention::class, groups: ["get_intervention"]))
            ),
            new OA\Response(response: 400, description: "Données invalides"),
            new OA\Response(response: 401, description: "Non authentifié")
        ]
    )]
    public function newIntervention(
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
    #[OA\Put(
        summary: "Remplacer une intervention",
        tags: ["Interventions"],
        security: [["Bearer" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "debut", type: "string", format: "date-time"),
                    new OA\Property(property: "fin", type: "string", format: "date-time"),
                    new OA\Property(property: "adresse", type: "string"),
                    new OA\Property(property: "veloCategorie", type: "string"),
                    new OA\Property(property: "veloElectrique", type: "boolean"),
                    new OA\Property(property: "veloMarque", type: "string"),
                    new OA\Property(property: "veloModele", type: "string"),
                    new OA\Property(property: "commentaireClient", type: "string"),
                    new OA\Property(property: "commentaireTechnicien", type: "string")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 204, description: "Modifiée"),
            new OA\Response(response: 400, description: "Données invalides"),
            new OA\Response(response: 401, description: "Non authentifié")
        ]
    )]
    #[OA\Patch(
        summary: "Modifier partiellement une intervention",
        tags: ["Interventions"],
        security: [["Bearer" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(type: "object") // tu peux détailler si tu veux
        ),
        responses: [
            new OA\Response(response: 204, description: "Modifiée"),
            new OA\Response(response: 400, description: "Données invalides"),
            new OA\Response(response: 401, description: "Non authentifié")
        ]
    )]
    public function editIntervention(Request $request, Intervention $intervention, EntityManagerInterface $em, TagAwareCacheInterface $cache, SerializerInterface $serializer): JsonResponse
    {
        $interventionModifiee = $serializer->deserialize(
            $request->getContent(), Intervention::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $intervention]
        );
        $em->persist($interventionModifiee);
        $em->flush();
        $cache->invalidateTags(["interventions_cache"]);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    // Permet aux techniciens et aux administrateurs de valider une intervention
    #[Route('/api/interventions/{id}/validate', name: 'validate_intervention', methods: ['POST'])]
    #[OA\Post(
        summary: "Valider/finaliser une intervention",
        tags: ["Interventions"],
        security: [["Bearer" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(
                        property: "commentaire_technicien",
                        type: "string",
                        example: "Purge des freins OK"
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Intervention finalisée"),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 403, description: "Interdit (ni admin, ni technicien assigné)"),
            new OA\Response(response: 404, description: "Introuvable")
        ]
    )]
    public function validerIntervention(
        int $id,
        Request $request,
        InterventionRepository $interventionRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $intervention = $interventionRepository->find($id);

        if (!$intervention) {
            return new JsonResponse(['error' => 'Intervention non trouvée.'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->getUser();

        // Seul le technicien associé ou un admin peut valider
        if (
            !$this->isGranted('ROLE_ADMIN') &&
            ($intervention->getTechnicien()?->getId() !== $user?->getId())
        ) {
            return new JsonResponse(['error' => 'Accès interdit.'], Response::HTTP_FORBIDDEN);
        }
        // Récupération du commentaire du technicien
        $data = json_decode($request->getContent(), true);

        try {
            $intervention->setFinalisee(true);
            if(isset($data["commentaire_technicien"])) {
                $intervention->setCommentaireTechnicien($data["commentaire_technicien"]);
            }
            $entityManager->persist($intervention);
            $entityManager->flush();

            return new JsonResponse(['message' => 'Intervention marquée comme finalisée.'], Response::HTTP_OK);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'Une erreur est survenue lors de la validation de l’intervention.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /* Supprime les interventions dans une plage de dates pour les techniciens listés */
    #[Route('/api/interventions/delete', name: 'delete_interventions', methods: ["DELETE"])]
    #[IsGranted("ROLE_ADMIN", message: "Droits insuffisants.")]
    #[OA\Delete(
        summary: "Supprimer en masse des interventions non réservées",
        tags: ["Interventions"],
        security: [["Bearer" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                required: ["technicians","from","to"],
                properties: [
                    new OA\Property(
                        property: "technicians",
                        type: "array",
                        items: new OA\Items(type: "integer"),
                        example: [12, 34]
                    ),
                    new OA\Property(property: "from", type: "string", format: "date", example: "2025-09-01"),
                    new OA\Property(property: "to", type: "string", format: "date", example: "2025-09-30")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Supprimées"),
            new OA\Response(response: 400, description: "Paramètres invalides"),
            new OA\Response(response: 404, description: "Technicien introuvable"),
            new OA\Response(response: 401, description: "Non authentifié")
        ]
    )]
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

        return new JsonResponse(['message' => 'Interventions supprimées avec succès.'], 200);
    }


    /* Supprime une intervention */
    #[Route('/api/interventions/{id}', name: 'delete_intervention', methods: ["DELETE"])]
    #[IsGranted("ROLE_ADMIN", message: "Droits insuffisants.")]
    #[OA\Delete(
        summary: "Supprimer une intervention",
        tags: ["Interventions"],
        security: [["Bearer" => []]],
        parameters: [ new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")) ],
        responses: [ new OA\Response(response: 204, description: "Supprimée"), new OA\Response(response: 404, description: "Introuvable") ]
    )]
    public function deleteIntervention(Intervention $intervention, EntityManagerInterface $em, TagAwareCacheInterface $cache): JsonResponse
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
    #[OA\Post(
        summary: "Générer des interventions à partir d’un modèle de planning",
        tags: ["Interventions"],
        security: [["Bearer" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                required: ["model","technicians","from","to"],
                properties: [
                    new OA\Property(
                        property: "model",
                        type: "integer",
                        example: 5,
                        description: "ID du ModelePlanning"
                    ),
                    new OA\Property(
                        property: "technicians",
                        type: "array",
                        items: new OA\Items(type: "integer"),
                        example: [12, 34]
                    ),
                    new OA\Property(
                        property: "from",
                        type: "string",
                        format: "date",
                        example: "2025-09-01"
                    ),
                    new OA\Property(
                        property: "to",
                        type: "string",
                        format: "date",
                        example: "2025-09-30"
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Créées"),
            new OA\Response(response: 400, description: "Paramètres invalides"),
            new OA\Response(response: 404, description: "Modèle/technicien introuvable"),
            new OA\Response(response: 401, description: "Non authentifié")
        ]
    )]
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
        return new JsonResponse(['message' => 'Interventions créées avec succès.'], 201);
    }


    #[Route('/api/interventions/available/{technicienId}', name: 'get_available_slots', methods: ['GET'])]
    #[OA\Get(
        summary: "Lister les créneaux disponibles d’un technicien (à partir de demain)",
        tags: ["Interventions"],
        parameters: [
            new OA\Parameter(
                name: "technicienId",
                in: "path",
                required: true,
                description: "ID du technicien",
                schema: new OA\Schema(type: "integer", example: 42)
            ),
            new OA\Parameter(
                name: "typeId",
                in: "query",
                required: false,
                description: "Filtrer par type d’intervention",
                schema: new OA\Schema(type: "integer", example: 3)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        ref: new Model(type: \App\Entity\Intervention::class, groups: ["get_intervention"])
                    )
                )
            ),
            new OA\Response(response: 400, description: "Type d’intervention introuvable"),
            new OA\Response(response: 404, description: "Technicien introuvable")
        ]
    )]
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
    #[OA\Post(
        summary: "Réserver une intervention pour un client",
        tags: ["Interventions"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    type: "object",
                    required: ["clientId"],
                    properties: [
                        new OA\Property(property: "clientId", type: "integer", example: 101),
                        new OA\Property(property: "photo", type: "string", format: "binary", description: "Photo du vélo (optionnelle)")
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Réservée"),
            new OA\Response(response: 404, description: "Intervention/Client introuvable"),
            new OA\Response(response: 400, description: "Règle métier non satisfaite")
        ]
    )]
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
