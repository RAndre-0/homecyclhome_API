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

final class ZoneController extends AbstractController
{
    /* Renvoie toutes les zones */
    #[Route("/api/zones", name: "get_zones", methods: ["GET"])]
    public function get_zones(ZoneRepository $zoneRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
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
    public function new_zone(
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
    public function show_zone(Zone $zone = null, SerializerInterface $serializer): JsonResponse
    {
        if (!$zone) {
            return new JsonResponse(["error" => "Zone non trouvée"], Response::HTTP_NOT_FOUND);
        }
        return new JsonResponse($serializer->serialize($zone, 'json'), Response::HTTP_OK, [], true);
    }

    /* Modifie une zone */
    #[Route("/api/zones/{id}/edit", name: "update_zone", methods: ["PUT", "PATCH"])]
    public function edit_zone(
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
    public function delete_zone(Zone $zone = null, EntityManagerInterface $em, TagAwareCacheInterface $cache): JsonResponse
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
            if ($zone->containsPoint($lat, $lon)) {
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
