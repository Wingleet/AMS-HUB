import { useState, useEffect } from "react";
import { ArrowRight, Zap } from "lucide-react";
import { AppMark } from "../components/AppMark";
import { applicationService } from "../services/applicationService";
import { API_URL } from "../services/apiClient";
import { useAuth } from "../hooks/useAuth";
import type { Application } from "../types";

/**
 * The launcher: every application the hub knows about, each opening in its own
 * tab. Styled in iDeck's language — white cards, soft shadow, slate borders on
 * the light page background.
 */
const Home = () => {
  const { user } = useAuth();
  const [applications, setApplications] = useState<Application[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const loadApplications = async () => {
      setIsLoading(true);
      setError(null);

      try {
        const result = await applicationService.getApplications();

        if (result.success && result.data) {
          const apps = Array.isArray(result.data) ? result.data : [];
          // Entries with no URL are roadmap placeholders — nothing to link to,
          // so they are noise on a launcher.
          setApplications(apps.filter((app) => app.url));
        } else {
          setError(result.message || "Failed to load applications");
        }
      } catch (err) {
        console.error("Error loading applications:", err);
        setError("An unexpected error occurred while loading applications");
        setApplications([]);
      }

      setIsLoading(false);
    };

    loadApplications();
  }, []);

  const decodeHTML = (html: string) => {
    const textarea = document.createElement("textarea");
    textarea.innerHTML = html;
    return textarea.value;
  };

  /**
   * Where a tile points. Applications wired to the hub SSO go through
   * /sso/authorize so they receive a session; the others open directly, which
   * is every application today since none has a callback URL configured.
   */
  const targetUrl = (application: Application) =>
    application.supportsSso
      ? `${API_URL}/sso/authorize?application_id=${application.id}`
      : (application.url as string);

  return (
    <>
      {user?.organization && !user.organization.isActive && (
        <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl flex items-start gap-4">
          <svg
            className="w-6 h-6 text-red-500 flex-shrink-0 mt-0.5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M12 9v2m0 4v2m0 0a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
          <div className="flex-1">
            <h3 className="text-red-700 font-semibold mb-1">
              Organization deactivated
            </h3>
            <p className="text-red-600 text-sm">
              Your organization has been deactivated. Please contact support for
              more information.
            </p>
          </div>
        </div>
      )}

      {error && (
        <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-600 text-sm">
          {error}
        </div>
      )}

      {isLoading && (
        <div className="bg-white rounded-xl shadow-sm border border-slate-200 p-16 flex items-center justify-center">
          <span className="text-slate-500">Loading applications…</span>
        </div>
      )}

      {!isLoading && (
        <section>
          <h2 className="text-xl font-bold text-slate-800 mb-6">
            Applications
          </h2>

          {applications.length === 0 ? (
            <div className="bg-white rounded-xl shadow-sm border border-slate-200 p-16 flex flex-col items-center">
              <Zap className="w-12 h-12 text-slate-300 mb-4" />
              <p className="text-slate-500">No application available yet</p>
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {applications.map((application) => (
                <div
                  key={application.id}
                  className="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex flex-col hover:shadow-md transition-shadow"
                >
                  <div className="flex items-start justify-between gap-3 mb-4">
                    <div className="flex items-center gap-4 min-w-0">
                      <AppMark
                        name={application.name}
                        iconUrl={application.iconUrl}
                      />
                      <h3 className="text-lg font-semibold text-slate-800 truncate">
                        {application.name}
                      </h3>
                    </div>
                    {!application.isActive && (
                      <span className="inline-flex px-3 py-1 rounded-md text-xs font-semibold bg-slate-100 text-slate-500 whitespace-nowrap">
                        Inactive
                      </span>
                    )}
                  </div>

                  {application.description && (
                    <p className="text-sm text-slate-500 mb-6">
                      {decodeHTML(application.description)}
                    </p>
                  )}

                  <div className="mt-auto pt-4 border-t border-slate-100">
                    {application.isActive ? (
                      <a
                        href={targetUrl(application)}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
                      >
                        Open
                        <ArrowRight className="w-4 h-4" />
                      </a>
                    ) : (
                      <span className="text-sm text-slate-400">
                        This application has been disabled
                      </span>
                    )}
                  </div>
                </div>
              ))}
            </div>
          )}
        </section>
      )}
    </>
  );
};

export default Home;
