import { screen, within } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { failure, mockApi, ok, product, renderApp } from './helpers'

const JACKET = product({
  id: 3,
  title: 'Cotton Jacket',
  price: 55.99,
  description: 'Great outerwear',
  rating: { rate: 4.7, count: 500 },
})

describe('product page', () => {
  it('opens from a product tile and shows the details', async () => {
    mockApi({
      '/api/products/featured': ok([JACKET]),
      '/api/products/3': ok(JACKET),
    })
    const { user, router } = renderApp('/')

    await user.click(await screen.findByRole('link', { name: /Cotton Jacket/ }))

    expect(router.state.location.pathname).toBe('/products/3')
    expect(await screen.findByRole('heading', { level: 1, name: 'Cotton Jacket' })).toBeInTheDocument()

    const details = within(screen.getByRole('article'))
    expect(details.getByText('$55.99')).toBeInTheDocument()
    expect(details.getByText('Great outerwear')).toBeInTheDocument()
    expect(details.getByText('(500 reviews)')).toBeInTheDocument()
    expect(details.getByRole('img', { name: 'Cotton Jacket' })).toHaveAttribute('src', JACKET.imageUrl)
    expect(details.getByRole('link', { name: "men's clothing" })).toHaveAttribute(
      'href',
      '/category/mens-clothing',
    )
  })

  it('shows not found for an unknown product', async () => {
    mockApi({ '/api/products/999': failure(404) })

    renderApp('/products/999')

    expect(await screen.findByText("We couldn't find that product.")).toBeInTheDocument()
    expect(screen.queryByRole('alert')).not.toBeInTheDocument()
  })

  it('shows an error, not "not found", when the API is down', async () => {
    mockApi({ '/api/products/3': failure(503) })

    renderApp('/products/3')

    expect(await screen.findByRole('alert')).toBeInTheDocument()
    expect(screen.queryByText("We couldn't find that product.")).not.toBeInTheDocument()
  })

  it('shows an error when the network is unreachable', async () => {
    mockApi({})
    globalThis.fetch = (async () => {
      throw new TypeError('Failed to fetch')
    }) as typeof fetch

    renderApp('/products/3')

    expect(await screen.findByRole('alert')).toBeInTheDocument()
  })
})

describe('API boundary', () => {
  it('only ever calls our own API, never the product source', async () => {
    const api = mockApi({
      '/api/products/featured': ok([JACKET]),
      '/api/products/3': ok(JACKET),
    })
    const { user } = renderApp('/')

    await user.click(await screen.findByRole('link', { name: /Cotton Jacket/ }))
    await screen.findByRole('heading', { level: 1, name: 'Cotton Jacket' })

    expect(api.calledUrls().length).toBeGreaterThan(0)
    for (const url of api.calledUrls()) {
      expect(url.startsWith('/api/')).toBe(true)
    }
  })
})
