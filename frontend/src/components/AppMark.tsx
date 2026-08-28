import { useState } from "react";

/**
 * The square mark identifying an application on the hub.
 *
 * Shows the application's own logo when one is registered (`iconUrl`), and
 * falls back to a generated mark otherwise.
 *
 * The fallback is not the first letter of the name: every AMS application is
 * named "i" + something, so that rendered the same "I" on every tile and made
 * the grid unreadable. It drops that shared prefix and keeps what actually
 * distinguishes the app, over an accent colour so no two tiles look alike.
 */

/**
 * Accents are assigned, not hashed. With barely a dozen applications any hash
 * collides — measured, 7 names landed on 5 colours — and the catalogue is a
 * curated list, so naming the colour is both shorter and predictable. Each one
 * is a different hue family, which is what makes tiles separable at a glance.
 */
const ACCENTS: Record<string, string> = {
  iSDR: "from-blue-500 to-indigo-600",
  iDeck: "from-violet-500 to-purple-600",
  iPlanning: "from-emerald-500 to-green-600",
  iQuality: "from-amber-500 to-orange-600",
  iCustomer: "from-fuchsia-500 to-pink-600",
  iDismantling: "from-red-500 to-rose-600",
  iKanban: "from-cyan-500 to-teal-600",
  iDashboard: "from-sky-500 to-cyan-600",
  iALB: "from-lime-500 to-green-600",
  iTech: "from-slate-500 to-slate-700",
  iAsset: "from-yellow-500 to-amber-600",
};

/** Applications not in the table above still need a stable colour. */
const FALLBACK_ACCENTS = [
  "from-blue-500 to-indigo-600",
  "from-violet-500 to-purple-600",
  "from-emerald-500 to-green-600",
  "from-amber-500 to-orange-600",
  "from-fuchsia-500 to-pink-600",
  "from-red-500 to-rose-600",
];

/**
 * "iSDR" → "SD", "iDeck" → "DE", "iKanban" → "KA".
 *
 * The leading "i" is dropped only when a letter follows it, so a name that does
 * not follow the convention keeps its own initials.
 */
export function markLabel(name: string): string {
  const trimmed = name.trim();
  const stem = /^i[A-Za-z]/.test(trimmed) ? trimmed.slice(1) : trimmed;

  return (stem.replace(/[^A-Za-z0-9]/g, "").slice(0, 2) || "?").toUpperCase();
}

export function markAccent(name: string): string {
  const assigned = ACCENTS[name];
  if (assigned) {
    return assigned;
  }

  let hash = 0;
  for (let i = 0; i < name.length; i++) {
    hash = (hash * 31 + name.charCodeAt(i)) | 0;
  }

  return FALLBACK_ACCENTS[Math.abs(hash) % FALLBACK_ACCENTS.length];
}

interface AppMarkProps {
  name: string;
  iconUrl?: string | null;
  className?: string;
}

export const AppMark = ({
  name,
  iconUrl,
  className = "w-12 h-12",
}: AppMarkProps) => {
  // A registered logo that fails to load must not leave a blank square, so a
  // broken image falls back to the generated mark rather than hiding itself.
  const [logoFailed, setLogoFailed] = useState(false);

  if (iconUrl && !logoFailed) {
    return (
      <div
        className={`${className} rounded-lg bg-white flex items-center justify-center p-1.5 shrink-0`}
      >
        <img
          src={iconUrl}
          alt={`${name} logo`}
          className="w-full h-full object-contain"
          onError={() => setLogoFailed(true)}
        />
      </div>
    );
  }

  return (
    <div
      className={`${className} rounded-lg bg-gradient-to-br ${markAccent(name)} flex items-center justify-center shrink-0`}
      aria-hidden="true"
    >
      <span className="text-white font-bold text-lg tracking-tight">
        {markLabel(name)}
      </span>
    </div>
  );
};
