import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { createMemoryRouter, RouterProvider } from 'react-router'
import { vi } from 'vitest'
import type { Product } from '../api/types'
import { routes } from '../routes'

export function product(overrides: Partial<Product> & { id: number }): Product {
  return {
    title: `Product ${overrides.id}`,
    price: 10,
    description: `Description of product ${overrides.id}`,
    category: { name: "men's clothing", slug: 'mens-clothing' },
    imageUrl: `https://images.test/${overrides.id}.png`,
    rating: { rate: 4, count: 100 },
    ...overrides,
  }
}

export const CATEGORIES = [
  { name: "men's clothing", slug: 'mens-clothing' },
  { name: 'electronics', slug: 'electronics' },
]

type MockResponse = { status: number; body: unknown }

/** Wraps a value in the API's success envelope. */
export function ok(data: unknown): MockResponse {
  return { status: 200, body: { data } }
}

/** A response whose body isn't JSON, like a proxy error page. */
export function failure(status: number, body: unknown = 'Server error'): MockResponse {
  return { status, body }
}

/**
 * Stubs fetch, the app's only way out to the network, and returns a 404 for any
 * URL not listed. Routes can be changed mid-test, e.g. to simulate a recovery.
 */
export function mockApi(routes: Record<string, MockResponse>) {
  const allRoutes: Record<string, MockResponse> = { '/api/categories': ok(CATEGORIES), ...routes }

  const fetchMock = vi.fn(async (url: string) => {
    const { status, body } = allRoutes[url] ?? failure(404, { message: 'Not found' })

    return new Response(typeof body === 'string' ? body : JSON.stringify(body), { status })
  })

  vi.stubGlobal('fetch', fetchMock)

  return {
    routes: allRoutes,
    calledUrls: () => fetchMock.mock.calls.map(([url]) => url),
  }
}

/** Renders the whole app at a URL, so tests follow the same journeys users do. */
export function renderApp(path: string) {
  const router = createMemoryRouter(routes, { initialEntries: [path] })
  const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } })

  render(
    <QueryClientProvider client={queryClient}>
      <RouterProvider router={router} />
    </QueryClientProvider>,
  )

  return { router, user: userEvent.setup() }
}
