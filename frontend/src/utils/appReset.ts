/**
 * Resetting the app — every place data hides between this hub and the screen.
 *
 * Ported from iDeck/iPlanning's "Reset app" button (Trello #67). Unlike those
 * apps, iHub's backend keeps no server-side AMS cache of its own (no
 * `/api/cache` endpoint exists here), so this file only drops what a browser
 * itself can hold: the backend session (an httpOnly cookie nothing else in
 * this file can reach), localStorage/sessionStorage/cookies/IndexedDB, and the
 * build's own service worker + Cache Storage entries.
 *
 * Every step is best-effort. A browser that refuses to enumerate its caches,
 * or a backend that is down, must not be able to fail the wipe: what cannot be
 * cleared is skipped, and the reload still happens.
 */

/**
 * Ends the backend session.
 *
 * Reuses the same endpoint as the header's sign-out (`authService.logout`,
 * `POST /api/auth/logout`) so a reset leaves no session behind for AMS to
 * resume, but is called directly here rather than through `authService`: the
 * button lives on the login page, which may be reached with no session (and
 * therefore no store) at all, and this call must not depend on either.
 */
export async function endServerSession(): Promise<void> {
  try {
    await fetch("/api/auth/logout", { method: "POST", credentials: "include" });
  } catch {
    // Offline, or the backend is down: the local wipe below still stands.
  }
}

/** Everything this browser stores for the app: both storages, cookies, IndexedDB. */
export async function clearClientData(): Promise<void> {
  try {
    localStorage.clear();
  } catch {
    // Private mode, or a quota error on a storage that is already empty.
  }
  try {
    sessionStorage.clear();
  } catch {
    // Same.
  }
  try {
    document.cookie.split(";").forEach((cookie) => {
      document.cookie =
        cookie.trim().split("=")[0] +
        "=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/";
    });
  } catch {
    // Some embedded webviews forbid writing document.cookie.
  }
  try {
    const databases = await indexedDB.databases?.();
    for (const database of databases ?? []) {
      if (database.name) indexedDB.deleteDatabase(database.name);
    }
  } catch {
    // Firefox lacks `databases()`; the rest of the wipe still stands.
  }
}

/**
 * The build itself — service workers, and the Cache Storage entries they keep.
 *
 * This is the step that answers "I deployed and I still see the old screen".
 */
export async function clearAppCode(): Promise<void> {
  try {
    const registrations = await navigator.serviceWorker?.getRegistrations?.();
    for (const registration of registrations ?? []) {
      await registration.unregister();
    }
  } catch {
    // No service worker, or the browser refuses to enumerate them.
  }
  try {
    const keys = await caches?.keys?.();
    for (const key of keys ?? []) await caches.delete(key);
  } catch {
    // The Cache Storage API is unavailable over plain http on some browsers.
  }
}

/**
 * Boots the app from a URL the browser has never seen.
 *
 * A cache-busting query rather than `location.reload()`: a reload revalidates
 * and can be answered with a 304 for `index.html`, which then points at the
 * very bundle we are trying to leave behind. It is also the only way to be
 * sure no in-memory state survives — a client-side route change cannot reach
 * module scope.
 */
export function reloadFresh(): void {
  const url = new URL(window.location.href, window.location.origin);
  url.searchParams.set("reset", String(Date.now()));
  window.location.replace(url.toString());
}

/**
 * The full wipe, in the order that leaves nothing behind: end the session
 * while the browser can still be asked, then drop every byte it is holding,
 * then the build.
 *
 * Does **not** reload — the caller decides, and it is the reload that the
 * user actually sees.
 */
export async function clearEverything(): Promise<void> {
  await endServerSession();
  await clearClientData();
  await clearAppCode();
}
