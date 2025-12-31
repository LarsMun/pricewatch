# WhenDue Code Style Manifest

> Dit document definieert de coding standards voor het WhenDue project.
> Claude Code en alle developers volgen deze regels.

## Kernprincipes

1. **Code is documentatie** — Leesbare code boven comments
2. **Fail fast** — Valideer vroeg, crash duidelijk
3. **Single responsibility** — Eén reden om te veranderen
4. **Testbaar by design** — Geen static calls, geen hidden dependencies

---

## Method & Class Design

### Method lengte
```
HARD LIMIT: Maximaal 20 regels per method (exclusief docblock)
SOFT TARGET: 10-15 regels
```

**Te lang? Splits op:**
- Extract method voor logische substappen
- Extract class als method te veel dependencies heeft

### Class lengte
```
SOFT LIMIT: 200 regels per class
HARD LIMIT: 300 regels — daarna verplicht refactoren
```

### Nesting depth
```
HARD LIMIT: Maximaal 3 niveaus diep
```

**Slecht:**
```php
if ($user) {
    if ($user->isActive()) {
        foreach ($items as $item) {
            if ($item->isValid()) {  // Te diep
                // ...
            }
        }
    }
}
```

**Goed:**
```php
if (!$user || !$user->isActive()) {
    return;
}

$validItems = array_filter($items, fn($item) => $item->isValid());
foreach ($validItems as $item) {
    // ...
}
```

### Early returns

Gebruik early returns om happy path links te houden:
```php
public function process(Request $request): Response
{
    if (!$request->isValid()) {
        return $this->badRequest();
    }

    if (!$this->isAuthorized($request)) {
        return $this->forbidden();
    }

    // Happy path hier — niet genest
    return $this->handleValidRequest($request);
}
```

---

## Domain Driven Design

### Layer structuur
```
src/
├── Controller/          # HTTP layer — alleen request/response mapping
├── Service/             # Application layer — use cases, orchestration
├── Domain/              # Business logic — entities, value objects, domain services
│   ├── Entity/          # Doctrine entities
│   ├── ValueObject/     # Immutable value objects
│   ├── Exception/       # Domain exceptions
│   └── Service/         # Pure domain logic
├── Infrastructure/      # External concerns
│   ├── Repository/      # Doctrine repositories
│   ├── External/        # Third-party API clients
│   └── Persistence/     # Database-specific code
└── DTO/                 # Data transfer objects
    ├── Request/         # Input DTOs
    └── Response/        # Output DTOs
```

### Controller rules

Controllers doen ALLEEN:
1. Request validatie (via DTO + Validator)
2. Service aanroepen
3. Response formatting
```php
// GOED
#[Route('/api/v1/items/{id}/events', methods: ['POST'])]
public function createEvent(
    string $id,
    CreateEventRequest $request,  // Auto-validated DTO
    EventService $eventService
): JsonResponse {
    $event = $eventService->createEvent($id, $request);
    
    return $this->json(['data' => $event], Response::HTTP_CREATED);
}

// SLECHT — business logic in controller
#[Route('/api/v1/items/{id}/events', methods: ['POST'])]
public function createEvent(string $id, Request $request): JsonResponse
{
    $item = $this->itemRepository->find($id);
    
    if ($request->get('type') === 'snoozed') {
        $maxDays = $this->templateProvider->getMaxSnoozeDays($item->getTemplateId());
        if ($daysUntil > $maxDays) {  // Business logic hoort hier niet
            // ...
        }
    }
    // ... meer logic die in service hoort
}
```

### Service rules

Services bevatten use case logic:
```php
// Application Service — orchestreert
class EventService
{
    public function createEvent(string $itemId, CreateEventRequest $request): EventResponse
    {
        $item = $this->itemRepository->findOrFail($itemId);
        
        $this->eventValidator->validate($item, $request);  // Delegeer validatie
        
        $event = $this->eventFactory->create($item, $request);  // Delegeer creatie
        
        $this->eventRepository->save($event);
        
        return EventResponse::fromEntity($event);
    }
}
```

### Entity rules

Entities zijn NIET anemic. Ze bevatten business logic die op hun eigen data werkt:
```php
// GOED — behavior in entity
class HouseholdItem
{
    public function getEffectiveLastDoneAt(): \DateTimeImmutable
    {
        return $this->cachedLastDoneAt
            ?? $this->userLastDoneAt
            ?? $this->assumedLastDoneAt;
    }

    public function isSuspended(): bool
    {
        return $this->cachedSuspendUntil !== null
            && $this->cachedSuspendUntil > new \DateTimeImmutable();
    }

    public function confirmAssumption(): void
    {
        if ($this->lastDoneSource === LastDoneSource::Assumed) {
            $this->lastDoneSource = LastDoneSource::Confirmed;
        }
    }
}

// SLECHT — anemic entity + external service
class HouseholdItem
{
    // Alleen getters/setters...
}

class ItemHelper
{
    public function getEffectiveLastDoneAt(HouseholdItem $item): \DateTimeImmutable
    {
        // Logic die in entity hoort
    }
}
```

### Value Objects

Gebruik value objects voor concepten met eigen validatie:
```php
// GOED
final readonly class PostalCode
{
    public function __construct(
        public string $value
    ) {
        if (!preg_match('/^\d{4}[A-Z]{2}$/', $value)) {
            throw new InvalidPostalCodeException($value);
        }
    }

    public static function fromString(string $value): self
    {
        $normalized = strtoupper(str_replace(' ', '', $value));
        return new self($normalized);
    }
}

// Gebruik
$household->setPostalCode(PostalCode::fromString($input));

// SLECHT — validatie overal verspreid
if (!preg_match('/^\d{4}[A-Z]{2}$/', $postalCode)) { ... }  // In controller
if (!preg_match('/^\d{4}[A-Z]{2}$/', $postalCode)) { ... }  // In service
if (!preg_match('/^\d{4}[A-Z]{2}$/', $postalCode)) { ... }  // In andere service
```

---

## Testing

### Test structuur
```
tests/
├── Unit/                # Pure unit tests — geen database, geen framework
│   ├── Domain/          # Entity en value object tests
│   └── Service/         # Service tests met mocks
├── Integration/         # Database tests
│   └── Repository/      # Repository tests
└── Functional/          # Full stack API tests
    └── Api/             # Endpoint tests
```

### Test naamgeving
```
test[MethodName][Scenario][ExpectedResult]

testCalculateStatus_WhenOverdue_ReturnsOverdueStatus
testCreateEvent_WithInvalidSnooze_ThrowsSnoozeTooLongException
testGetItems_FilteredByPack_ReturnsOnlyPackItems
```

### Unit test rules
```php
class ItemStatusServiceTest extends TestCase
{
    private ItemStatusService $service;
    private MockObject $templateProvider;

    protected function setUp(): void
    {
        $this->templateProvider = $this->createMock(PackTemplateProvider::class);
        $this->service = new ItemStatusService($this->templateProvider);
    }

    public function testCalculateStatus_WhenWithinInterval_ReturnsOk(): void
    {
        // Arrange
        $item = $this->createItemWithLastDone(days: 5, interval: 30);
        
        // Act
        $status = $this->service->calculateStatus($item);
        
        // Assert
        $this->assertEquals('ok', $status->status);
        $this->assertFalse($status->isPastDue);
    }
}
```

**Unit test regels:**
- Geen database
- Geen filesystem
- Geen network
- Mock alle dependencies
- Eén assert per test (of gerelateerde asserts)

### Functional test rules
```php
class EventsTest extends ApiTestCase
{
    public function testCreateDoneEvent_ReturnsCreatedWithUpdatedStatus(): void
    {
        // Given
        $household = $this->createHousehold();
        $user = $this->createUser($household);
        $item = $this->createItem($household, 'smoke_detector_test');
        $this->em->flush();

        // When
        $this->authAs($user);
        $response = $this->postJson(
            "/api/v1/items/{$item->getId()}/events",
            ['type' => 'done'],
            ['HTTP_IDEMPOTENCY_KEY' => 'test-key-123']
        );

        // Then
        $this->assertResponseStatusCode(201);
        $this->assertArrayHasKey('event', $response['data']);
        $this->assertEquals('done', $response['data']['event']['type']);
    }
}
```

**Functional test regels:**
- Given/When/Then structuur
- Database wordt gereset per test (transaction rollback)
- Test één scenario per test
- Helper methods voor setup (`createHousehold`, `createUser`, etc.)

### Test coverage targets
```
MINIMUM: 80% line coverage op Domain/ en Service/
TARGET:  90% line coverage
```

Niet testen:
- DTOs (geen logic)
- Framework boilerplate
- Getters/setters zonder logic

---

## Error Handling

### Exception hierarchy
```php
namespace App\Domain\Exception;

// Base
abstract class DomainException extends \RuntimeException
{
    abstract public function getErrorCode(): string;
    abstract public function getHttpStatusCode(): int;
}

// Specifieke exceptions
class EntityNotFoundException extends DomainException
{
    public function __construct(string $entityType, string $id)
    {
        parent::__construct("$entityType with ID $id not found");
    }

    public function getErrorCode(): string { return 'NOT_FOUND'; }
    public function getHttpStatusCode(): int { return 404; }
}

class SnoozeTooLongException extends DomainException
{
    public function __construct(int $requested, int $max)
    {
        parent::__construct("Snooze of $requested days exceeds maximum of $max");
    }

    public function getErrorCode(): string { return 'SNOOZE_TOO_LONG'; }
    public function getHttpStatusCode(): int { return 400; }
}
```

### Exception handler

Eén centrale handler die DomainExceptions converteert naar API responses:
```php
class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof DomainException) {
            $response = new JsonResponse([
                'error' => [
                    'code' => $exception->getErrorCode(),
                    'message' => $exception->getMessage(),
                ]
            ], $exception->getHttpStatusCode());

            $event->setResponse($response);
        }
    }
}
```

### Geen exception swallowing
```php
// SLECHT
try {
    $this->doSomething();
} catch (\Exception $e) {
    // Silently ignore
}

// SLECHT
try {
    $this->doSomething();
} catch (\Exception $e) {
    return null;  // Maskeert het probleem
}

// GOED
try {
    $this->doSomething();
} catch (SpecificException $e) {
    $this->logger->error('Failed to do something', ['exception' => $e]);
    throw new ApplicationException('Operation failed', previous: $e);
}
```

---

## Naming Conventions

### Classes

| Type | Convention | Voorbeeld |
|------|------------|-----------|
| Entity | Singular noun | `User`, `HouseholdItem` |
| Repository | Entity + Repository | `UserRepository` |
| Service | Noun + Service | `EventService`, `ItemStatusService` |
| Controller | Plural noun + Controller | `ItemsController`, `EventsController` |
| Exception | Description + Exception | `SnoozeTooLongException` |
| DTO | Purpose + Request/Response | `CreateEventRequest` |
| Value Object | Concept name | `PostalCode`, `EmailAddress` |
| Interface | Adjective of capability | `Identifiable`, `Timestampable` |

### Methods

| Type | Convention | Voorbeeld |
|------|------------|-----------|
| Query (returns data) | get/find/is/has/can | `getItems()`, `findByEmail()`, `isActive()` |
| Command (changes state) | verb | `activate()`, `deactivate()`, `confirm()` |
| Factory | create/from | `createFromRequest()`, `fromEntity()` |
| Conversion | to | `toArray()`, `toResponse()` |

### Variables
```php
// Collections zijn plural
$items = $this->itemRepository->findAll();
$activeUsers = $household->getActiveUsers();

// Singles zijn singular
$item = $this->itemRepository->find($id);
$user = $this->security->getUser();

// Booleans lezen als vraag
$isActive = $user->isActive();
$hasEvents = $item->getEvents()->count() > 0;
$canSnooze = $template->getMaxSnoozeDays() > 0;
```

---

## Dependency Injection

### Constructor injection only
```php
// GOED
class EventService
{
    public function __construct(
        private readonly EventRepository $eventRepository,
        private readonly ItemRepository $itemRepository,
        private readonly PackTemplateProvider $templateProvider,
    ) {}
}

// SLECHT — setter injection
class EventService
{
    private EventRepository $eventRepository;

    public function setEventRepository(EventRepository $repo): void
    {
        $this->eventRepository = $repo;
    }
}

// SLECHT — service locator
class EventService
{
    public function __construct(private ContainerInterface $container) {}

    public function doSomething(): void
    {
        $repo = $this->container->get(EventRepository::class);  // Hidden dependency
    }
}
```

### Dependency limits
```
SOFT LIMIT: 4 constructor dependencies
HARD LIMIT: 6 dependencies — daarna splitsen
```

Te veel dependencies = class doet te veel.

---

## Immutability

### DTOs zijn immutable
```php
final readonly class CreateEventRequest
{
    public function __construct(
        public EventType $type,
        public ?\DateTimeImmutable $doneAt = null,
        public ?\DateTimeImmutable $suspendUntil = null,
        public ?string $notes = null,
    ) {}
}
```

### Prefer DateTimeImmutable
```php
// GOED
private \DateTimeImmutable $createdAt;

public function getCreatedAt(): \DateTimeImmutable
{
    return $this->createdAt;
}

// SLECHT — mutable, kan extern gewijzigd worden
private \DateTime $createdAt;

public function getCreatedAt(): \DateTime
{
    return $this->createdAt;  // Caller kan dit muteren
}
```

### Collection returns
```php
// GOED — return nieuwe collection of array
public function getActiveItems(): array
{
    return $this->items->filter(fn($i) => $i->isActive())->toArray();
}

// SLECHT — interne collection blootgesteld
public function getItems(): Collection
{
    return $this->items;  // Caller kan add/remove doen
}
```

---

## Comments & Documentation

### Wanneer wel comments
```php
// Business rule die niet obvious is
// Rookmelders moeten elke 30 dagen getest worden volgens NEN 2555
private const SMOKE_DETECTOR_INTERVAL = 30;

// Workaround voor external limitation
// PostgreSQL CITEXT extension vereist lowercase comparison voor index hit
$email = strtolower($email);

// Complex algoritme uitleg
// Status berekening: zie docs/wanneer-moet-ik-weer-datamodel.md §7
```

### Wanneer geen comments
```php
// SLECHT — comment herhaalt code
// Get the user
$user = $this->getUser();

// SLECHT — comment in plaats van goede naam
// Check if item can be snoozed
if ($days <= $max && $type !== 'external') { ... }

// GOED — code spreekt voor zich
if ($this->canSnooze($item, $days)) { ... }
```

### PHPDoc rules

Alleen waar types niet voldoende zijn:
```php
// GOED — adds value
/**
 * @param positive-int $days Number of days to snooze
 * @throws SnoozeTooLongException When days exceeds max_snooze_days
 */
public function snooze(HouseholdItem $item, int $days): Event

// SLECHT — herhaalt type hints
/**
 * @param HouseholdItem $item The item
 * @param int $days The days
 * @return Event The event
 */
public function snooze(HouseholdItem $item, int $days): Event
```

---

## Git Commits

### Commit message format
```
<type>: <description>

[optional body]

[optional footer]
```

**Types:**
- `feat`: Nieuwe feature
- `fix`: Bug fix
- `refactor`: Code refactor zonder behavior change
- `test`: Tests toevoegen/wijzigen
- `docs`: Documentatie
- `chore`: Build, config, dependencies

**Voorbeelden:**
```
feat: add POST /items/{id}/events endpoint

fix: SNOOZE_TOO_LONG now returns correct max_days in details

refactor: extract ItemStatusService from ItemService

test: add functional tests for event creation
```

### Commit scope

- Eén logische wijziging per commit
- Tests samen met implementatie (niet apart)
- Migrations samen met entity changes

---

## Checklist voor Code Review (Backend)

Voordat code klaar is voor review:

- [ ] Alle tests groen
- [ ] Geen method > 20 regels
- [ ] Geen class > 300 regels
- [ ] Geen nesting > 3 levels
- [ ] Max 6 constructor dependencies
- [ ] Exceptions hebben correcte HTTP codes
- [ ] DTOs zijn readonly
- [ ] Geen business logic in controllers
- [ ] Nieuwe features hebben tests
- [ ] PHPStan level 8 zonder errors

---

## Frontend (React Native / Expo)

De frontend volgt dezelfde kernprincipes maar met aangepaste limieten en patterns.

### File Limits

```
Components: 150 lines max
Hooks: 100 lines max
Stores: 100 lines max
```

Kleiner dan backend omdat React components typisch meer gefocust zijn.

### Component Rules

```tsx
// Prefer functional components with hooks
export function ItemCard({ item, onPress }: ItemCardProps) {
  // Early returns for edge cases
  if (!item) return null;

  // Hooks at top
  const { isOnline } = useNetworkStatus();

  // Event handlers prefixed with 'handle'
  const handlePress = () => onPress?.(item.id);

  // Render
  return (/* JSX */);
}
```

**Component regels:**
- Eén component per file (behalve kleine interne helpers)
- Props interface gedefinieerd boven component
- Event handlers prefixed met `handle` (bijv. `handlePress`)
- Hooks altijd bovenaan de component
- Early returns voor edge cases

### State Management

**Server state: TanStack Query**
```tsx
export function useItems(householdId: string) {
  return useQuery({
    queryKey: queryKeys.householdItems(householdId),
    queryFn: () => itemsApi.getItems(householdId),
  });
}
```

**Client state: Zustand**
```tsx
export const useAuthStore = create<AuthStore>((set) => ({
  token: null,

  setToken: async (token) => {
    await SecureStore.setItemAsync(TOKEN_KEY, token);
    set({ token });
  },
}));
```

**HARD RULE:** Geen mixing! Server data hoort in TanStack Query, niet in Zustand.

### API Layer

**Alle endpoints unwrappen de response:**
```tsx
// GOED — returns T, not ApiResponse<T>
export const itemsApi = {
  getItems: (householdId: string) =>
    apiClient.get<HouseholdItem[]>(`/households/${householdId}/items`),
};

// Client unwrapt { data: T } automatisch
```

**Throw ApiException on errors:**
```tsx
if (!response.ok) {
  const error = await response.json();
  throw new ApiException(error.error.code, error.error.message, response.status);
}
```

**Idempotency keys per mutation:**
```tsx
const createMutation = useMutation({
  mutationFn: () => eventsApi.create(itemId, {
    type: 'done',
    idempotencyKey: generateIdempotencyKey(),  // Nieuw per call
  }),
});
```

### Styling

**NativeWind (Tailwind) only:**
```tsx
// GOED
<View className="flex-row items-center p-4 bg-gray-50">

// SLECHT — alleen als NativeWind niet kan
const styles = StyleSheet.create({
  container: { flexDirection: 'row' }
});
```

**Custom kleuren in tailwind.config.js:**
```js
colors: {
  primary: { 500: '#2563eb', ... },
  success: { 500: '#16a34a', ... },
}
```

### Hook Pattern

```tsx
// Custom hook voor data fetching
export function useItem(itemId: string) {
  return useQuery({
    queryKey: queryKeys.item(itemId),
    queryFn: () => itemsApi.getItem(itemId),
    enabled: !!itemId,
  });
}

// Custom hook voor mutations met optimistic updates
export function useCreateEvent(itemId: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (request: CreateEventRequest) =>
      eventsApi.create(itemId, request),
    onMutate: async (newEvent) => {
      // Optimistic update logic
    },
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.items });
    },
  });
}
```

### Checklist voor Code Review (Frontend)

Voordat code klaar is voor review:

- [ ] TypeScript strict mode geen errors
- [ ] Geen component > 150 regels
- [ ] Geen hook > 100 regels
- [ ] Geen nesting > 3 levels
- [ ] Server state in TanStack Query, client state in Zustand
- [ ] Idempotency keys bij mutaties
- [ ] NativeWind voor styling (geen inline StyleSheet)
- [ ] Event handlers prefixed met `handle`
- [ ] Props interface gedefinieerd
