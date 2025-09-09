import { Link } from '@inertiajs/react';
import { ReactNode } from 'react';

interface AuthenticatedLayoutProps {
  children: ReactNode;
  header?: ReactNode;
}

export default function AuthenticatedLayout({ children, header }: AuthenticatedLayoutProps) {
  return (
    <div className="min-h-screen bg-gray-100">
      <nav className="bg-white border-b border-gray-100">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between h-16">
            <div className="flex items-center">
              <Link href="/" className="text-xl font-semibold text-gray-900">
                Laravel Inertia
              </Link>
            </div>
            
            <div className="flex items-center space-x-4">
              <Link
                href="/"
                className="text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium"
              >
                Home
              </Link>
              <Link
                href="/review"
                className="text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium"
              >
                Review
              </Link>
            </div>
          </div>
        </div>
      </nav>

      {header && (
        <header className="bg-white shadow">
          <div className="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            {header}
          </div>
        </header>
      )}

      <main>{children}</main>
    </div>
  );
}