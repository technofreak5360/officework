import type { FormEvent, ReactNode } from 'react'
import { Link, useParams } from 'react-router'
import { useProduct } from '../api/catalogue'
import { ApiError, isNotFound } from '../api/client'
import { useSubmitEnquiry } from '../api/enquiries'
import type { Product } from '../api/types'
import { ErrorState, LoadingState } from '../components/QueryStates'
import { NotFoundPage } from './NotFoundPage'

const FORM_FIELDS = ['name', 'email', 'phone', 'message']

function submitErrorMessage(error: unknown): string {
  if (error instanceof ApiError && error.status === 429) {
    return 'You have sent several enquiries in a short time. Please wait a minute and try again.'
  }

  if (error instanceof ApiError && error.status === 422) {
    const hasFieldError = FORM_FIELDS.some((field) => error.fieldErrors[field])

    if (hasFieldError) {
      return 'Please check the details below.'
    }
  }

  return 'Sorry, we could not send your enquiry. Please try again.'
}

function Field({
  name,
  label,
  errors,
  children,
}: {
  name: string
  label: string
  errors?: string[]
  children: (props: {
    id: string
    name: string
    'aria-invalid': boolean
    'aria-describedby': string | undefined
  }) => ReactNode
}) {
  const id = `enquiry-${name}`
  const errorId = `${id}-error`

  return (
    <div className="field">
      <label htmlFor={id}>{label}</label>
      {children({
        id,
        name,
        'aria-invalid': Boolean(errors),
        'aria-describedby': errors ? errorId : undefined,
      })}
      {errors && (
        <p id={errorId} className="field__error">
          {errors[0]}
        </p>
      )}
    </div>
  )
}

function EnquiryForm({ product }: { product: Product }) {
  const submitEnquiry = useSubmitEnquiry()
  const { error, isPending } = submitEnquiry
  const fieldErrors = error instanceof ApiError ? error.fieldErrors : {}

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()

    const form = new FormData(event.currentTarget)

    submitEnquiry.mutate({
      productId: product.id,
      name: String(form.get('name')),
      email: String(form.get('email')),
      phone: String(form.get('phone')),
      message: String(form.get('message')),
    })
  }

  if (submitEnquiry.isSuccess) {
    return (
      <div role="status">
        <h1>Thank you</h1>
        <p>We have received your enquiry about {product.title} and will be in touch soon.</p>
        <Link to={`/products/${product.id}`}>Back to the product</Link>
      </div>
    )
  }

  return (
    <>
      <h1>Enquire about {product.title}</h1>
      {error && (
        <p className="form-error" role="alert">
          {submitErrorMessage(error)}
        </p>
      )}
      {/* noValidate: the API owns the rules, so they live in one place. */}
      <form className="form" onSubmit={handleSubmit} noValidate>
        <Field name="name" label="Name" errors={fieldErrors.name}>
          {(props) => <input {...props} className="input" type="text" autoComplete="name" />}
        </Field>
        <Field name="email" label="Email" errors={fieldErrors.email}>
          {(props) => <input {...props} className="input" type="email" autoComplete="email" />}
        </Field>
        <Field name="phone" label="Phone (optional)" errors={fieldErrors.phone}>
          {(props) => <input {...props} className="input" type="tel" autoComplete="tel" />}
        </Field>
        <Field name="message" label="Message (optional)" errors={fieldErrors.message}>
          {(props) => <textarea {...props} className="input" rows={5} />}
        </Field>
        <button type="submit" className="button" disabled={isPending}>
          {isPending ? 'Sending…' : 'Send enquiry'}
        </button>
      </form>
    </>
  )
}

export function EnquiryPage() {
  const { id = '' } = useParams()
  const { data: product, isPending, error, refetch } = useProduct(id)

  if (isPending) {
    return <LoadingState />
  }

  if (isNotFound(error)) {
    return <NotFoundPage message="We couldn't find that product." />
  }

  if (!product) {
    return <ErrorState onRetry={() => refetch()} />
  }

  return (
    <>
      <title>{`Enquire: ${product.title} | Product Catalogue`}</title>
      <EnquiryForm product={product} />
    </>
  )
}
