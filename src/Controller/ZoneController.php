<?php

namespace App\Controller;

use App\Entity\Zone;
use App\Repository\ZoneRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

final class ZoneController extends AbstractController
{
    /* Renvoie toutes les zones */
    #[Route("/api/zones", name: "get_zones", methods: ["GET"])]
    #[OA\Get(
        summary: "Lister toutes les zones",
        tags: ["Zones"],
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        ref: new Model(type: \App\Entity\Zone::class, groups: ["get_zones"])
                    )
                )
            ),
            new OA\Response(response: 500, description: "Erreur serveur")
        ]
    )]
    public function getZones(ZoneRepository $zoneRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        $cache->invalidateTags(["zones_cache"]);
        $id_cache = "get_zones";

        $listeZones = $cache->get($id_cache, function ($item) use ($zoneRepository, $serializer) {
            $item->tag("zones_cache");
            $listeZones = $zoneRepository->findAll();
            return $serializer->serialize($listeZones, "json", ["groups" => "get_zones"]);
        });

        return new JsonResponse($listeZones, Response::HTTP_OK, [], true);
    }

    /* Créé une nouvelle zone */
    #[Route("/api/zones", name: "create_zone", methods: ["POST"])]
    #[OA\Post(
        summary: "Créer une zone",
        tags: ["Zones"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                required: ["name"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Paris Est"),
                    new OA\Property(
                        property: "color",
                        type: "string",
                        example: "#757575",
                        description: "Couleur hexadécimale (6 caractères)"
                    ),
                    new OA\Property(
                        property: "coordinates",
                        type: "array",
                        description: "Liste des points du polygone (latitude/longitude)",
                        items: new OA\Items(
                            type: "object",
                            properties: [
                                new OA\Property(property: "latitude", type: "number", format: "float", example: 48.8566),
                                new OA\Property(property: "longitude", type: "number", format: "float", example: 2.3522)
                            ]
                        )
                    ),
                    new OA\Property(
                        property: "technicien",
                        type: "object",
                        nullable: true,
                        description: "Affectation optionnelle d’un technicien (par ID)",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 42)
                        ]
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Créée",
                content: new OA\JsonContent(ref: new Model(type: \App\Entity\Zone::class, groups: ["get_zones"]))
            ),
            new OA\Response(response: 400, description: "Données invalides"),
            new OA\Response(response: 409, description: "Une zone avec ce nom existe déjà"),
            new OA\Response(response: 500, description: "Erreur serveur")
        ]
    )]
    public function newZone(
        ZoneRepository $zoneRepository,
        Request $request,
        EntityManagerInterface $em,
        SerializerInterface $serializer,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator,
        TagAwareCacheInterface $cache
    ): JsonResponse 
    {
        try {
            $data = $request->getContent();
            if (empty($data)) {
                return new JsonResponse(["error" => "Données JSON manquantes"], JsonResponse::HTTP_BAD_REQUEST);
            }

            try {
                $zone = $serializer->deserialize($data, Zone::class, "json");
            } catch (\Exception $e) {
                return new JsonResponse(["error" => "Format JSON invalide"], JsonResponse::HTTP_BAD_REQUEST);
            }

            // Validation des données
            $errors = $validator->validate($zone);
            if ($errors->count() > 0) {
                return new JsonResponse($serializer->serialize($errors, "json"), JsonResponse::HTTP_BAD_REQUEST, [], true);
            }

            // Vérification si une zone du même nom existe déjà
            if ($zoneRepository->findOneBy(['name' => $zone->getName()])) {
                return new JsonResponse(["error" => "Une zone avec ce nom existe déjà"], JsonResponse::HTTP_CONFLICT);
            }

            $em->persist($zone);
            $em->flush();
            $cache->invalidateTags(["zones_cache"]);

            // Génération de l'URL de la ressource créée
            $location = $urlGenerator->generate("get_zone", ["id" => $zone->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

            return new JsonResponse($serializer->serialize($zone, "json"), JsonResponse::HTTP_CREATED, ["location" => $location], true);
        } catch (\Exception $e) {
            return new JsonResponse(["error" => "Une erreur interne s'est produite"], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /* Retourne une zone */
    #[Route("/api/zones/{id}", name: "get_zone", methods: ["GET"])]
    #[OA\Get(
        summary: "Détail d’une zone",
        tags: ["Zones"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: new Model(type: \App\Entity\Zone::class, groups: ["get_zones"]))
            ),
            new OA\Response(response: 404, description: "Introuvable")
        ]
    )]
    public function showZone(Zone $zone = null, SerializerInterface $serializer): JsonResponse
    {
        if (!$zone) {
            return new JsonResponse(["error" => "Zone non trouvée"], Response::HTTP_NOT_FOUND);
        }
        return new JsonResponse($serializer->serialize($zone, 'json'), Response::HTTP_OK, [], true);
    }

    /* Modifie une zone */
    #[Route("/api/zones/{id}/edit", name: "update_zone", methods: ["PUT", "PATCH"])]
    #[OA\Put(
        summary: "Remplacer une zone",
        tags: ["Zones"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "color", type: "string", example: "#4CAF50"),
                    new OA\Property(
                        property: "coordinates",
                        type: "array",
                        items: new OA\Items(
                            type: "object",
                            properties: [
                                new OA\Property(property: "latitude", type: "number", format: "float"),
                                new OA\Property(property: "longitude", type: "number", format: "float")
                            ]
                        )
                    ),
                    new OA\Property(
                        property: "technicien",
                        type: "object",
                        nullable: true,
                        properties: [
                            new OA\Property(property: "id", type: "integer", nullable: true)
                        ]
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: new Model(type: \App\Entity\Zone::class, groups: ["get_zones"]))
            ),
            new OA\Response(response: 400, description: "Données invalides / Technicien déjà assigné"),
            new OA\Response(response: 404, description: "Introuvable")
        ]
    )]
    #[OA\Patch(
        summary: "Modifier partiellement une zone",
        tags: ["Zones"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(type: "object")
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: new Model(type: \App\Entity\Zone::class, groups: ["get_zones"]))
            ),
            new OA\Response(response: 400, description: "Données invalides / Technicien déjà assigné"),
            new OA\Response(response: 404, description: "Introuvable")
        ]
    )]
    public function editZone(
        Request $request,
        Zone $zone = null,
        EntityManagerInterface $em,
        ZoneRepository $zoneRepository,
        UserRepository $userRepository,
        TagAwareCacheInterface $cache,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse 
    {
        if (!$zone) {
            return new JsonResponse(["error" => "Zone non trouvée"], Response::HTTP_NOT_FOUND);
        }

        $data = $request->getContent();
        if (empty($data)) {
            return new JsonResponse(["error" => "Données JSON manquantes"], Response::HTTP_BAD_REQUEST);
        }

        try {
            $zoneModifiee = $serializer->deserialize($data, Zone::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $zone]);
        } catch (\Exception $e) {
            return new JsonResponse(["error" => "Format JSON invalide"], Response::HTTP_BAD_REQUEST);
        }

        // Gérer la relation avec le technicien
        $decodedData = json_decode($data, true);
        if (array_key_exists('technicien', $decodedData)) {
            $technicienId = $decodedData['technicien']['id'] ?? null;
            if ($technicienId !== null) {
                $technicien = $userRepository->find(intval($technicienId));
                if (!$technicien) {
                    return new JsonResponse(["error" => "Technicien non trouvé"], Response::HTTP_BAD_REQUEST);
                }
                 // Vérifier si ce technicien est déjà assigné à une autre zone
                $zoneDejaAssignee = $zoneRepository->createQueryBuilder('z')
                    ->andWhere('z.technicien = :technicien')
                    ->setParameter('technicien', $technicien)
                    ->getQuery()
                    ->getOneOrNullResult();

                if ($zoneDejaAssignee && $zoneDejaAssignee->getId() !== $zone->getId()) {
                    return new JsonResponse(
                        ["error" => "Technicien déjà assigné à la zone " . $zoneDejaAssignee->getName()],
                        Response::HTTP_BAD_REQUEST
                    );
                }

                // On assigne le technicien à la zone en cours de modification
                $zoneModifiee->setTechnicien($technicien);
                    } else {
                        $zoneModifiee->setTechnicien(null);
                    }
                }
        

        // Validation des données
        $errors = $validator->validate($zoneModifiee);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, "json"), Response::HTTP_BAD_REQUEST, [], true);
        }

        try {
            $em->persist($zoneModifiee);
            $em->flush();
            $cache->invalidateTags(["zones_cache"]);
        } catch (\Exception $e) {
            return new JsonResponse(["error" => "Erreur lors de la mise à jour en base de données"], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse($serializer->serialize($zoneModifiee, "json", ["groups" => ["get_zones"]]), Response::HTTP_OK, [], true);
    }


    /* Supprime une zone */
    #[Route('/api/zones/{id}', name: 'delete_zone', methods: ["DELETE"])]
    #[IsGranted("ROLE_ADMIN", message: "Droits insuffisants.")]
    #[OA\Delete(
        summary: "Supprimer une zone",
        tags: ["Zones"],
        security: [["Bearer" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 204, description: "Supprimée"),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 403, description: "Interdit"),
            new OA\Response(response: 404, description: "Introuvable")
        ]
    )]
    public function deleteZone(Zone $zone = null, EntityManagerInterface $em, TagAwareCacheInterface $cache): JsonResponse
    {
        if (!$zone) {
            return new JsonResponse(["error" => "Zone non trouvée"], Response::HTTP_NOT_FOUND);
        }

        try {
            $em->remove($zone);
            $em->flush();
            $cache->invalidateTags(["zones_cache"]);

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            return new JsonResponse(["error" => "Une erreur est survenue lors de la suppression"], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /* Vérifie si une coordonnée est couverte */
    #[Route("/api/zones/check", name: "check_zone_coverage", methods: ["POST"])]
    #[OA\Post(
        summary: "Vérifier si des coordonnées sont couvertes par une zone",
        tags: ["Zones"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                required: ["latitude","longitude"],
                properties: [
                    new OA\Property(property: "latitude", type: "number", format: "float", example: 48.8566),
                    new OA\Property(property: "longitude", type: "number", format: "float", example: 2.3522)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(
                    oneOf: [
                        new OA\Schema( // couvert
                            type: "object",
                            properties: [
                                new OA\Property(property: "covered", type: "boolean", example: true),
                                new OA\Property(property: "zone_id", type: "integer", example: 7),
                                new OA\Property(property: "zone_name", type: "string", example: "Paris Est"),
                                new OA\Property(property: "technicien_id", type: "integer", nullable: true, example: 42)
                            ]
                        ),
                        new OA\Schema( // non couvert
                            type: "object",
                            properties: [
                                new OA\Property(property: "covered", type: "boolean", example: false)
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Latitude/Longitude manquantes")
        ]
    )]
    public function checkZoneCoverage(Request $request, ZoneRepository $zoneRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['latitude']) || !isset($data['longitude'])) {
            return new JsonResponse(["error" => "Latitude et longitude requises"], JsonResponse::HTTP_BAD_REQUEST);
        }

        $lat = $data['latitude'];
        $lon = $data['longitude'];

        // Recherche de toutes les zones
        $zones = $zoneRepository->findAll();

        foreach ($zones as $zone) {
            if ($zone->containsPoint($lat, $lon) && $zone->getTechnicien() !== null) {
                return new JsonResponse([
                    "covered" => true,
                    "zone_id" => $zone->getId(),
                    "zone_name" => $zone->getName(),
                    "technicien_id" => $zone->getTechnicien()?->getId(),
                ], JsonResponse::HTTP_OK);
            }
        }

        return new JsonResponse(["covered" => false], JsonResponse::HTTP_OK);
    }

}
