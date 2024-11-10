<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\ItemInterface;
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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserController extends AbstractController
{
    /* Renvoie tous les utilisateurs */
    #[Route('/api/users', name: 'users', methods: ["GET"])]
    public function get_users(UserRepository $userRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        $id_cache = "get_users";

        $liste_users = $cache->get($id_cache, function (ItemInterface $item) use ($userRepository, $serializer) {
            $item->tag("users_cache");
            $liste_users = $userRepository->findAll();
            return $serializer->serialize($liste_users, "json", ["groups" => "get_users"]);
        });

        return new JsonResponse($liste_users, Response::HTTP_OK, [], true);
    }

    /* Retourne un utilisateur */
    #[Route('/api/users/{id}', name: 'user', methods: ["GET"])]
    public function get_user(User $user, UserRepository $userRepository, SerializerInterface $serializer): JsonResponse
    {
        $user_json = $serializer->serialize($user, 'json', ["groups" => "get_user"]);
        return new JsonResponse($user_json, Response::HTTP_OK, [], true);
    }

    /* Supprime un utilisateur */
    #[Route('/api/users/{id}', name: 'delete_user', methods: ["DELETE"])]
    #[IsGranted("ROLE_ADMIN", message: "Droits insuffisants.")]
    public function delete_user(User $user, EntityManagerInterface $em, TagAwareCacheInterface $cache): JsonResponse
    {
        $cache->invalidateTags(["users_cache"]);
        $em->remove($user);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /* Créé un nouveau user */
    #[Route('/api/users', name: 'create_user', methods: ["POST"])]
    #[IsGranted("ROLE_ADMIN", message: "Droits insuffisants.")]
    public function create_user(Request $request, EntityManagerInterface $em, SerializerInterface $serializer, UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $user = $serializer->deserialize($request->getContent(), User::class, "json");

        $errors = $validator->validate($user);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, "json"), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        /* Hash le mot de passe de l'utilisateur */
        $plaintext_password = $user->getPassword();
        $hashedPassword = $passwordHasher->hashPassword($user, $plaintext_password);
        $user->setPassword($hashedPassword);

        $em->persist($user);
        $em->flush();
        $json_user = $serializer->serialize($user, "json", ["groups" => "get_users"]);
        $location = $urlGenerator->generate("user", ["id" => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse($json_user, Response::HTTP_CREATED, ["location" => $location], true);
    }

    /* Met à jour un utilisateur existant */
    #[Route('/api/users/{id}', name: 'update_user', methods: ["PUT", "PATCH"])]
    public function update_user(User $user, EntityManagerInterface $em, SerializerInterface $serializer, UserRepository $userRepository, Request $request, TagAwareCacheInterface $cache): JsonResponse
    {
        $user_modifie = $serializer->deserialize($request->getContent(), User::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $user]);
        $em->persist($user_modifie);
        $em->flush();
        $cache->invalidateTags(["users_cache"]);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
