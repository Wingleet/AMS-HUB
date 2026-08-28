import { Link, Outlet, useLocation } from "@tanstack/react-router";
import { User, LogOut, Menu, X, Database } from "lucide-react";
import { useAuth } from "../hooks/useAuth";
import { useState } from "react";

/**
 * Application shell, using iDeck's design language so the two products read as
 * one: light `bg-slate-100` page, blue gradient header bar, `max-w-[1200px]`
 * content column, white cards with a soft shadow.
 */
export const Layout = () => {
  const { user, isAuthenticated, logout } = useAuth();
  const location = useLocation();
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

  const isLoginPage = location.pathname === "/login";

  const handleSignOut = async () => {
    await logout();
  };

  // The sign-in page owns the whole viewport, like iDeck's: its gradient runs
  // edge to edge, so the chrome and the centred content column would box it in.
  if (isLoginPage) {
    return <Outlet />;
  }

  return (
    <div className="min-h-screen bg-slate-100 text-slate-700 flex flex-col antialiased">
      <header className="bg-gradient-to-r from-blue-600 to-blue-700 shadow-md">
        <div className="max-w-[1200px] mx-auto px-6 py-4 flex items-center justify-between gap-4">
          <Link
            to="/"
            className="flex items-center gap-3 hover:opacity-90 transition-opacity"
          >
            <img
              src="/ams_aircraft_management_system_logo.jpeg"
              alt="AMS Logo"
              className="h-10 w-10 rounded-lg object-cover bg-white shadow-sm"
            />
            <span className="text-2xl font-bold text-white tracking-tight">
              AMS APPS HUB
            </span>
          </Link>

          {/* Desktop */}
          <div className="hidden md:flex items-center gap-4">
            {isAuthenticated ? (
              <>
                <div className="flex items-center gap-2 text-sm font-medium text-white/90">
                  <User className="w-4 h-4" />
                  {user?.fullName}
                </div>
                {/* The AMS database, not the organization: which data set you
                    are looking at is what changes between sessions. Recorded at
                    sign-in, so a session opened before this existed has none —
                    show nothing rather than a labelless icon. */}
                {user?.amsServerDb && (
                  <>
                    <div className="text-white/40">|</div>
                    <div
                      className="flex items-center gap-2 text-sm font-medium text-white/90"
                      title="AMS database"
                    >
                      <Database className="w-4 h-4" />
                      {user.amsServerDb}
                    </div>
                  </>
                )}
                <button
                  className="px-3 py-2 text-sm font-medium bg-white/20 hover:bg-white/30 text-white rounded-lg flex items-center gap-2 transition-colors"
                  onClick={handleSignOut}
                >
                  <LogOut className="w-4 h-4" />
                  Logout
                </button>
              </>
            ) : null}
          </div>

          {/* Mobile */}
          <div className="md:hidden">
            {isAuthenticated && (
              <button
                onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
                className="p-2 text-white/90 hover:text-white transition-colors"
                title="Toggle menu"
              >
                {isMobileMenuOpen ? (
                  <X className="w-6 h-6" />
                ) : (
                  <Menu className="w-6 h-6" />
                )}
              </button>
            )}
          </div>
        </div>

        {isAuthenticated && isMobileMenuOpen && (
          <div className="md:hidden border-t border-white/20 bg-blue-700">
            <div className="px-6 py-4 space-y-4">
              <div className="flex items-center gap-2 text-sm font-medium text-white/90">
                <User className="w-4 h-4" />
                {user?.fullName}
              </div>
              {user?.amsServerDb && (
                <div className="flex items-center gap-2 text-sm font-medium text-white/90">
                  <Database className="w-4 h-4" />
                  {user.amsServerDb}
                </div>
              )}
              <button
                className="w-full px-3 py-2 text-sm font-medium bg-white/20 hover:bg-white/30 text-white rounded-lg flex items-center justify-center gap-2 transition-colors"
                onClick={handleSignOut}
              >
                <LogOut className="w-4 h-4" />
                Logout
              </button>
            </div>
          </div>
        )}
      </header>

      <main className="flex-grow max-w-[1200px] mx-auto w-full px-6 py-8">
        <Outlet />
      </main>

      <footer className="border-t border-slate-200 py-6 mt-auto">
        <div className="max-w-[1200px] mx-auto px-6">
          <p className="text-sm text-slate-400">
            &copy; 2025 Production Systems. All rights reserved.
          </p>
        </div>
      </footer>
    </div>
  );
};
