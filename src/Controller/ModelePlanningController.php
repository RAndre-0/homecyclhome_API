<?php

namespace App\Controller;

use App\Entity\Intervention;
use App\Entity\ModelePlanning;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use App\Repository\ModelePlanningRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ModelePlanningController extends AbstractController
{
    /* Renvoie tous les modeles */
    #[Route('/api/modeles-planning', name: 'get_modeles_planning', methods: ["GET"])]
    public function get_modeles_planning(ModelePlanningRepository $modelePlanningRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        try {
            $idCache = "modele_planning_cache";
            $cache->invalidateTags(["modele_planning_cache"]);
            $listeModelesPlanning = $cache->get($idCache, function (ItemInterface $item) use ($modelePlanningRepository, $serializer) {
                $item->tag("modele_planning_cache");
                $listeModelesPlanning = $modelePlanningRepository->findAll();
                return $serializer->serialize($listeModelesPlanning, "json", ["groups" => "get_modeles_planning"]);
            });
        } catch (\Exception $e) {
            return new JsonResponse(["error" => "Une erreur est survenue"], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse($listeModelesPlanning, Response::HTTP_OK, [], true);
    }

    /* Renvoie un modèle de planning */
    #[Route('/api/modeles-planning/{id}', name: 'get_modele_planning', methods: ["GET"])]
    public function get_modele_planning(ModelePlanning $modelePlanning, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        if (!$modelePlanning) {
            return new JsonResponse(["message" => "Modèle de planning non trouvé"], Response::HTTP_NOT_FOUND);
        }
        $modelePlanningJson = $serializer->serialize($modelePlanning, "json", ["groups" => "get_modele_planning"]);
        return new JsonResponse($modelePlanningJson, Response::HTTP_OK, [], true);
    }

    /* Supprime un modèle de planning et les types d'intervention liés */
    #[Route('/api/modeles-planning/{id}', name: 'delete_modele_planning', methods: ["DELETE"])]
    #[IsGranted("ROLE_ADMIN", message: "Droits insuffisants.")]
    public function delete_modele_planning(
        ModelePlanning $modelePlanning,
        TagAwareCacheInterface $cache,
        EntityManagerInterface $em
    ): JsonResponse 
    {
        if (!$modelePlanning) {
            return new JsonResponse(["message" => "Modèle de planning non trouvé"], Response::HTTP_NOT_FOUND);
        }
        try {
            foreach ($modelePlanning->getModeleInterventions() as $intervention) {
                $em->remove($intervention);
            }
            $em->remove($modelePlanning);
            $em->flush();
            $cache->invalidateTags(["modele_planning_cache"]);
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            return new JsonResponse(["error" => "Erreur lors de la suppression"], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

    /* Modifie un modèle de planning */
    #[Route("/api/modeles-planning/{id}", name: "update_modele_planning", methods: ["PUT", "PATCH"])]
    #[IsGranted("ROLE_ADMIN", message: "Droits insuffisants.")]
    public function update_modele_planning(
        SerializerInterface $serializer, 
        EntityManagerInterface $em, 
        TagAwareCacheInterface $cache, 
        Request $request,
        ModelePlanning $modelePlanning,
        ValidatorInterface $validator
        ): JsonResponse 
        {
        if (!$modelePlanning) {
            return new JsonResponse(["message" => "Modèle de planning non trouvé"], Response::HTTP_NOT_FOUND);
        }
        try {
            $modeleplanningModifie = $serializer->deserialize($request->getContent(), ModelePlanning::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $modelePlanning]);
            $errors = $validator->validate($modeleplanningModifie);
            if ($errors->count() > 0) {
                return new JsonResponse($serializer->serialize(["error" => "Données non conformes"], "json"), Response::HTTP_BAD_REQUEST, [], true);
            }
            $em->persist($modeleplanningModifie);
            $em->flush();
            $cache->invalidateTags(["modele_planning_cache"]);
            return new JsonResponse($serializer->serialize($modeleplanningModifie, "json", ["groups" => "get_modele_planning"]), Response::HTTP_OK, [], true);
        } catch (\Exception $e) {
            return new JsonResponse(["error" => "Erreur lors de la mise à jour"], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }


    /* Créé un nouveau modèle de planning */
    #[Route("/api/modeles-planning", name: "create_modele_planning", methods: ["POST"])]
    #[IsGranted("ROLE_ADMIN", message: "Droits insuffisants.")]
    public function create_modele_planning(
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator,
        TagAwareCacheInterface $cache,
        Request $request
    ): JsonResponse 
    {
        try {
            $modelePlanning = $serializer->deserialize($request->getContent(), ModelePlanning::class, "json");

            $errors = $validator->validate($modelePlanning);
            if ($errors->count() > 0) {
                return new JsonResponse($serializer->serialize(["error" => "Données non conformes"], "json"), JsonResponse::HTTP_BAD_REQUEST, [], true);
            }
    
            $em->persist($modelePlanning);
            $em->flush();
            $cache->invalidateTags(["modele_planning_cache"]);
            $modelePlanningJson = $serializer->serialize($modelePlanning, "json", ["groups" => "get_modele_planning"]);
            $location = $urlGenerator->generate("get_modele_planning", ["id" => $modelePlanning->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
            return new JsonResponse($modelePlanningJson, Response::HTTP_CREATED, ["location" => $location], true);
        } catch (\Exception $e) {
            return new JsonResponse(["error" => "Erreur lors de la création"], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }
}
