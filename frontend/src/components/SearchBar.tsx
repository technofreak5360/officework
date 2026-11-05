import type { FormEvent } from 'react'
import { useNavigate, useSearchParams } from 'react-router'

export function SearchBar() {
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()
  const currentTerm = searchParams.get('q') ?? ''

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()

    const term = String(new FormData(event.currentTarget).get('q') ?? '').trim()

    if (term !== '') {
      navigate(`/search?q=${encodeURIComponent(term)}`)
    }
  }

  return (
    <form className="search" role="search" onSubmit={handleSubmit}>
      <label className="visually-hidden" htmlFor="search-term">
        Search products
      </label>
      {/* The key resets the input when the URL's term changes, e.g. on Back. */}
      <input
        key={currentTerm}
        id="search-term"
        className="search__input"
        name="q"
        type="search"
        placeholder="Search products"
        defaultValue={currentTerm}
        maxLength={100}
      />
      <button type="submit" className="button">
        Search
      </button>
    </form>
  )
}
