<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\EvenementRepository;

class AuthController extends AbstractController
{   
    //méthode pour le login
    #[Route('/login', name: 'auth_user', methods: ['POST'])]
    public function authenticateUser(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $mail = $data['mail'] ?? null;
        $motdepasse = $data['motdepasse'] ?? null;
        if (!$mail || !$motdepasse) {
            return new JsonResponse(['error' => 'Adresse email ou mot de passe manquant.'], 400);
        }
        $user = $entityManager->getRepository(Utilisateur::class)->findOneBy(['mail' => $mail]);

        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé.'], 404);
        }
        if ($user->getMotdepasse() !== $motdepasse) {
            return new JsonResponse(['error' => 'Mot de passe incorrect.'], 401);
        }
        $role = $user->getRole();
        $redirectUrl = $this->getRedirectUrlByRole($role);
        if (!$redirectUrl) {
            return new JsonResponse(['error' => 'Rôle inconnu.'], 403);
        }
        return new JsonResponse([
            'message' => 'Authentification réussie',
            'redirect_url' => $redirectUrl,
            'role' => $role 
        ], 200);
    }
    //méthode pour naviguer selon le role 
    private function getRedirectUrlByRole(string $role): ?string
    {
        switch ($role) {
            case 'admin':
                return '/admin/Home';
            case 'organisateur':
                return '/organisateur/Home';
            case 'participant':
                return '/participant/Home3';
            default:
                return null;
        }
    }
    //méthode pour le logout
    #[Route('/logout', name: 'logout', methods: ['POST'])]
    public function logout(Request $request, SessionInterface $session): JsonResponse
    {
        $session->invalidate();
        return new JsonResponse(['message' => 'Déconnexion réussie'], JsonResponse::HTTP_OK);
    }

  
}

