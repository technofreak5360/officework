import { Link, useParams } from 'react-router'
import { useProduct } from '../api/catalogue'
import { isNotFound } from '../api/client'
import { ProductRating } from '../components/ProductRating'
import { ErrorState, LoadingState } from '../components/QueryStates'
import { formatPrice } from '../format'
import { NotFoundPage } from './NotFoundPage'

export function ProductPage() {
  const { id = '' } = useParams()
  const { data: product, isPending, error, refetch } = useProduct(id)

  if (isPending) {
    return <LoadingState />
  }

  if (isNotFound(error)) {
    return <NotFoundPage message="We couldn't find that product." />
  }

  if (!product) {
    return <ErrorState onRetry={() => refetch()} />
  }

  return (
    <article className="product">
      <title>{`${product.title} | Product Catalogue`}</title>
      <img className="product__image" src={product.imageUrl} alt={product.title} />
      <div className="product__details">
        <Link to={`/category/${product.category.slug}`} className="capitalize">
          {product.category.name}
        </Link>
        <h1>{product.title}</h1>
        <p className="product__price">{formatPrice(product.price)}</p>
        <ProductRating rating={product.rating} />
        <p>{product.description}</p>
      </div>
    </article>
  )
}
