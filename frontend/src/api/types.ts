// The shapes returned by our own API. Nothing here should mirror Fake Store.

export type Category = {
  name: string
  slug: string
}

export type Product = {
  id: number
  title: string
  price: number
  description: string
  category: Category
  imageUrl: string
  rating: {
    rate: number
    count: number
  }
}

export type CategoryListing = {
  category: Category
  topRated: Product[]
  others: Product[]
}
