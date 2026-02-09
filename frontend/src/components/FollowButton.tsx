import { useState } from 'react'
import { useAuth } from '../contexts/AuthContext'
import { useFollowingIds, useFollowUser, useUnfollowUser } from '../hooks/useFollow'
import { Link } from 'react-router-dom'

interface FollowButtonProps {
  userId: number
  username: string
  initialFollowerCount: number
  onFollowerCountChange?: (newCount: number) => void
  size?: 'sm' | 'md'
}

export default function FollowButton({
  userId,
  username: _username,
  initialFollowerCount: _initialFollowerCount,
  onFollowerCountChange,
  size = 'md',
}: FollowButtonProps) {
  const { user, token } = useAuth()
  const { data: followingData, isLoading: isLoadingFollowingIds } = useFollowingIds()
  const followMutation = useFollowUser()
  const unfollowMutation = useUnfollowUser()
  const [isHovering, setIsHovering] = useState(false)

  // Don't render if viewing own profile
  if (user?.id === userId) {
    return null
  }

  // Not logged in - show login prompt button
  if (!token) {
    return (
      <Link
        to="/login"
        className={`inline-flex items-center justify-center font-medium rounded-lg transition-colors ${
          size === 'sm'
            ? 'px-3 py-1.5 text-sm'
            : 'px-4 py-2'
        } bg-primary-600 text-white hover:bg-primary-700`}
      >
        Volgen
      </Link>
    )
  }

  const isFollowing = followingData?.followingIds.includes(userId) ?? false
  const isLoading = isLoadingFollowingIds || followMutation.isPending || unfollowMutation.isPending

  const handleClick = async () => {
    if (isLoading) return

    try {
      if (isFollowing) {
        const result = await unfollowMutation.mutateAsync(userId)
        onFollowerCountChange?.(result.followerCount)
      } else {
        const result = await followMutation.mutateAsync(userId)
        onFollowerCountChange?.(result.followerCount)
      }
    } catch (error) {
      console.error('Follow/unfollow error:', error)
    }
  }

  const buttonClasses = size === 'sm'
    ? 'px-3 py-1.5 text-sm'
    : 'px-4 py-2'

  if (isLoading && !isFollowing) {
    return (
      <button
        disabled
        className={`inline-flex items-center justify-center font-medium rounded-lg ${buttonClasses} bg-gray-200 text-gray-400 cursor-not-allowed`}
      >
        <svg className="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
          <circle
            className="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            strokeWidth="4"
          />
          <path
            className="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
          />
        </svg>
        Laden...
      </button>
    )
  }

  if (isFollowing) {
    return (
      <button
        onClick={handleClick}
        onMouseEnter={() => setIsHovering(true)}
        onMouseLeave={() => setIsHovering(false)}
        disabled={isLoading}
        className={`inline-flex items-center justify-center font-medium rounded-lg transition-colors ${buttonClasses} ${
          isHovering
            ? 'bg-red-50 text-red-600 border border-red-200 hover:bg-red-100'
            : 'bg-gray-100 text-gray-700 border border-gray-200 hover:bg-gray-200'
        } ${isLoading ? 'cursor-not-allowed opacity-75' : ''}`}
      >
        {isLoading ? (
          <svg className="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
            <circle
              className="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              strokeWidth="4"
            />
            <path
              className="opacity-75"
              fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
            />
          </svg>
        ) : isHovering ? (
          'Ontvolgen'
        ) : (
          'Volgend'
        )}
      </button>
    )
  }

  return (
    <button
      onClick={handleClick}
      disabled={isLoading}
      className={`inline-flex items-center justify-center font-medium rounded-lg transition-colors ${buttonClasses} bg-primary-600 text-white hover:bg-primary-700 ${
        isLoading ? 'cursor-not-allowed opacity-75' : ''
      }`}
    >
      {isLoading ? (
        <svg className="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
          <circle
            className="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            strokeWidth="4"
          />
          <path
            className="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
          />
        </svg>
      ) : (
        'Volgen'
      )}
    </button>
  )
}
