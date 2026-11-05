import { screen, within } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { failure, mockApi, ok, product, renderApp } from './helpers'

const FEATURED = [1, 2, 3, 4, 5].map((id) => product({ id }))

describe('home page', () => {
  it('shows the chosen featured products in order', async () => {
    mockApi({ '/api/products/featured': ok(FEATURED) })

    renderApp('/')

    const links = await within(await screen.findByRole('main')).findAllByRole('link')
    expect(links).toHaveLength(5)
    expect(links.map((link) => link.getAttribute('href'))).toEqual(
      FEATURED.map(({ id }) => `/products/${id}`),
    )
  })

  it('shows loading while the products are on their way', async () => {
    mockApi({ '/api/products/featured': ok(FEATURED) })

    renderApp('/')

    expect(screen.getByRole('status')).toHaveTextContent('Loading')
    expect(await screen.findByText('Product 1')).toBeInTheDocument()
    expect(screen.queryByRole('status')).not.toBeInTheDocument()
  })

  it('shows an error when the API fails, and recovers on retry', async () => {
    const api = mockApi({ '/api/products/featured': failure(503) })
    const { user } = renderApp('/')

    expect(await screen.findByRole('alert')).toHaveTextContent('something went wrong')
    expect(screen.queryByText('Server error')).not.toBeInTheDocument()

    api.routes['/api/products/featured'] = ok(FEATURED)
    await user.click(screen.getByRole('button', { name: 'Try again' }))

    expect(await screen.findByText('Product 1')).toBeInTheDocument()
    expect(screen.queryByRole('alert')).not.toBeInTheDocument()
  })

  it('still shows the page when the category links fail to load', async () => {
    mockApi({ '/api/products/featured': ok(FEATURED), '/api/categories': failure(503) })

    renderApp('/')

    expect(await screen.findByText('Product 1')).toBeInTheDocument()
    expect(screen.queryByRole('alert')).not.toBeInTheDocument()
  })
})

describe('unknown URLs', () => {
  it('shows a not found page', async () => {
    mockApi({})

    renderApp('/no-such-page')

    expect(await screen.findByRole('heading', { name: 'Not found' })).toBeInTheDocument()
  })
})
