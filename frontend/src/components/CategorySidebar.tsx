import { useState } from 'react'
import type { Category } from '../types'

interface CategorySidebarProps {
  categories: Category[]
  selectedCategory?: string
  onSelectCategory: (slug: string | undefined) => void
}

function CategoryItem({
  category,
  selectedCategory,
  onSelectCategory,
  level = 0,
}: {
  category: Category
  selectedCategory?: string
  onSelectCategory: (slug: string | undefined) => void
  level?: number
}) {
  const [isExpanded, setIsExpanded] = useState(
    // Auto-expand if this category or a child is selected
    selectedCategory === category.slug ||
      category.children.some((c) => c.slug === selectedCategory)
  )

  const hasChildren = category.children.length > 0
  const isSelected = selectedCategory === category.slug

  return (
    <div>
      <div className="flex items-center">
        {hasChildren && (
          <button
            onClick={() => setIsExpanded(!isExpanded)}
            className="w-6 h-6 flex items-center justify-center text-gray-400 hover:text-gray-600"
          >
            <svg
              className={`w-4 h-4 transition-transform ${isExpanded ? 'rotate-90' : ''}`}
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M9 5l7 7-7 7"
              />
            </svg>
          </button>
        )}
        <button
          onClick={() => onSelectCategory(isSelected ? undefined : category.slug)}
          className={`flex-1 flex items-center gap-2 px-2 py-1.5 rounded-lg text-sm text-left ${
            isSelected
              ? 'bg-blue-50 text-blue-700 font-medium'
              : 'text-gray-600 hover:bg-gray-50'
          } ${!hasChildren ? 'ml-6' : ''}`}
        >
          {category.icon && <span>{category.icon}</span>}
          <span className="flex-1">{category.name}</span>
          {category.productCount > 0 && (
            <span className="text-gray-400 text-xs">({category.productCount})</span>
          )}
        </button>
      </div>
      {hasChildren && isExpanded && (
        <div className="ml-4 mt-1 space-y-0.5">
          {category.children.map((child) => (
            <CategoryItem
              key={child.id}
              category={child}
              selectedCategory={selectedCategory}
              onSelectCategory={onSelectCategory}
              level={level + 1}
            />
          ))}
        </div>
      )}
    </div>
  )
}

export default function CategorySidebar({
  categories,
  selectedCategory,
  onSelectCategory,
}: CategorySidebarProps) {
  return (
    <div className="bg-white rounded-lg border border-gray-200 p-4">
      <h2 className="font-semibold text-gray-900 mb-3">Categorieen</h2>
      <button
        onClick={() => onSelectCategory(undefined)}
        className={`block w-full text-left px-3 py-2 rounded-lg text-sm mb-2 ${
          !selectedCategory ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-600 hover:bg-gray-50'
        }`}
      >
        Alle categorieen
      </button>
      <div className="space-y-0.5">
        {categories.map((category) => (
          <CategoryItem
            key={category.id}
            category={category}
            selectedCategory={selectedCategory}
            onSelectCategory={onSelectCategory}
          />
        ))}
      </div>
    </div>
  )
}
