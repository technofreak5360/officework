export function LoadingState() {
  return (
    <p className="state" role="status">
      Loading…
    </p>
  )
}

export function ErrorState({ onRetry }: { onRetry: () => void }) {
  return (
    <div className="state" role="alert">
      <p>Sorry, something went wrong loading this page.</p>
      <button type="button" className="button" onClick={onRetry}>
        Try again
      </button>
    </div>
  )
}
