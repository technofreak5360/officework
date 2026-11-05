import { screen } from '@testing-library/react'
import type { UserEvent } from '@testing-library/user-event'
import { describe, expect, it } from 'vitest'
import { failure, mockApi, ok, pending, product, renderApp } from './helpers'

const JACKET = product({ id: 3, title: 'Cotton Jacket' })
const ENQUIRE_URL = '/products/3/enquire'
const POST = 'POST /api/enquiries'
const SUCCESS = { status: 201, body: { message: 'Thank you for your enquiry.' } }

async function fillRequiredFields(user: UserEvent) {
  await user.type(await screen.findByLabelText('Name'), 'Sam Taylor')
  await user.type(screen.getByLabelText('Email'), 'sam@example.com')
}

function clickSend(user: UserEvent) {
  return user.click(screen.getByRole('button', { name: 'Send enquiry' }))
}

describe('enquiry', () => {
  it('goes from the product page to a thank you message and sends the contact details', async () => {
    const api = mockApi({ '/api/products/3': ok(JACKET), [POST]: SUCCESS })
    const { user, router } = renderApp('/products/3')

    await user.click(await screen.findByRole('link', { name: 'Enquire' }))
    expect(router.state.location.pathname).toBe(ENQUIRE_URL)
    expect(
      await screen.findByRole('heading', { name: 'Enquire about Cotton Jacket' }),
    ).toBeInTheDocument()

    await fillRequiredFields(user)
    await user.type(screen.getByLabelText('Phone (optional)'), '+44 20 7946 0000')
    await user.type(screen.getByLabelText('Message (optional)'), 'Is this in stock?')
    await clickSend(user)

    expect(await screen.findByRole('heading', { name: 'Thank you' })).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Send enquiry' })).not.toBeInTheDocument()
    expect(api.postedBodies()).toEqual([
      {
        productId: 3,
        name: 'Sam Taylor',
        email: 'sam@example.com',
        phone: '+44 20 7946 0000',
        message: 'Is this in stock?',
      },
    ])
  })

  it('shows validation errors against their fields and keeps what was typed', async () => {
    mockApi({
      '/api/products/3': ok(JACKET),
      [POST]: failure(422, {
        message: 'The given data was invalid.',
        errors: { email: ['The email field must be a valid email address.'] },
      }),
    })
    const { user } = renderApp(ENQUIRE_URL)

    await user.type(await screen.findByLabelText('Name'), 'Sam Taylor')
    await user.type(screen.getByLabelText('Email'), 'not-an-email')
    await clickSend(user)

    expect(await screen.findByRole('alert')).toHaveTextContent('Please check the details below.')
    const email = screen.getByLabelText('Email')
    expect(email).toBeInvalid()
    expect(email).toHaveAccessibleDescription('The email field must be a valid email address.')
    expect(email).toHaveValue('not-an-email')
    expect(screen.getByLabelText('Name')).toBeValid()
    expect(screen.getByLabelText('Name')).toHaveValue('Sam Taylor')
    expect(screen.queryByRole('heading', { name: 'Thank you' })).not.toBeInTheDocument()
  })

  it('leaves the rules to the API, so an empty form still reaches it', async () => {
    const api = mockApi({
      '/api/products/3': ok(JACKET),
      [POST]: failure(422, { errors: { name: ['The name field is required.'] } }),
    })
    const { user } = renderApp(ENQUIRE_URL)

    await screen.findByLabelText('Name')
    await clickSend(user)

    expect(await screen.findByText('The name field is required.')).toBeInTheDocument()
    expect(api.postedBodies()).toHaveLength(1)
  })

  it('explains the rate limit', async () => {
    mockApi({ '/api/products/3': ok(JACKET), [POST]: failure(429, { message: 'Too Many Attempts.' }) })
    const { user } = renderApp(ENQUIRE_URL)

    await fillRequiredFields(user)
    await clickSend(user)

    expect(await screen.findByRole('alert')).toHaveTextContent('wait a minute')
  })

  it('keeps the details after a server failure, so the customer can send them again', async () => {
    const api = mockApi({ '/api/products/3': ok(JACKET), [POST]: failure(503) })
    const { user } = renderApp(ENQUIRE_URL)

    await fillRequiredFields(user)
    await clickSend(user)

    expect(await screen.findByRole('alert')).toHaveTextContent('could not send your enquiry')
    expect(screen.getByLabelText('Name')).toHaveValue('Sam Taylor')

    api.routes[POST] = SUCCESS
    await clickSend(user)

    expect(await screen.findByRole('heading', { name: 'Thank you' })).toBeInTheDocument()
    expect(screen.queryByRole('alert')).not.toBeInTheDocument()
  })

  it('shows a general error when the problem is not in a form field', async () => {
    mockApi({
      '/api/products/3': ok(JACKET),
      [POST]: failure(422, { errors: { productId: ['The selected product does not exist.'] } }),
    })
    const { user } = renderApp(ENQUIRE_URL)

    await fillRequiredFields(user)
    await clickSend(user)

    expect(await screen.findByRole('alert')).toHaveTextContent('could not send your enquiry')
  })

  it('stops a second submit while the first is on its way', async () => {
    const api = mockApi({ '/api/products/3': ok(JACKET), [POST]: pending() })
    const { user } = renderApp(ENQUIRE_URL)

    await fillRequiredFields(user)
    await clickSend(user)

    const button = await screen.findByRole('button', { name: 'Sending…' })
    expect(button).toBeDisabled()

    await user.click(button)
    expect(api.postedBodies()).toHaveLength(1)
  })

  it('shows not found for an unknown product', async () => {
    mockApi({ '/api/products/999': failure(404) })

    renderApp('/products/999/enquire')

    expect(await screen.findByText("We couldn't find that product.")).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Send enquiry' })).not.toBeInTheDocument()
  })
})
