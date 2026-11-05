import { useSearchParams } from 'react-router'
import { useProductSearch } from '../api/catalogue'
import { ProductGrid } from '../components/ProductGrid'
import { ErrorState, LoadingState } from '../components/QueryStates'

export function SearchPage() {
  const [searchParams] = useSearchParams()
  const term = (searchParams.get('q') ?? '').trim()
  const { data: products, isLoading, isError, refetch } = useProductSearch(term)

  if (term === '') {
    return <h1>Enter a search term to find products</h1>
  }

  return (
    <>
      <title>{`Search: ${term} | Product Catalogue`}</title>
      <h1>Results for “{term}”</h1>
      {isLoading && <LoadingState />}
      {isError && <ErrorState onRetry={() => refetch()} />}
      {products?.length === 0 && (
        <p className="state" role="status">
          No products match your search.
        </p>
      )}
      {products && products.length > 0 && <ProductGrid products={products} />}
    </>
  )
}
