// The only code that talks to the backend. Same-origin in development, where
// Vite proxies /api to Laravel.
const API_BASE = '/api'

export class ApiError extends Error {
  // 0 means the request never got a response.
  readonly status: number

  constructor(status: number, message: string) {
    super(message)
    this.name = 'ApiError'
    this.status = status
  }
}

export function isNotFound(error: unknown): boolean {
  return error instanceof ApiError && error.status === 404
}

export function isClientError(error: unknown): boolean {
  return error instanceof ApiError && error.status >= 400 && error.status < 500
}

/** GETs a path and unwraps the API's `{ data }` envelope. */
export async function getData<T>(path: string, signal?: AbortSignal): Promise<T> {
  let response: Response

  try {
    response = await fetch(`${API_BASE}${path}`, {
      headers: { Accept: 'application/json' },
      signal,
    })
  } catch (error) {
    // Let React Query see cancellations for what they are.
    if (error instanceof DOMException && error.name === 'AbortError') {
      throw error
    }
    throw new ApiError(0, 'Could not reach the server.')
  }

  if (!response.ok) {
    throw new ApiError(response.status, `Request failed with status ${response.status}.`)
  }

  const body = (await response.json()) as { data: T }

  return body.data
}
