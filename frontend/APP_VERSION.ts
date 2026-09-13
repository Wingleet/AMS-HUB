/**
 * Single source of truth for this app's version — see CLAUDE.md → Versioning.
 *
 * This file did not exist before this change: iHub shipped with no version
 * tracking (`package.json` sat at the Vite default `0.0.0`). It starts here at
 * `1.0.0`, mirroring the sibling apps' convention, and every future change
 * must bump it (and `frontend/package.json`'s `version`, same number) in the
 * same commit, proportionally to the change's weight.
 */
export default "1.0.0";
