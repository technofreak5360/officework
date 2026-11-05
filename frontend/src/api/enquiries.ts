import { useMutation } from '@tanstack/react-query'
import { postJson } from './client'

export type NewEnquiry = {
  productId: number
  name: string
  email: string
  phone: string
  message: string
}

export function useSubmitEnquiry() {
  return useMutation({
    mutationFn: (enquiry: NewEnquiry) => postJson('/enquiries', enquiry),
  })
}
