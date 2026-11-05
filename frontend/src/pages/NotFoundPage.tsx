import { Link } from 'react-router'

export function NotFoundPage({ message = "We couldn't find that page." }: { message?: string }) {
  return (
    <div className="state">
      <h1>Not found</h1>
      <p>{message}</p>
      <Link to="/">Back to the home page</Link>
    </div>
  )
}
