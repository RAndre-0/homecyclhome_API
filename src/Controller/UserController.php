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
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;


class UserController extends AbstractController
{
    /* Renvoie tous les utilisateurs */
    // #[Route('/api/users', name: 'users', methods: ["GET"])]
    // public function get_users(UserRepository $userRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    // {
    //     $id_cache = "get_users";

    //     $liste_users = $cache->get($id_cache, function (ItemInterface $item) use ($userRepository, $serializer) {
    //         $item->tag("users_cache");
    //         $liste_users = $userRepository->findAll();
    //         return $serializer->serialize($liste_users, "json", ["groups" => "get_users"]);
    //     });

    //     return new JsonResponse($liste_users, Response::HTTP_OK, [], true);
    // }
    #[Route('/api/users', name: 'users_all', methods: ["GET"])]
    #[Route('/api/users/role-{role?}', name: 'users', methods: ["GET"])]
    public function getUsers(
        UserRepository $userRepository,
        SerializerInterface $serializer,
        TagAwareCacheInterface $cache,
        string $role = null // Injection du paramètre d'URL (optionnel)
    ): JsonResponse 
    {
        $cache->invalidateTags(["users_cache", "users_cache_ROLE_TECHNICIEN", "users_cache_ROLE_ADMIN"]);
        // Génération d'un ID de cache en fonction du rôle
        $idCache = "users_cache" . ($role ? "_" . $role : "");
        
        $usersData = $cache->get($idCache, function (ItemInterface $item) use ($userRepository, $serializer, $role) {
            $item->tag("users_cache");
            
            // Récupération des utilisateurs en fonction du rôle
            $users = $role ? $userRepository->findUsersByRole($role) : $userRepository->findAll();
            
            return $serializer->serialize($users, "json", ["groups" => "get_users"]);
        });
    
        return new JsonResponse($usersData, Response::HTTP_OK, [], true);
    }

    /* Retourne un utilisateur */
    #[Route('/api/users/{id<\d+>}', name: 'user', methods: ["GET"])]
    public function get_user(User $user, UserRepository $userRepository, SerializerInterface $serializer): JsonResponse
    {
        $userJson = $serializer->serialize($user, 'json', ["groups" => "get_user"]);
        return new JsonResponse($userJson, Response::HTTP_OK, [], true);
    }

    /* Retourne l'utilisateur connecté */
    #[Route('/api/users/me', name: 'user_me', methods: ['GET'])]
    public function getCurrentUser(SerializerInterface $serializer): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $userJson = $serializer->serialize($user, 'json', ['groups' => 'get_user']);

        return new JsonResponse($userJson, Response::HTTP_OK, [], true);
    }

    /* Supprime un utilisateur */
    #[Route('/api/users/{id}', name: 'delete_user', methods: ["DELETE"])]
    #[IsGranted("ROLE_ADMIN", message: "Droits insuffisants.")]
    public function delete_user(User $user, EntityManagerInterface $em, TagAwareCacheInterface $cache): JsonResponse
    {
        $em->remove($user);
        $em->flush();
        $cache->invalidateTags(["users_cache", "users_cache_ROLE_TECHNICIEN", "users_cache_ROLE_ADMIN"]);
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /* Créé un nouveau user */
    #[Route('/api/users', name: 'create_user', methods: ["POST"])]
    #[IsGranted("ROLE_ADMIN", message: "Droits insuffisants.")]
    public function create_user(Request $request, EntityManagerInterface $em, SerializerInterface $serializer, UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator, UserPasswordHasherInterface $passwordHasher, TagAwareCacheInterface $cache): JsonResponse
    {
        $user = $serializer->deserialize($request->getContent(), User::class, "json");

        $errors = $validator->validate($user);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, "json"), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        /* Hash le mot de passe de l'utilisateur */
        $plaintextpassword = $user->getPassword();
        $hashedPassword = $passwordHasher->hashPassword($user, $plaintextpassword);
        $user->setPassword($hashedPassword);

        $em->persist($user);
        $em->flush();
        $cache->invalidateTags(["users_cache", "users_cache_ROLE_TECHNICIEN", "users_cache_ROLE_ADMIN"]);
        $jsonUser = $serializer->serialize($user, "json", ["groups" => "get_users"]);
        $location = $urlGenerator->generate("user", ["id" => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse($jsonUser, Response::HTTP_CREATED, ["location" => $location], true);
    }

    /* Met à jour un utilisateur existant */
    #[Route('/api/users/{id}', name: 'update_user', methods: ["PUT", "PATCH"])]
    public function update_user(User $user, EntityManagerInterface $em, SerializerInterface $serializer, UserRepository $userRepository, Request $request, TagAwareCacheInterface $cache): JsonResponse
    {
        $userModifie = $serializer->deserialize($request->getContent(), User::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $user]);
        $em->persist($userModifie);
        $em->flush();
        $cache->invalidateTags(["users_cache", "users_cache_ROLE_TECHNICIEN", "users_cache_ROLE_ADMIN"]);

        return new JsonResponse($serializer->serialize($userModifie, 'json'), Response::HTTP_OK, [], true);
    }

    #[Route('/api/register', name: 'register_user', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $passwordHasher,
        UrlGeneratorInterface $urlGenerator,
        UserRepository $userRepository,
        TagAwareCacheInterface $cache,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $user = $serializer->deserialize($request->getContent(), User::class, 'json');

        $errors = $validator->validate($user);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        if ($userRepository->findOneBy(['email' => $user->getEmail()])) {
            return new JsonResponse(['error' => 'Un utilisateur avec cette adresse email existe déjà.'], Response::HTTP_CONFLICT);
        }

        $plainPassword = $user->getPassword();
        $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        try {
            $em->persist($user);
            $em->flush();
            $cache->invalidateTags(["users_cache", "users_cache_ROLE_TECHNICIEN", "users_cache_ROLE_ADMIN"]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de l’enregistrement.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Renvoi d'un token JWT pour authentifier l'utilisateur
        $token = $jwtManager->create($user);

        return new JsonResponse([
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ]
        ], JsonResponse::HTTP_CREATED);
    }


}
