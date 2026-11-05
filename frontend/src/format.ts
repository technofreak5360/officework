// The API has no currency field, so USD is an assumption kept in this one place.
const priceFormat = new Intl.NumberFormat('en-US', {
  style: 'currency',
  currency: 'USD',
})

export function formatPrice(price: number): string {
  return priceFormat.format(price)
}
