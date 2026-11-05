import { useQuery } from '@tanstack/react-query'
import { getData } from './client'
import type { Category, CategoryListing, Product } from './types'

export function useFeaturedProducts() {
  return useQuery({
    queryKey: ['products', 'featured'],
    queryFn: ({ signal }) => getData<Product[]>('/products/featured', signal),
  })
}

export function useCategories() {
  return useQuery({
    queryKey: ['categories'],
    queryFn: ({ signal }) => getData<Category[]>('/categories', signal),
  })
}

export function useCategoryListing(slug: string) {
  return useQuery({
    queryKey: ['categories', slug, 'products'],
    queryFn: ({ signal }) =>
      getData<CategoryListing>(`/categories/${encodeURIComponent(slug)}/products`, signal),
  })
}

export function useProductSearch(term: string) {
  return useQuery({
    queryKey: ['products', 'search', term],
    queryFn: ({ signal }) =>
      getData<Product[]>(`/products?search=${encodeURIComponent(term)}`, signal),
    enabled: term !== '',
  })
}

export function useProduct(id: string) {
  return useQuery({
    queryKey: ['products', id],
    queryFn: ({ signal }) => getData<Product>(`/products/${encodeURIComponent(id)}`, signal),
  })
}
