<?php

namespace App\Controller;

use App\Entity\Collection;
use App\Entity\User;
use App\Repository\CollectionRepository;
use App\Repository\ProductWatchRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/collections')]
class CollectionController extends AbstractController
{
    public function __construct(
        private CollectionRepository $collectionRepository,
        private ProductWatchRepository $watchRepository,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
    ) {}

    #[Route('', name: 'api_collections_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $collections = $this->collectionRepository->findByUserWithWatchCount($user);

        return $this->json([
            'collections' => $collections,
        ]);
    }

    #[Route('', name: 'api_collections_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $name = trim($data['name'] ?? '');
        if (!$name) {
            return $this->json(['error' => 'Naam is verplicht'], Response::HTTP_BAD_REQUEST);
        }

        /** @var User $user */
        $user = $this->getUser();

        $collection = new Collection();
        $collection->setUser($user);
        $collection->setName($name);

        if (isset($data['description'])) {
            $collection->setDescription($data['description']);
        }

        $errors = $this->validator->validate($collection);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($collection);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Collectie aangemaakt',
            'collection' => $this->serializeCollection($collection),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_collections_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $collection = $this->findUserCollection($id);
        if (!$collection) {
            return $this->json(['error' => 'Collectie niet gevonden'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'collection' => $this->serializeCollectionWithWatches($collection),
        ]);
    }

    #[Route('/{id}', name: 'api_collections_update', methods: ['PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $collection = $this->findUserCollection($id);
        if (!$collection) {
            return $this->json(['error' => 'Collectie niet gevonden'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['name'])) {
            $name = trim($data['name']);
            if (!$name) {
                return $this->json(['error' => 'Naam mag niet leeg zijn'], Response::HTTP_BAD_REQUEST);
            }
            $collection->setName($name);
        }

        if (array_key_exists('description', $data)) {
            $collection->setDescription($data['description']);
        }

        if (array_key_exists('isPublic', $data)) {
            $collection->setIsPublic((bool) $data['isPublic']);
        }

        $errors = $this->validator->validate($collection);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        return $this->json([
            'message' => 'Collectie bijgewerkt',
            'collection' => $this->serializeCollection($collection),
        ]);
    }

    #[Route('/{id}', name: 'api_collections_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $collection = $this->findUserCollection($id);
        if (!$collection) {
            return $this->json(['error' => 'Collectie niet gevonden'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($collection);
        $this->entityManager->flush();

        return $this->json(['message' => 'Collectie verwijderd']);
    }

    #[Route('/{id}/watches/{watchId}', name: 'api_collections_add_watch', methods: ['POST'])]
    public function addWatch(int $id, int $watchId): JsonResponse
    {
        $collection = $this->findUserCollection($id);
        if (!$collection) {
            return $this->json(['error' => 'Collectie niet gevonden'], Response::HTTP_NOT_FOUND);
        }

        /** @var User $user */
        $user = $this->getUser();
        $watch = $this->watchRepository->find($watchId);

        if (!$watch || $watch->getUser() !== $user) {
            return $this->json(['error' => 'Watch niet gevonden'], Response::HTTP_NOT_FOUND);
        }

        if ($collection->hasProductWatch($watch)) {
            return $this->json(['error' => 'Watch zit al in deze collectie'], Response::HTTP_BAD_REQUEST);
        }

        $collection->addProductWatch($watch);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Watch toegevoegd aan collectie',
            'collection' => $this->serializeCollection($collection),
        ]);
    }

    #[Route('/{id}/watches/{watchId}', name: 'api_collections_remove_watch', methods: ['DELETE'])]
    public function removeWatch(int $id, int $watchId): JsonResponse
    {
        $collection = $this->findUserCollection($id);
        if (!$collection) {
            return $this->json(['error' => 'Collectie niet gevonden'], Response::HTTP_NOT_FOUND);
        }

        /** @var User $user */
        $user = $this->getUser();
        $watch = $this->watchRepository->find($watchId);

        if (!$watch || $watch->getUser() !== $user) {
            return $this->json(['error' => 'Watch niet gevonden'], Response::HTTP_NOT_FOUND);
        }

        $collection->removeProductWatch($watch);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Watch verwijderd uit collectie',
            'collection' => $this->serializeCollection($collection),
        ]);
    }

    private function findUserCollection(int $id): ?Collection
    {
        /** @var User $user */
        $user = $this->getUser();
        $collection = $this->collectionRepository->find($id);

        if (!$collection || $collection->getUser() !== $user) {
            return null;
        }

        return $collection;
    }

    private function serializeCollection(Collection $collection): array
    {
        /** @var User $user */
        $user = $this->getUser();
        return [
            'id' => $collection->getId(),
            'name' => $collection->getName(),
            'description' => $collection->getDescription(),
            'watchCount' => $collection->getWatchCount(),
            'isPublic' => $collection->isPublic(),
            'shareUrl' => $collection->isPublic() && $user->getUsername()
                ? '/u/' . $user->getUsername() . '/' . $collection->getSlug()
                : null,
            'createdAt' => $collection->getCreatedAt()->format('c'),
            'updatedAt' => $collection->getUpdatedAt()?->format('c'),
        ];
    }

    private function serializeCollectionWithWatches(Collection $collection): array
    {
        $serialized = $this->serializeCollection($collection);
        $serialized['watches'] = [];

        foreach ($collection->getProductWatches() as $watch) {
            $serialized['watches'][] = [
                'id' => $watch->getId(),
                'url' => $watch->getUrl(),
                'domain' => $watch->getDomain(),
                'productName' => $watch->getProductName(),
                'priceSelector' => $watch->getPriceSelector(),
                'currency' => $watch->getCurrency(),
                'currentPrice' => $watch->getCurrentPrice(),
                'previousPrice' => $watch->getPreviousPrice(),
                'originalPrice' => $watch->getOriginalPrice(),
                'checkMethod' => $watch->getCheckMethod()->value,
                'consecutiveFailures' => $watch->getConsecutiveFailures(),
                'isActive' => $watch->isActive(),
                'lastCheckedAt' => $watch->getLastCheckedAt()?->format('c'),
                'imageUrl' => $watch->getImageUrl(),
                'createdAt' => $watch->getCreatedAt()->format('c'),
            ];
        }

        return $serialized;
    }
}
