import { Link, NavLink, Outlet, ScrollRestoration } from 'react-router'
import { useCategories } from '../api/catalogue'
import { SearchBar } from './SearchBar'

export function Layout() {
  // If this fails the links are simply missing. The page's own content still works.
  const { data: categories = [] } = useCategories()

  return (
    <>
      <header className="header">
        <div className="header__row container">
          <Link to="/" className="brand">
            Product Catalogue
          </Link>
          <SearchBar />
        </div>
        <nav className="container" aria-label="Categories">
          <ul className="category-nav">
            {categories.map((category) => (
              <li key={category.slug}>
                <NavLink to={`/category/${category.slug}`} className="category-nav__link">
                  {category.name}
                </NavLink>
              </li>
            ))}
          </ul>
        </nav>
      </header>
      <main className="container main">
        <Outlet />
      </main>
      <ScrollRestoration />
    </>
  )
}
