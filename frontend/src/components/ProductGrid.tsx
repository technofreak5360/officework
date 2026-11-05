import { Link } from 'react-router'
import type { Product } from '../api/types'
import { formatPrice } from '../format'
import { ProductRating } from './ProductRating'

function ProductTile({ product }: { product: Product }) {
  return (
    <Link to={`/products/${product.id}`} className="tile">
      <img
        className="tile__image"
        src={product.imageUrl}
        alt=""
        loading="lazy"
        width="200"
        height="200"
      />
      <span className="tile__title">{product.title}</span>
      <span className="tile__price">{formatPrice(product.price)}</span>
      <ProductRating rating={product.rating} />
    </Link>
  )
}

export function ProductGrid({ products }: { products: Product[] }) {
  return (
    <ul className="grid">
      {products.map((product) => (
        <li key={product.id}>
          <ProductTile product={product} />
        </li>
      ))}
    </ul>
  )
}
