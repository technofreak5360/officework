import { QueryClient } from '@tanstack/react-query'
import { isClientError } from './client'

export function createQueryClient(): QueryClient {
  return new QueryClient({
    defaultOptions: {
      queries: {
        // Products rarely change, and the backend caches them anyway.
        staleTime: 60_000,
        refetchOnWindowFocus: false,
        // A 404 won't change on retry. Anything else gets one more attempt.
        retry: (failureCount, error) => !isClientError(error) && failureCount < 1,
      },
    },
  })
}
