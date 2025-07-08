<?php

namespace App\Controller;

use App\Entity\ModelePlanning;
use App\Entity\TypeIntervention;
use App\Entity\ModeleInterventions;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ModeleInterventionsRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ModeleInterventionsController extends AbstractController
{
    /* Renvoie les relations modèle interventions */
    #[Route('/api/modele-interventions', name: 'get_modele_interventions', methods: ["GET"])]
    public function get_modele_interventions(ModeleInterventionsRepository $modeleInterventionsRepository, TagAwareCacheInterface $cache, SerializerInterface $serializer): JsonResponse
    {
        try {
            $idCache = "modele_interventions_cache";
            $cache->invalidateTags(["modele_interventions_cache"]);
            $listModeleInterventions = $cache->get($idCache, function (ItemInterface $item) use ($modeleInterventionsRepository, $serializer) {
                $item->tag("modele_interventions_cache");
                $listModeleInterventions = $modeleInterventionsRepository->findAll();
                return $serializer->serialize($listModeleInterventions, "json", ["groups" => "get_modele_interventions"]);
            });
    
            return new JsonResponse($listModeleInterventions, Response::HTTP_OK, [], true);
        } catch (\Exception $e) {
            return new JsonResponse(["error" => "Une erreur est survenue"], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /* Retourne un type d'intervention du modèle */
    #[Route('/api/modele-interventions/{id}', name: "get_modele_intervention", methods: ["GET"])]
    public function get_modele_intervention(ModeleInterventions $modeleInterventions, SerializerInterface $serializer): JsonResponse
    {
        if (!$modeleInterventions) {
            return new JsonResponse(["message" => "Intervention de modèle non trouvée"], Response::HTTP_NOT_FOUND);
        }
        try {
            $modeleInterventionsJson = $serializer->serialize($modeleInterventions, "json", ["groups" => "get_modele_intervention"]);
            return new JsonResponse($modeleInterventionsJson, Response::HTTP_OK, [], true);
        } catch (\Exception $e) {
            return new JsonResponse(["error" => "Une erreur est survenue"], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /* Supprime une intervention du modèle */
    #[Route('/api/modele-interventions/{id}', name: "delete_modele_intervention", methods: ["DELETE"])]
    #[IsGranted("ROLE_ADMIN", message: "Droits insuffisants.")]
    public function delete_modele_intervention(ModeleInterventions $modeleInterventions, TagAwareCacheInterface $cache, EntityManagerInterface $em): JsonResponse
    {
        if (!$modeleInterventions) {
            return new JsonResponse(["message" => "Intervention de modèle non trouvée"], Response::HTTP_NOT_FOUND);
        }
        try {
            $em->remove($modeleInterventions);
            $em->flush();
            $cache->invalidateTags(["modele_interventions_cache"]);
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            return new JsonResponse(["error" => "Erreur lors de la suppression"], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

    /* Créé une intervention dans le modèle */
    #[Route("/api/modele-interventions", name: "create_modele_intervention", methods: ["POST"])]
    #[IsGranted("ROLE_ADMIN", message: "Droits insuffisants.")]
    public function create_modele_intervention(
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator,
        TagAwareCacheInterface $cache,
        Request $request
    ): JsonResponse 
    {
        $data = json_decode($request->getContent(), true);
    
        if (!isset($data["type_intervention"], $data["modele_planning"], $data["intervention_time"])) {
            return new JsonResponse(["error" => "Invalid or missing arguments"], JsonResponse::HTTP_BAD_REQUEST);
        }
    
        // Récupérer les entités associées
        $typeIntervention = $em->getRepository(TypeIntervention::class)->find((int) $data["type_intervention"]);
        $modelePlanning = $em->getRepository(ModelePlanning::class)->find((int) $data["modele_planning"]);
    
        try {
            $interventionTime = new \DateTime($data["intervention_time"]);
        } catch (\Exception $e) {
            return new JsonResponse(["error" => "Format de de date invalide"], JsonResponse::HTTP_BAD_REQUEST);
        }
    
        if (!$typeIntervention || !$modelePlanning) {
            return new JsonResponse(["error" => "Type d'intervention ou modèle de planning invalide"], JsonResponse::HTTP_BAD_REQUEST);
        }
    
        // Création du modèle d'intervention
        $modeleIntervention = new ModeleInterventions();
        $modeleIntervention->setTypeIntervention($typeIntervention);
        $modeleIntervention->setModeleIntervention($modelePlanning);
        $modeleIntervention->setInterventionTime($interventionTime);
    
        // Validation
        $errors = $validator->validate($modeleIntervention);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, "json"), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
    
        try {
            $em->persist($modeleIntervention);
            $em->flush();
            $cache->invalidateTags(["modele_interventions_cache"]);
    
            $modeleInterventionJson = $serializer->serialize($modeleIntervention, "json", ["groups" => "get_modele_intervention"]);
            $location = $urlGenerator->generate("get_modele_intervention", ["id" => $modeleIntervention->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
    
            return new JsonResponse($modeleInterventionJson, JsonResponse::HTTP_CREATED, ["Location" => $location], true);
        } catch (\Exception $e) {
            return new JsonResponse(["error" => "Une erreur est survenue lors de la sauvegarde des données"], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
}
