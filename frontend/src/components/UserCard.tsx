import { Link } from 'react-router-dom'
import FollowButton from './FollowButton'
import type { DiscoverUser } from '../hooks/useDiscover'

interface UserCardProps {
  user: DiscoverUser
  showFollowButton?: boolean
}

export default function UserCard({ user, showFollowButton = true }: UserCardProps) {
  return (
    <div className="bg-white rounded-lg border border-gray-200 p-4 hover:shadow-md transition-shadow">
      <Link to={`/u/${user.username}`} className="block">
        <div className="flex items-center gap-3 mb-3">
          <div className="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0">
            <span className="text-lg font-bold text-primary-600">
              {user.username.charAt(0).toUpperCase()}
            </span>
          </div>
          <div className="min-w-0">
            <h3 className="font-medium text-gray-900 truncate">@{user.username}</h3>
            <p className="text-sm text-gray-500">
              {user.productCount} {user.productCount === 1 ? 'product' : 'producten'}
            </p>
          </div>
        </div>
      </Link>

      <div className="flex items-center justify-between">
        <div className="text-sm text-gray-500">
          <span className="font-medium text-gray-900">{user.followerCount}</span>{' '}
          {user.followerCount === 1 ? 'volger' : 'volgers'}
        </div>
        {showFollowButton && (
          <FollowButton
            userId={user.id}
            username={user.username}
            initialFollowerCount={user.followerCount}
            size="sm"
          />
        )}
      </div>
    </div>
  )
}
