import { screen, within } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { failure, mockApi, ok, product, renderApp } from './helpers'

const LISTING = {
  category: { name: 'electronics', slug: 'electronics' },
  topRated: [product({ id: 11, title: 'Best SSD' })],
  others: [product({ id: 12, title: 'Other Drive' })],
}

describe('category page', () => {
  it('opens from a category link and shows top rated and additional products', async () => {
    mockApi({
      '/api/products/featured': ok([]),
      '/api/categories/electronics/products': ok(LISTING),
    })
    const { user, router } = renderApp('/')

    await user.click(await screen.findByRole('link', { name: 'electronics' }))

    expect(router.state.location.pathname).toBe('/category/electronics')
    expect(await screen.findByRole('heading', { level: 1, name: 'electronics' })).toBeInTheDocument()

    const topRated = screen.getByRole('region', { name: 'Top rated' })
    expect(within(topRated).getByText('Best SSD')).toBeInTheDocument()
    expect(within(topRated).queryByText('Other Drive')).not.toBeInTheDocument()

    const others = screen.getByRole('region', { name: 'More in this category' })
    expect(within(others).getByText('Other Drive')).toBeInTheDocument()
    expect(within(others).queryByText('Best SSD')).not.toBeInTheDocument()
  })

  it('leaves out the additional section when there is nothing to show', async () => {
    mockApi({ '/api/categories/electronics/products': ok({ ...LISTING, others: [] }) })

    renderApp('/category/electronics')

    expect(await screen.findByRole('region', { name: 'Top rated' })).toBeInTheDocument()
    expect(screen.queryByRole('region', { name: 'More in this category' })).not.toBeInTheDocument()
  })

  it('shows not found for an unknown category', async () => {
    mockApi({ '/api/categories/nope/products': failure(404) })

    renderApp('/category/nope')

    expect(await screen.findByText("We couldn't find that category.")).toBeInTheDocument()
    expect(screen.queryByRole('alert')).not.toBeInTheDocument()
  })

  it('shows an error when the API fails', async () => {
    mockApi({ '/api/categories/electronics/products': failure(503) })

    renderApp('/category/electronics')

    expect(await screen.findByRole('alert')).toBeInTheDocument()
  })
})
