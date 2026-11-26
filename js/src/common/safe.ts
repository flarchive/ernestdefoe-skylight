/**
 * The two places Skylight expects a throw it can't do anything about, so the
 * rest of the code doesn't need try/catch of its own.
 */

/** JSON that may be missing or malformed (old settings, hand-edited storage): the fallback instead. */
export function parseJson<T>(text: string | null | undefined, fallback: T): T {
  if (!text) return fallback;
  try {
    return JSON.parse(text) as T;
  } catch (e) {
    return fallback;
  }
}

/**
 * Browser storage, which throws in private windows or when the visitor blocks
 * site data. The look then lasts for this page only, which is all the visitor
 * asked for, so there's nothing to report.
 */
export function storage<T>(use: (local: Storage, session: Storage) => T, fallback: T): T {
  try {
    return use(window.localStorage, window.sessionStorage);
  } catch (e) {
    return fallback;
  }
}
