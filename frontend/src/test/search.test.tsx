import { screen, within } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { failure, mockApi, ok, product, renderApp } from './helpers'

describe('search', () => {
  it('shows only what the API matched after clicking the search button', async () => {
    const api = mockApi({
      '/api/products/featured': ok([product({ id: 1, title: 'Unrelated Shirt' })]),
      '/api/products?search=jacket': ok([product({ id: 3, title: 'Cotton Jacket' })]),
    })
    const { user, router } = renderApp('/')
    expect(await screen.findByText('Unrelated Shirt')).toBeInTheDocument()

    await user.type(screen.getByRole('searchbox', { name: 'Search products' }), 'jacket')
    expect(api.calledUrls()).not.toContain('/api/products?search=jacket')
    await user.click(screen.getByRole('button', { name: 'Search' }))

    expect(router.state.location.search).toBe('?q=jacket')
    expect(await screen.findByText('Cotton Jacket')).toBeInTheDocument()
    expect(screen.getByRole('heading', { level: 1 })).toHaveTextContent('Results for “jacket”')
    expect(within(screen.getByRole('main')).getAllByRole('link')).toHaveLength(1)
    expect(screen.queryByText('Unrelated Shirt')).not.toBeInTheDocument()
  })

  it('also searches when pressing Enter', async () => {
    mockApi({
      '/api/products/featured': ok([]),
      '/api/products?search=ssd': ok([product({ id: 11, title: 'Fast SSD' })]),
    })
    const { user } = renderApp('/')

    await user.type(screen.getByRole('searchbox'), 'ssd{Enter}')

    expect(await screen.findByText('Fast SSD')).toBeInTheDocument()
  })

  it('encodes special characters in the term', async () => {
    const api = mockApi({
      '/api/products/featured': ok([]),
      '/api/products?search=gold%20%26%20silver%3F': ok([]),
    })
    const { user, router } = renderApp('/')

    await user.type(screen.getByRole('searchbox'), 'gold & silver?{Enter}')

    await screen.findByText('No products match your search.')
    expect(router.state.location.search).toBe('?q=gold%20%26%20silver%3F')
    expect(api.calledUrls()).toContain('/api/products?search=gold%20%26%20silver%3F')
  })

  it('does nothing for a blank term', async () => {
    const api = mockApi({ '/api/products/featured': ok([]) })
    const { user, router } = renderApp('/')

    await user.type(screen.getByRole('searchbox'), '   ')
    await user.click(screen.getByRole('button', { name: 'Search' }))

    expect(router.state.location.pathname).toBe('/')
    expect(api.calledUrls().filter((url) => url.includes('search'))).toEqual([])
  })

  it('tells the user when nothing matches', async () => {
    mockApi({ '/api/products?search=zzz': ok([]) })

    renderApp('/search?q=zzz')

    expect(await screen.findByText('No products match your search.')).toBeInTheDocument()
    expect(screen.queryByText('Loading…')).not.toBeInTheDocument()
  })

  it('restores the term from the URL, so results can be linked to', async () => {
    mockApi({ '/api/products?search=ssd': ok([product({ id: 11, title: 'Fast SSD' })]) })

    renderApp('/search?q=ssd')

    expect(await screen.findByText('Fast SSD')).toBeInTheDocument()
    expect(screen.getByRole('searchbox')).toHaveValue('ssd')
  })

  it('prompts for a term when the URL has none, without calling the search API', async () => {
    const api = mockApi({})

    renderApp('/search')

    expect(await screen.findByRole('heading', { level: 1 })).toHaveTextContent('Enter a search term')
    expect(api.calledUrls().filter((url) => url.includes('search'))).toEqual([])
  })

  it('shows an error when the API fails', async () => {
    mockApi({ '/api/products?search=ssd': failure(503) })

    renderApp('/search?q=ssd')

    expect(await screen.findByRole('alert')).toBeInTheDocument()
    expect(screen.queryByText('No products match your search.')).not.toBeInTheDocument()
  })
})
