<?php
namespace App\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\EvenementRepository;
use App\Entity\Evenement;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\Persistence\ManagerRegistry as PersistenceManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
class EvenementController extends AbstractController
{   private $evenementRepository;
    private $entityManager;
    public function __construct(EvenementRepository $evenementRepository, EntityManagerInterface $entityManager)
    {
        $this->evenementRepository = $evenementRepository;
        $this->entityManager = $entityManager;
    }
    //méthode par défaut pour symfony
    #[Route('/', name: 'homePage', methods: ['GET'])]
    public function index(): Response
    {
        return new Response('Bienvenue sur la page d\'accueil.');
    }
    //méthode d'ajout d'une event
    #[Route('/evenement/add', name: 'add_evenement', methods: ['POST'])]
    public function add(Request $request, PersistenceManagerRegistry $doctrine): Response
    {
        $data = json_decode($request->getContent(), true);
        $entityManager = $doctrine->getManager();
        if (!$data) {
            return new JsonResponse(['message' => 'Invalid JSON data'], Response::HTTP_BAD_REQUEST);
        }
        $requiredFields = ['titre', 'description', 'date', 'heure', 'lieu', 'categorie', 'type', 'capacity','archive'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                return new JsonResponse(['message' => "Le champ '$field' est manquant."], Response::HTTP_BAD_REQUEST);
            }
        }
            $evenement = new Evenement();
            $evenement->setTitre($data['titre']);
            $evenement->setDescription($data['description']);
            $evenement->setDate(new \DateTime($data['date']));
            $evenement->setHeure(new \DateTime($data['heure']));
            $evenement->setLieu($data['lieu']);
            $evenement->setCategorie($data['categorie']);
            $evenement->setType($data['type']);
            $evenement->setCapacity((int) $data['capacity']);
            $evenement->setImage($data['image'] ?? null);
            $evenement->setArchive($data['archive']);
            $entityManager->persist($evenement);
            $entityManager->flush();
            return $this->json( $evenement);
            
    }
    //méthode de lister une event par id 
    /*#[Route('/evenement/{id}', name: 'get_event_by_id', methods: ['GET'])]
    public function getEventById(int $id): JsonResponse
    {
        $event = $this->evenementRepository->find($id);

        if (!$event) {
            return new JsonResponse(['error' => 'Event not found'], 404);
        }

        $data = [
            'id' => $event->getId(),
            'titre' => $event->getTitre(),
            'description' => $event->getDescription(),
            'date' => $event->getDate()->format('Y-m-d'),
            'heure' => $event->getHeure()->format('H:i'),
            'lieu' => $event->getLieu(),
            'categorie' => $event->getCategorie(),
            'type' => $event->getType(),
            'capacity' => $event->getCapacity(),
            'image' => $event->getImage(),
            'archive' => $event->getArchive(),
        ];

        return new JsonResponse($data, 200);
    }*/
    //méthode pour modifier une event
    #[Route('/evenement/update/{id}', name: 'modifier_evenement', methods: ['PUT'])]
    public function update(Request $request, EvenementRepository $evenementRepository, int $id,PersistenceManagerRegistry $doctrine): JsonResponse
    {
        
        $data = json_decode($request->getContent(), true);
        $entityManager=$doctrine->getManager();
        $evenement = $entityManager->getRepository(Evenement::class)->find($id);
        if (!$evenement) {
            return new JsonResponse(['message' => 'Événement non trouvé'], Response::HTTP_NOT_FOUND);
        }
        if (!$evenement) {
            return $this->json('No evenement found for id' . $id, 404);
        }
        if (isset($data['titre'])) {
            $evenement->setTitre($data['titre']);
        }
        if (isset($data['description'])) {
            $evenement->setDescription($data['description']);
        }
        if (isset($data['date'])) {
            $evenement->setDate(new \DateTime($data['date']));
        }
        if (isset($data['heure'])) {
            $evenement->setHeure(new \DateTime($data['heure']));
        }
        if (isset($data['lieu'])) {
            $evenement->setLieu($data['lieu']);
        }
        if (isset($data['categorie'])) {
            $evenement->setCategorie($data['categorie']);
        }
        if (isset($data['type'])) {
            $evenement->setType($data['type']);
        }
        if (isset($data['capacity'])) {
            $evenement->setCapacity((int) $data['capacity']);
        }
        if (isset($data['image'])) {
            $evenement->setImage($data['image']);
        }
        if (isset($data['archive'])) {
            $evenement->setArchive($data['archive']);
        }
        $entityManager->flush();
        return new JsonResponse(['message' => 'Evenement modifier avec succes'], Response::HTTP_OK);
    }
    //méthode pour rechrcher a une event
    #[Route('/evenement/filtrer', name: 'filtrer_evenements', methods: ['GET'])]
    public function filtrer(Request $request, PersistenceManagerRegistry $doctrine): JsonResponse
        {
            $entityManager = $doctrine->getManager();
            $criteres = [];
            $titre = $request->query->get('titre');
            $type = $request->query->get('type');
            $lieu = $request->query->get('lieu');
            $date = $request->query->get('date'); 
            if ($titre) {
                $criteres['titre'] = $titre;
            }
            if ($type) {
                $criteres['type'] = $type;
            }
            if ($lieu) {
                $criteres['lieu'] = $lieu;
            }
            if ($date) {
                $criteres['date'] = new \DateTime($date);
            }
            $evenements = $entityManager->getRepository(Evenement::class)->findBy($criteres);
            $data = [];
            foreach ($evenements as $evenement) {
                $data[] = [
                    'id' => $evenement->getId(),
                    'titre' => $evenement->getTitre(),
                    'description' => $evenement->getDescription(),
                    'date' => $evenement->getDate()->format('Y-m-d'),
                    'lieu' => $evenement->getLieu(),
                    'categorie' => $evenement->getCategorie(),
                ];
            }
            return new JsonResponse($data);
        }
    //méthode pour lister les events non archivé
    #[Route('/evenement/nonArchive', name: 'non_archive_events', methods: ['GET'])]
    public function getNonArchivedEvents(Request $request, EvenementRepository $evenementRepository): JsonResponse
        {
            $archive = $request->query->get('archive') === 'false' ? 0 : 1;
            $evenements = $evenementRepository->createQueryBuilder('e')
                ->andWhere('e.archive = :archive')
                ->setParameter('archive', $archive)
                ->getQuery()
                ->getResult();
            $data = [];
            foreach ($evenements as $evenement) {
                $data[] = [
                    'id' => $evenement->getId(),
                    'titre' => $evenement->getTitre(),
                    'description' => $evenement->getDescription(),
                    'date' => $evenement->getDate()->format('Y-m-d'),
                    'heure' => $evenement->getHeure()->format('H:i'),
                    'lieu' => $evenement->getLieu(),
                    'categorie' => $evenement->getCategorie(),
                    'type' => $evenement->getType(),
                    'capacity' => $evenement->getCapacity(),
                    'image' => $evenement->getImage(),
                    'archive' => $evenement->getArchive(),
                ];
            }
            return new JsonResponse($data);
            return $this->json($data);
        }
    //méthode pour changer le statut de archive 
    #[Route('/evenement/{id}/archive', name: 'archive_event', methods: ['PUT'])]
    public function archiveEvent(int $id): JsonResponse
        {
            $event = $this->evenementRepository->find($id);
            if (!$event) {
                return $this->json(['error' => 'Event not found'], 404);
            }
            $event->setArchive(false);
            $this->entityManager->flush();
            return $this->json(['message' => 'Event archived successfully']);
        }
    
}
