/**
 * The square mark identifying an application on the hub.
 *
 * Every application shows the same AMS logo, by design: the hub is one product
 * and its tiles are meant to read as one family. That also means a newly
 * registered application needs no artwork and no entry in a colour table to
 * look right — it gets the logo like every other tile.
 *
 * This replaced a generated mark (the name's initials over a per-application
 * gradient). Nothing else needs to change to onboard an application.
 */

/** Served from `frontend/public/`, so the same file backs the header logo. */
const AMS_LOGO = "/ams_aircraft_management_system_logo.jpeg";

interface AppMarkProps {
  /** The application's name. Rendered beside the mark by the caller. */
  name: string;
  /**
   * A per-application logo, kept in the props so callers need no change.
   * Deliberately ignored: the hub shows one logo for every application.
   */
  iconUrl?: string | null;
  className?: string;
}

export const AppMark = ({ className = "h-10 w-10" }: AppMarkProps) => (
  <img
    src={AMS_LOGO}
    alt="AMS Logo"
    className={`${className} rounded-lg object-cover bg-white shadow-sm shrink-0`}
  />
);
