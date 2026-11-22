# Skylight (ernestdefoe/skylight)

Glass-and-daylight theme for Flarum 2 with a member Studio. Working name: the
name and pricing are confirmed with Ernest before release, so keep "Skylight"
in composer.json `extra.flarum-extension.title`, locale strings and this file only.
Scope and mockups: ~/github/scopes/glass-theme/. Local forum: https://skylightdev.test
(~/Herd/skylightdev, prefix sky_, admin password in ~/Herd/skylightdev-admin.txt).
Shots: `node ~/github/scopes/glass-theme/build/shots.mjs [filter]`.

- Every rule hangs off `html[data-sky]` (stamped by Content\Stamp only when
  ActiveTheme says yes): Wardrobe or Bespoke keep it inert for other visitors.
- Look.php <-> js/src/common/look.ts and Color.php <-> color.ts are mirrors.
- Tag accent: inline `--sky-tag-accent` is read FIRST in tokens.less (inline
  `--sky-accent` beats any stylesheet override).
- Cards excerpt: `filter[skylight]=cards` (no-op filter; the JSON:API refuses
  unknown query params), one buffered firstPost query, text from stored XML.
- Studio is a lazy chunk: `jsDirectory()` in extend.php, `cache:clear` after builds.
- After `npm run build`: `php flarum cache:clear` in the local forum.
