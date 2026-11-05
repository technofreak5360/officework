import { useFeaturedProducts } from '../api/catalogue'
import { ProductGrid } from '../components/ProductGrid'
import { ErrorState, LoadingState } from '../components/QueryStates'

export function HomePage() {
  const { data: products, isPending, isError, refetch } = useFeaturedProducts()

  return (
    <>
      <h1>Top rated products</h1>
      {isPending && <LoadingState />}
      {isError && <ErrorState onRetry={() => refetch()} />}
      {products && <ProductGrid products={products} />}
    </>
  )
}
