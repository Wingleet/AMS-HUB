# CLAUDE.md — ams-hub (iHub)

> Instructions for AI assistants (Claude, Copilot, …) working on this
> repository. Symfony backend in `backend/`, React frontend in `frontend/`, plus `SSO_App/`.
>
> All code, comments, documentation and commit messages in this project are
> written in **English**.

---

## 🚀 Staging deploys itself — push to `staging`

`git push origin staging` updates https://staging-ihub.wingleetdev.com on its own. No SSH, no
manual `make` on the server.

```
git push origin staging
        │
        ▼
.github/workflows/deploy-staging.yml   (GitHub-hosted runner)
        │  ssh -i <deploy key> debian@162.19.127.20 "<commit sha>"
        ▼
~debian/.ssh/authorized_keys
        │  command="/usr/local/bin/deploy-staging ihub",restrict
        ▼
/usr/local/bin/deploy-staging ihub
        │  flock → update the checkout → make deploy-staging
        │  → health check → rollback to the previous commit on failure
        ▼
output streamed back into the Actions log
```

| | |
| --- | --- |
| GitHub repo | `Wingleet/AMS-HUB` |
| Deployed branch | `staging` |
| Checkout on the host | `/var/www/ams-ihub` |
| Build command | ``make deploy-staging` — compose build **plus** `doctrine:migrations:migrate`, `cache:clear` and `assets:install`` |
| Host settings | `/etc/staging-deploy/ihub.conf` |
| URL | https://staging-ihub.wingleetdev.com |
| Deployment log | `/var/log/staging-deploy/ihub.log` |

The deploy key is registered as a *forced command*: it cannot open a shell and
cannot touch the other applications sharing that server (iDeck, iKanban,
iCustomer, iHub, iSDR, iDismantling and iAsset all live behind one Caddy proxy
on 162.19.127.20).

### What a push does **not** deploy

- `.env.staging` lives on the server only. Adding an environment variable means
  editing that file on the host **before** pushing, otherwise the container
  starts without it.
- ⚠️ This application's deployment **runs Doctrine migrations** on the staging
  database, because that is what its own `deploy-staging` target does and a code
  change often needs them. A migration that destroys data will therefore be
  applied automatically: review migrations before pushing to `staging`.

### When it fails

The script health-checks the stack and returns to the previous commit when the
build or the check fails, so a broken push does not leave the recette down. The
run shows red in the **Actions** tab, and its whole server-side output is in the
run log.

### Rules for an assistant

- **Never deploy by hand over SSH** when a push to `staging` can do it.
- **Never `git clean -fdx`** in the checkout on the host: untracked files there
  (`.env.staging`, runtime data) are part of the running configuration.
- To redeploy the same commit, use **Actions → Deploy staging → Run workflow**,
  not a manual SSH session.

---

## 🔢 Versioning — always bump, proportionally

The app version is displayed to users in the sidebar. It is the only way anyone
looking at a running instance can tell **which build they are on**, so a shipped
change that leaves it untouched makes every bug report ambiguous.

**Every change to the code MUST bump the version.** Do it as part of the change
itself, not as a follow-up commit.

### Single source of truth

This project has **no version file yet**. Create one, mirroring the sibling
applications (`ams-tarmac`, `tos-digital-sdr`): `frontend/APP_VERSION.ts`
exporting the version as a default string, rendered in the shell's sidebar.
Keep `frontend/package.json`'s `version` field in sync with it — same number,
both files, same commit.

### How much to bump — `MAJOR.MINOR.PATCH`

The increment must be **proportional to the weight of the change**. Judge it by
what the change means to a user of the app, not by the diff's line count.

| Bump | When | Example |
| --- | --- | --- |
| **PATCH** `1.0.0`→`1.0.1` | Hotfix, bug fix, copy/label change, styling touch-up, refactor with no visible effect, test-only change. | A column sorted on the wrong value; a spinner using the wrong tone. |
| **MINOR** `1.0.1`→`1.1.0` | A new feature, a new page or view, a new endpoint, a meaningful capability added to an existing screen. | A new Gantt view; a new filter panel. |
| **MAJOR** `1.1.0`→`2.0.0` | A breaking or structural change: navigation reorganised, a module removed or replaced, persistence relocated, an API contract change that makes older builds incompatible. | Moving a module's storage to another backend. |

Rules that keep the numbering honest:

- **One bump per shipped change**, not one per commit inside it. If a branch
  already bumped to `1.1.0` and you add another patch to the same branch,
  `1.1.0` still covers it — only bump again if the added work outweighs the
  bump already made (patch branch that grows a feature → make it a MINOR).
- **The largest change wins.** A branch containing a feature *and* three bug
  fixes is a MINOR bump, not a MINOR plus three PATCHes.
- **Resetting lower digits is mandatory:** a MINOR bump zeroes PATCH, a MAJOR
  bump zeroes both.
- **Never invent a fourth digit**, a suffix, or a build number.
- **If the weight is genuinely ambiguous**, choose the smaller bump and say so
  in the commit message — an under-stated MINOR is recoverable, an inflated
  MAJOR is not.

### Every bump gets a git tag

A version number that exists only in the source is not traceable: the sidebar
says `v1.1.0` but nothing says **which commit** that is. So **each time the
version changes, the released commit MUST be tagged and the tag pushed.**

Tag format: `v` + the exact version — `v1.0.0`, `v1.0.1`, `v1.1.0`. Nothing else
(no `release-`, no date, no suffix).

```bash
# On the commit that carries the bump, once it is merged/pushed
git tag -a v1.1.0 -m "v1.1.0 — <what this version contains>"
git push origin v1.1.0
```

- **Tag the commit that ships**, i.e. after the change is merged into the branch
  being deployed — never a local commit that may still be rebased. An annotated
  tag (`-a`) is required, so the tag carries a message.
- **The message states what the version contains** in one line, at the same
  altitude as the bump: the feature for a MINOR, the fix for a PATCH.
- **The tag must be pushed** (`git push origin <tag>`); a local-only tag is
  invisible on the forge and therefore useless.
- **Tags are immutable.** Never move or force-push one. A mistake is corrected
  by a new version and a new tag, not by rewriting the old.
- **One tag per version**, and every version has one — no gaps, no reuse.

⚠️ Tagging and pushing are outward-facing actions: **ask the user before
creating or pushing a tag**, unless they have already told you to ship the
release.
