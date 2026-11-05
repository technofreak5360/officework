// The only code that talks to the backend. Same-origin in development, where
// Vite proxies /api to Laravel.
const API_BASE = '/api'

export type FieldErrors = Record<string, string[]>

export class ApiError extends Error {
  // 0 means the request never got a response.
  readonly status: number
  // Validation messages per field, from a 422 response.
  readonly fieldErrors: FieldErrors

  constructor(status: number, message: string, fieldErrors: FieldErrors = {}) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.fieldErrors = fieldErrors
  }
}

export function isNotFound(error: unknown): boolean {
  return error instanceof ApiError && error.status === 404
}

export function isClientError(error: unknown): boolean {
  return error instanceof ApiError && error.status >= 400 && error.status < 500
}

async function request(path: string, init: RequestInit): Promise<Response> {
  let response: Response

  try {
    response = await fetch(`${API_BASE}${path}`, {
      ...init,
      headers: { Accept: 'application/json', ...init.headers },
    })
  } catch (error) {
    // Let React Query see cancellations for what they are.
    if (error instanceof DOMException && error.name === 'AbortError') {
      throw error
    }
    throw new ApiError(0, 'Could not reach the server.')
  }

  if (!response.ok) {
    throw new ApiError(
      response.status,
      `Request failed with status ${response.status}.`,
      response.status === 422 ? await readFieldErrors(response) : {},
    )
  }

  return response
}

async function readFieldErrors(response: Response): Promise<FieldErrors> {
  try {
    const body = (await response.json()) as { errors?: FieldErrors }

    return body.errors ?? {}
  } catch {
    return {}
  }
}

/** GETs a path and unwraps the API's `{ data }` envelope. */
export async function getData<T>(path: string, signal?: AbortSignal): Promise<T> {
  const response = await request(path, { signal })
  const body = (await response.json()) as { data: T }

  return body.data
}

/** POSTs JSON to a path. Failures throw an ApiError. */
export async function postJson(path: string, payload: unknown): Promise<void> {
  await request(path, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  })
}
