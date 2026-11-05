import { useParams } from 'react-router'
import { useCategoryListing } from '../api/catalogue'
import { isNotFound } from '../api/client'
import { ProductGrid } from '../components/ProductGrid'
import { ErrorState, LoadingState } from '../components/QueryStates'
import { NotFoundPage } from './NotFoundPage'

export function CategoryPage() {
  const { slug = '' } = useParams()
  const { data: listing, isPending, error, refetch } = useCategoryListing(slug)

  if (isPending) {
    return <LoadingState />
  }

  if (isNotFound(error)) {
    return <NotFoundPage message="We couldn't find that category." />
  }

  if (!listing) {
    return <ErrorState onRetry={() => refetch()} />
  }

  return (
    <>
      <title>{`${listing.category.name} | Product Catalogue`}</title>
      <h1 className="capitalize">{listing.category.name}</h1>

      <section aria-labelledby="top-rated-heading">
        <h2 id="top-rated-heading">Top rated</h2>
        <ProductGrid products={listing.topRated} />
      </section>

      {listing.others.length > 0 && (
        <section aria-labelledby="others-heading">
          <h2 id="others-heading">More in this category</h2>
          <ProductGrid products={listing.others} />
        </section>
      )}
    </>
  )
}
