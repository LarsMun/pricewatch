# Changelog 12 januari 2026

## Overzicht

Deze sessie bevatte de implementatie van SEO-ondersteuning, publieke collectie-deling, en UI-verbeteringen aan het dashboard.

---

## 1. SEO Implementatie

### Nieuwe packages
- `react-helmet-async` voor dynamische meta tags

### Nieuwe bestanden
- `frontend/src/components/SEO.tsx` - SEO component met JSON-LD helpers

### Aangepaste bestanden
- `frontend/src/index.tsx` - HelmetProvider wrapper toegevoegd
- `frontend/src/pages/FeedPage.tsx` - SEO met ItemList schema
- `frontend/src/pages/PublicProductPage.tsx` - SEO met Product schema
- `frontend/src/pages/UserProfilePage.tsx` - SEO met profielpagina meta tags
- `frontend/public/index.html` - Standaard meta tags
- `frontend/public/robots.txt` - Sitemap referentie

### Backend
- `backend/src/Controller/PublicController.php` - `/sitemap.xml` endpoint toegevoegd

### JSON-LD Schemas
- **Product**: Voor individuele productpagina's
- **ItemList**: Voor feed/lijstpagina's
- **BreadcrumbList**: Voor navigatiepad
- **WebSite**: Voor homepage

---

## 2. Publieke Collectie Sharing

### Functionaliteit
Gebruikers kunnen nu collecties delen via een publieke URL: `/u/{username}/{collection-slug}`

### Database wijzigingen
- `Collection` entity: `isPublic` boolean veld toegevoegd
- Migration: `Version20250111XXXXXX` voor is_public kolom

### Backend wijzigingen

**Entity/Collection.php**
```php
#[ORM\Column(type: Types::BOOLEAN)]
private bool $isPublic = false;

public function getSlug(): string
{
    return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $this->name), '-'));
}
```

**Controller/CollectionController.php**
- `isPublic` veld in update endpoint
- `shareUrl` in serialisatie (alleen wanneer openbaar + username ingesteld)

**Service/PublicFeedService.php**
- `getUserCollection()` methode voor ophalen publieke collectie
- `getUserProfile()` retourneert nu ook publieke collecties

**Controller/PublicController.php**
- Nieuw endpoint: `GET /api/public/users/{username}/collections/{slug}`

### Frontend wijzigingen

**Nieuwe pagina**
- `pages/UserCollectionPage.tsx` - Publieke collectiepagina met SEO

**Types**
```typescript
interface Collection {
  // ... bestaande velden
  isPublic: boolean
  shareUrl: string | null
}

interface UpdateCollectionRequest {
  // ... bestaande velden
  isPublic?: boolean
}
```

**Routing**
- Route toegevoegd: `/u/:username/:collectionSlug`

---

## 3. Dashboard UI Verbeteringen

### Bookmarklet naar header
- Bookmarklet link verplaatst van actieknoppen naar hoofdnavigatie
- Zowel desktop als mobiel menu

### Check alle - Admin only
- "Check alle" knop alleen zichtbaar voor gebruikers met `ROLE_ADMIN`

### Collectie delen met toggle en native share

**Toggle switch**
- Groen met oog-icoon = openbaar
- Grijs met doorstreept oog = privé
- Klikken wisselt de zichtbaarheid

**Deel knop**
- Gebruikt Web Share API (`navigator.share()`)
- Native share sheet op iOS/Android
- Fallback: kopieert link naar klembord
- Uitgeschakeld wanneer collectie privé is

### Code (DashboardPage.tsx)

```typescript
const handleShare = async () => {
  if (!selectedCollection?.shareUrl) return

  const shareUrl = window.location.origin + selectedCollection.shareUrl
  const shareData = {
    title: selectedCollection.name,
    text: `Bekijk mijn collectie "${selectedCollection.name}" op ShopQ`,
    url: shareUrl,
  }

  if (navigator.share && navigator.canShare?.(shareData)) {
    try {
      await navigator.share(shareData)
    } catch (err) {
      if ((err as Error).name !== 'AbortError') {
        await navigator.clipboard.writeText(shareUrl)
      }
    }
  } else {
    await navigator.clipboard.writeText(shareUrl)
    alert('Link gekopieerd naar klembord!')
  }
}

const handleTogglePublic = () => {
  if (selectedCollection) {
    updateCollection.mutate({
      id: selectedCollection.id,
      data: { isPublic: !selectedCollection.isPublic }
    })
  }
}
```

---

## 4. React Query Cache Fix

### Probleem
UI updatet niet direct na collectie-update (isPublic toggle)

### Oplossing
`useUpdateCollection` hook aangepast om direct de cache bij te werken met server response:

```typescript
onSuccess: (updatedCollection, variables) => {
  const previousCollections = queryClient.getQueryData<Collection[]>(['collections'])
  if (previousCollections) {
    queryClient.setQueryData<Collection[]>(['collections'],
      previousCollections.map(c =>
        c.id === variables.id ? updatedCollection : c
      )
    )
  }
  queryClient.invalidateQueries({ queryKey: ['collections', variables.id] })
}
```

---

## 5. Bug Fixes

### React Hooks volgorde (PublicProductPage.tsx)
- `useMemo` hooks verplaatst voor early returns
- Fix voor React hooks rules violation

### Docker container package issue
- `react-helmet-async` niet beschikbaar in container
- Oplossing: Docker volume verwijderen en rebuilden

---

## Commits

```
652ce4d Use native Web Share API for collection sharing
3a55152 Fix collection update not reflecting in UI immediately
a3aaf49 Simplify share popover flow
9a5bd7b Improve share popover with public/private state feedback
edbf7b2 Add collection sharing UI to dashboard
de77269 Add public collection sharing feature
3960198 Fix React hooks order in PublicProductPage
3687d1a Add SEO support with JSON-LD structured data
```

---

## Productie commando's

### Database migratie
```bash
docker compose exec pricewatch-php php bin/console doctrine:migrations:migrate --no-interaction
```

### Categorie backfill (indien nodig)
```bash
docker compose exec pricewatch-php php bin/console app:backfill-categories
```
