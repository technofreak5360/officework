import type { Product } from '../api/types'

export function ProductRating({ rating }: { rating: Product['rating'] }) {
  return (
    <span className="rating">
      <span aria-hidden="true">★ </span>
      {rating.rate.toFixed(1)} <span className="muted">({rating.count} reviews)</span>
    </span>
  )
}
