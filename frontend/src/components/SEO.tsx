import { Helmet } from 'react-helmet-async'

interface SEOProps {
  title?: string
  description?: string
  canonicalUrl?: string
  ogImage?: string
  ogType?: 'website' | 'product'
  noIndex?: boolean
  jsonLd?: object | object[]
}

const SITE_NAME = 'ShopQ'
const DEFAULT_DESCRIPTION =
  'Volg productprijzen en ontvang alerts bij prijsdalingen. Vergelijk prijzen van Nederlandse webshops.'
const DEFAULT_OG_IMAGE = 'https://shopq.nl/og-image.png'
const SITE_URL = 'https://shopq.nl'

export function SEO({
  title,
  description = DEFAULT_DESCRIPTION,
  canonicalUrl,
  ogImage = DEFAULT_OG_IMAGE,
  ogType = 'website',
  noIndex = false,
  jsonLd,
}: SEOProps) {
  const fullTitle = title ? `${title} | ${SITE_NAME}` : `${SITE_NAME} - Prijsmonitor & Alerts`

  return (
    <Helmet>
      <title>{fullTitle}</title>
      <meta name="description" content={description} />

      {noIndex && <meta name="robots" content="noindex, nofollow" />}

      {canonicalUrl && <link rel="canonical" href={canonicalUrl} />}

      {/* Open Graph */}
      <meta property="og:site_name" content={SITE_NAME} />
      <meta property="og:title" content={fullTitle} />
      <meta property="og:description" content={description} />
      <meta property="og:type" content={ogType} />
      <meta property="og:image" content={ogImage} />
      {canonicalUrl && <meta property="og:url" content={canonicalUrl} />}
      <meta property="og:locale" content="nl_NL" />

      {/* Twitter Card */}
      <meta name="twitter:card" content="summary_large_image" />
      <meta name="twitter:title" content={fullTitle} />
      <meta name="twitter:description" content={description} />
      <meta name="twitter:image" content={ogImage} />

      {/* JSON-LD Structured Data */}
      {jsonLd && (
        <script type="application/ld+json">
          {JSON.stringify(Array.isArray(jsonLd) ? jsonLd : jsonLd)}
        </script>
      )}
    </Helmet>
  )
}

// Helper to create WebSite schema
export function createWebSiteSchema() {
  return {
    '@context': 'https://schema.org',
    '@type': 'WebSite',
    name: SITE_NAME,
    url: SITE_URL,
    description: DEFAULT_DESCRIPTION,
    potentialAction: {
      '@type': 'SearchAction',
      target: {
        '@type': 'EntryPoint',
        urlTemplate: `${SITE_URL}/?search={search_term_string}`,
      },
      'query-input': 'required name=search_term_string',
    },
  }
}

// Helper to create Product schema
export function createProductSchema(product: {
  id: number
  productName: string
  url: string
  imageUrl?: string | null
  currentPrice?: string | null
  currency?: string
  description?: string
}) {
  const schema: Record<string, unknown> = {
    '@context': 'https://schema.org',
    '@type': 'Product',
    name: product.productName,
    url: product.url,
  }

  if (product.imageUrl) {
    schema.image = product.imageUrl
  }

  if (product.description) {
    schema.description = product.description
  }

  if (product.currentPrice) {
    schema.offers = {
      '@type': 'Offer',
      price: product.currentPrice,
      priceCurrency: product.currency || 'EUR',
      availability: 'https://schema.org/InStock',
      url: product.url,
    }
  }

  return schema
}

// Helper to create ItemList schema for product feeds
export function createItemListSchema(
  products: Array<{
    id: number
    productName: string
    url: string
    imageUrl?: string | null
    currentPrice?: string | null
    currency?: string
  }>,
  listName: string = 'Productoverzicht'
) {
  return {
    '@context': 'https://schema.org',
    '@type': 'ItemList',
    name: listName,
    numberOfItems: products.length,
    itemListElement: products.slice(0, 10).map((product, index) => ({
      '@type': 'ListItem',
      position: index + 1,
      item: {
        '@type': 'Product',
        name: product.productName,
        url: product.url,
        ...(product.imageUrl && { image: product.imageUrl }),
        ...(product.currentPrice && {
          offers: {
            '@type': 'Offer',
            price: product.currentPrice,
            priceCurrency: product.currency || 'EUR',
          },
        }),
      },
    })),
  }
}

// Helper to create BreadcrumbList schema
export function createBreadcrumbSchema(
  items: Array<{ name: string; url: string }>
) {
  return {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: items.map((item, index) => ({
      '@type': 'ListItem',
      position: index + 1,
      name: item.name,
      item: item.url,
    })),
  }
}

// Helper to create Organization schema
export function createOrganizationSchema() {
  return {
    '@context': 'https://schema.org',
    '@type': 'Organization',
    name: SITE_NAME,
    url: SITE_URL,
    logo: `${SITE_URL}/logo.png`,
    sameAs: [],
  }
}
