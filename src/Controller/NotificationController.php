<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Entity\Notification;
use App\Entity\Registre;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
class NotificationController extends AbstractController
{
    #[Route('/organisateur/envoyer-notification/{evenementId}', name: 'envoyer_notification', methods: ['POST'])]
    public function envoyerNotification(int $evenementId, Request $request, EntityManagerInterface $entityManager, UserInterface $user)
    {
        if ($user->getRole() !== 'organisateur') {
            return $this->json(['message' => 'Vous devez être un organisateur pour envoyer des notifications.'], 403);
        }
        $evenement = $entityManager->getRepository(Evenement::class)->find($evenementId);
        if (!$evenement) {
            return $this->json(['message' => 'Événement non trouvé.'], 404);
        }
        $registreRepository = $entityManager->getRepository(Registre::class);
        $participants = $registreRepository->findBy(['evenement' => $evenement]);

        if (empty($participants)) {
            return $this->json(['message' => 'Aucun participant trouvé pour cet événement.'], 404);
        }
        $message = $request->request->get('message');
        if (!$message) {
            return $this->json(['message' => 'Le message ne peut pas être vide.'], 400);
        }
        foreach ($participants as $registre) {
            $participant = $registre->getUtilisateur();
            if ($participant->getRole() === 'participant') {
                $notification = new Notification();
                $notification->setExpediteur($user) 
                             ->setDestinataire($participant) 
                             ->setEvenement($evenement) 
                             ->setMessage($message) 
                             ->setDateEnvoi(new \DateTime()); 
                $entityManager->persist($notification);
            }
        }
        $entityManager->flush();
        return $this->json(['message' => 'Les notifications ont été envoyées avec succès.']);
    }
}
