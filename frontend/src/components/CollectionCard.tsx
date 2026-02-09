import { Link } from 'react-router-dom'
import type { DiscoverCollection } from '../hooks/useDiscover'

interface CollectionCardProps {
  collection: DiscoverCollection
}

export default function CollectionCard({ collection }: CollectionCardProps) {
  return (
    <Link
      to={`/u/${collection.user.username}/${collection.slug}`}
      className="block bg-white rounded-lg border border-gray-200 overflow-hidden hover:shadow-md transition-shadow"
    >
      {/* Thumbnail */}
      <div className="aspect-video bg-gray-100">
        {collection.thumbnailUrl ? (
          <img
            src={collection.thumbnailUrl}
            alt={collection.name}
            className="w-full h-full object-cover"
            loading="lazy"
          />
        ) : (
          <div className="w-full h-full flex items-center justify-center text-gray-400">
            <svg className="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={1}
                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
              />
            </svg>
          </div>
        )}
      </div>

      {/* Content */}
      <div className="p-4">
        <h3 className="font-medium text-gray-900 line-clamp-1">{collection.name}</h3>
        {collection.description && (
          <p className="text-sm text-gray-500 mt-1 line-clamp-2">{collection.description}</p>
        )}
        <div className="mt-3 flex items-center justify-between text-sm">
          <span className="text-gray-500">
            {collection.productCount} {collection.productCount === 1 ? 'product' : 'producten'}
          </span>
          <span className="text-gray-400">@{collection.user.username}</span>
        </div>
      </div>
    </Link>
  )
}
