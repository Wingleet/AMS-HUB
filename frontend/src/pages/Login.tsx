import { useNavigate } from "@tanstack/react-router";
import { useState } from "react";
import { authService } from "../services/authService";
import { useAuthStore } from "../store/authStore";
import type { LoginFormData } from "../types";
import { ConfirmDialog } from "../components/ConfirmDialog";
import { clearEverything, reloadFresh } from "../utils/appReset";

/**
 * Sign-in page, deliberately mirroring iDeck's so the two applications feel
 * like one product: same glass card on a blue gradient, same field set, same
 * interactions (uppercase username, reveal toggles, AMS database on the form).
 */

/** Key used to persist the last successfully used database name */
const LAST_SERVERDB_KEY = "hub_last_serverdb";

function getSavedServerDb(): string {
  try {
    return (
      localStorage.getItem(LAST_SERVERDB_KEY) ||
      import.meta.env.VITE_DEFAULT_SERVER_DB ||
      ""
    );
  } catch {
    return import.meta.env.VITE_DEFAULT_SERVER_DB || "";
  }
}

const EyeIcon = ({ crossed }: { crossed: boolean }) => (
  <svg
    className="w-6 h-6"
    fill="none"
    stroke="currentColor"
    viewBox="0 0 24 24"
  >
    {crossed ? (
      <path
        strokeLinecap="round"
        strokeLinejoin="round"
        strokeWidth={2}
        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"
      />
    ) : (
      <>
        <path
          strokeLinecap="round"
          strokeLinejoin="round"
          strokeWidth={2}
          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
        />
        <path
          strokeLinecap="round"
          strokeLinejoin="round"
          strokeWidth={2}
          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
        />
      </>
    )}
  </svg>
);

const FIELD_CLASS =
  "w-full px-5 py-4 bg-white/10 border border-white/20 rounded-xl text-white text-lg placeholder-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all touch-manipulation";

const REVEAL_BUTTON_CLASS =
  "absolute right-2 top-1/2 transform -translate-y-1/2 text-blue-300 hover:text-white active:text-blue-100 transition-colors p-2 touch-manipulation";

const Login = () => {
  const navigate = useNavigate();
  const { setUser } = useAuthStore();
  const [formData, setFormData] = useState<LoginFormData>({
    username: "",
    password: "",
    rememberMe: false,
    serverdb: getSavedServerDb(),
    serverdbpass: "",
  });
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [showServerDbPassword, setShowServerDbPassword] = useState(false);

  /**
   * Reset app — ported from iDeck/iPlanning's broom (Trello #67). Lives here,
   * outside the form and below it, so it never sits beside the submit button:
   * a destructive control next to "Sign in" is a mis-click waiting to happen.
   * It ends the session the same way sign-out does, then drops everything
   * else this browser holds for the app and reloads from scratch.
   */
  const [confirmReset, setConfirmReset] = useState(false);
  const [isResetting, setIsResetting] = useState(false);

  const resetApp = async () => {
    setIsResetting(true);
    await clearEverything();
    reloadFresh();
  };

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value, type, checked } = e.target;
    setFormData((prev) => ({
      ...prev,
      // The username must always be uppercase.
      [name]:
        type === "checkbox"
          ? checked
          : name === "username"
            ? value.toUpperCase()
            : value,
    }));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    setError("");

    try {
      const response = await authService.login({
        username: formData.username,
        password: formData.password,
        rememberMe: formData.rememberMe,
        serverdb: formData.serverdb,
        serverdbpass: formData.serverdbpass,
      });

      if (response.success && response.user) {
        // Persist only the database name for next login
        try {
          localStorage.setItem(LAST_SERVERDB_KEY, formData.serverdb);
        } catch {
          // A blocked localStorage only costs the convenience of prefilling.
        }
        setUser(response.user);
        navigate({ to: "/" });
      } else {
        setError(response.message || "Login error");
      }
    } catch (err) {
      setError(
        err instanceof Error ? err.message : "Authentication failed",
      );
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-900 via-blue-800 to-indigo-900 flex items-center justify-center p-4">
      <div className="bg-white/10 backdrop-blur-lg rounded-3xl shadow-2xl border border-white/20 p-8 w-full max-w-md">
        <div className="text-center mb-8">
          <div className="w-32 h-32 bg-white rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg p-3">
            <img
              src="/ams_aircraft_management_system_logo.jpeg"
              alt="iCare AMS Logo"
              className="w-full h-full object-contain"
            />
          </div>
          <h1 className="text-3xl font-bold text-white mb-2">AMS APPS HUB</h1>
          <p className="text-blue-200">Sign in to access your applications</p>
        </div>

        {error && (
          <div className="bg-red-500/10 border border-red-500/20 rounded-xl p-4 mb-6">
            <div className="flex items-center">
              <svg
                className="w-5 h-5 text-red-400 mr-2 flex-shrink-0"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
              <span className="text-red-300 text-sm">{error}</span>
            </div>
          </div>
        )}

        <form name="loginForm" onSubmit={handleSubmit} className="space-y-5">
          {/* Username */}
          <div>
            <label
              htmlFor="username"
              className="block text-base font-medium text-blue-200 mb-2"
            >
              Username
            </label>
            <input
              type="text"
              id="username"
              name="username"
              value={formData.username}
              onChange={handleChange}
              required
              autoComplete="username"
              className={FIELD_CLASS}
              placeholder="Enter your username"
              style={{ minHeight: "56px" }}
            />
          </div>

          {/* Password */}
          <div>
            <label
              htmlFor="password"
              className="block text-base font-medium text-blue-200 mb-2"
            >
              Password
            </label>
            <div className="relative">
              <input
                type={showPassword ? "text" : "password"}
                id="password"
                name="password"
                value={formData.password}
                onChange={handleChange}
                required
                autoComplete="current-password"
                className={`${FIELD_CLASS} pr-14`}
                placeholder="Enter your password"
                style={{ minHeight: "56px" }}
              />
              <button
                type="button"
                onClick={() => setShowPassword(!showPassword)}
                aria-label={showPassword ? "Hide password" : "Show password"}
                className={REVEAL_BUTTON_CLASS}
                style={{ minHeight: "44px", minWidth: "44px" }}
              >
                <EyeIcon crossed={showPassword} />
              </button>
            </div>
          </div>

          {/* Database Name */}
          <div>
            <label
              htmlFor="serverdb"
              className="block text-base font-medium text-blue-200 mb-2"
            >
              Database
            </label>
            <input
              type="text"
              id="serverdb"
              name="serverdb"
              value={formData.serverdb}
              onChange={handleChange}
              required
              className={FIELD_CLASS}
              placeholder="Database name"
              style={{ minHeight: "56px" }}
            />
          </div>

          {/* Server DB Password (Optional) */}
          <div>
            <label
              htmlFor="serverdbpass"
              className="block text-base font-medium text-blue-200 mb-2"
            >
              DB password <span className="text-blue-400">(optional)</span>
            </label>
            <div className="relative">
              <input
                type={showServerDbPassword ? "text" : "password"}
                id="serverdbpass"
                name="serverdbpass"
                value={formData.serverdbpass}
                onChange={handleChange}
                className={`${FIELD_CLASS} pr-14`}
                placeholder="Database password"
                style={{ minHeight: "56px" }}
              />
              {formData.serverdbpass && (
                <button
                  type="button"
                  onClick={() => setShowServerDbPassword(!showServerDbPassword)}
                  aria-label={
                    showServerDbPassword
                      ? "Hide database password"
                      : "Show database password"
                  }
                  className={REVEAL_BUTTON_CLASS}
                  style={{ minHeight: "44px", minWidth: "44px" }}
                >
                  <EyeIcon crossed={showServerDbPassword} />
                </button>
              )}
            </div>
          </div>

          {/* Submit Button */}
          <button
            type="submit"
            disabled={isLoading}
            className="w-full py-4 px-6 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 active:from-blue-800 active:to-indigo-800 text-white text-lg font-bold rounded-xl transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none shadow-lg touch-manipulation"
            style={{ minHeight: "60px" }}
          >
            {isLoading ? (
              <div className="flex items-center justify-center">
                <span
                  className="-ml-1 mr-3 inline-block h-5 w-5 rounded-full border-2 border-current border-t-transparent animate-spin"
                  aria-hidden="true"
                />
                Signing in...
              </div>
            ) : (
              "Sign in"
            )}
          </button>
        </form>

        <div className="mt-6 text-center">
          <p className="text-blue-300 text-sm">
            Use your iCare AMS credentials to sign in
          </p>

          {/* The broom — Trello #67, ported from iDeck/iPlanning. OUTSIDE the
              form and below it, not beside "Sign in": a destructive control
              against the primary submit is exactly the mis-click this
              placement avoids. */}
          <button
            type="button"
            onClick={() => setConfirmReset(true)}
            disabled={isLoading || isResetting}
            title="Reset app — clears everything this browser holds for the application and re-downloads it"
            className="mt-4 inline-flex items-center gap-2 rounded-lg border border-red-300/30 bg-red-500/10 px-3 py-2 text-sm text-red-200 hover:bg-red-500/20 hover:text-red-100 disabled:opacity-50"
            style={{ minHeight: 0 }}
          >
            <span className="text-base leading-none" aria-hidden="true">
              🧹
            </span>
            Reset app
          </button>
        </div>
      </div>

      {/* The app draws its own question. NEVER `window.confirm`: once somebody
          ticks Chrome's "prevent this page from creating additional dialogs",
          `confirm` stops drawing anything and simply returns false, so the
          broom would read every press as "cancelled" and do nothing, silently,
          for the rest of that tab's life. */}
      <ConfirmDialog
        isOpen={confirmReset}
        title="Reset the app?"
        message="This clears everything this browser holds for the application — preferences, the database remembered on this form, cached data — and re-downloads the application itself, then reloads."
        confirmLabel="Reset the app"
        tone="danger"
        isProcessing={isResetting}
        onConfirm={() => {
          setConfirmReset(false);
          void resetApp();
        }}
        onCancel={() => setConfirmReset(false)}
      />
    </div>
  );
};

export default Login;
