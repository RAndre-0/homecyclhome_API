<?php

namespace App\Controller;

use App\Entity\TypeIntervention;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\TypeInterventionRepository;
use Doctrine\ORM\EntityManager;
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

class TypeInterventionController extends AbstractController
{
    /* Retourne la liste des types d'intervention' */
    #[Route('/api/types-intervention', name: 'get_types_intervention', methods: ["GET"])]
    public function get_types_intervention(TypeInterventionRepository $typeInterventionRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        try {
            $idCache = "types_inter_cache";
            $cache->invalidateTags([$idCache]);
            $listTypesintervention = $cache->get($idCache, function (ItemInterface $item) use ($typeInterventionRepository, $serializer) {
                $item->tag("types_inter_cache");
                $listTypesintervention = $typeInterventionRepository->findAll();
                return $serializer->serialize($listTypesintervention, "json", ["groups" => "get_types_intervention"]);
            });
            return new JsonResponse($listTypesintervention, Response::HTTP_OK, [], true);
        } catch (\Exception $e) {
            return new JsonResponse(["error" => "Une erreur est survenue"], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /* Retourne un type d'intervention */
    #[Route('/api/types-intervention/{id}', name: 'get_type_intervention', methods: ["GET"])]
    public function get_type_intervention(TypeIntervention $typeIntervention, SerializerInterface $serializer): JsonResponse
    {
        if (!$typeIntervention) {
            return new JsonResponse(["message" => "Type d'intervention non trouvé"], Response::HTTP_NOT_FOUND);
        }
        try {
            $typeInterventionJson = $serializer->serialize($typeIntervention, "json", ["groups" => "get_type_Intervention"]);
            return new JsonResponse($typeInterventionJson, Response::HTTP_OK, [], true);
        } catch (\Exception $e) {
            return new JsonResponse(["error" => "Une erreur est survenue", Response::HTTP_INTERNAL_SERVER_ERROR]);
        }
    }

    /* Nouveau type d'intervention */
    #[Route('/api/types-intervention', name: 'create_type_intervention', methods: ["POST"])]
    #[IsGranted("ROLE_ADMIN", message: "Droits insuffisants.")]
    public function create_type_intervention(
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator,
        TagAwareCacheInterface $cache,
        Request $request
    ): JsonResponse 
    {
        try {
            $typeIntervention = $serializer->deserialize($request->getContent(), TypeIntervention::class, "json");

            $errors = $validator->validate($typeIntervention);
            if ($errors->count() > 0) {
                return new JsonResponse($serializer->serialize(["error" => "Données non conformes"], "json"), JsonResponse::HTTP_BAD_REQUEST, [], true);
            }
    
            $em->persist($typeIntervention);
            $em->flush();
            $cache->invalidateTags(["types_inter_cache"]);
            $typeInterventionJson = $serializer->serialize($typeIntervention, "json", ["groups" => "get_type_intervention"]);
            $location = $urlGenerator->generate("get_type_intervention", ["id" => $typeIntervention->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
            return new JsonResponse($typeInterventionJson, Response::HTTP_CREATED, ["location" => $location], true);
        } catch (\Exception $e) {
            return new JsonResponse(["error" => "Une erreur est survenue", Response::HTTP_INTERNAL_SERVER_ERROR]);
        }

    }

    /* Modifie un type d'intervention */
    #[Route("api/types-intervention/{id}", name: "update_type_intervention", methods: ["PATCH", "PUT"])]
    #[IsGranted("ROLE_ADMIN", message: "Droits insuffisants.")]
    public function update_type_intervention(
        TypeIntervention $typeIntervention, 
        SerializerInterface $serializer, 
        Request $request,
        EntityManagerInterface $em,
        TagAwareCacheInterface $cache,
        ValidatorInterface $validator
        ): JsonResponse 
        {
        if (!$typeIntervention) {
            return new JsonResponse(["message" => "Type d'intervention non trouvé"], Response::HTTP_NOT_FOUND);
        }
        try {
            $typeInterventionModifie = $serializer->deserialize($request->getContent(), TypeIntervention::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $typeIntervention]);
            $errors = $validator->validate($typeInterventionModifie);
            if ($errors->count() > 0) {
                return new JsonResponse($serializer->serialize(["error" => "Données non conformes"], "json"), JsonResponse::HTTP_BAD_REQUEST, [], true);
            }
            $em->persist($typeInterventionModifie);
            $em->flush();
            $cache->invalidateTags(["types_inter_cache"]);
            return new JsonResponse($serializer->serialize($typeInterventionModifie, "json", ["groups" => "get_type_intervention"]), Response::HTTP_OK, [], true);
        } catch (\Exception $e) {
            return new JsonResponse(["error" => "Erreur lors de la mise à jour du type d'intervention"], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /* Supprime un type d'intervention et les interventions qui lui sont liées */
    #[Route('/api/types-intervention/{id}', name: 'delete_type_intervention', methods: ["DELETE"])]
    #[IsGranted("ROLE_ADMIN", message: "Droits insuffisants.")]
    public function delete_type_intervention(EntityManagerInterface $em, TypeIntervention $typeIntervention, TagAwareCacheInterface $cache): JsonResponse 
    {
        $interventions = $typeIntervention->getInterventions();
        foreach ($interventions as $intervention) {
            $em->remove($intervention);
        }
        $em->remove($typeIntervention);
        $em->flush();
        $cache->invalidateTags(["types_inter_cache"]);
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
