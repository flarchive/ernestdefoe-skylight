import app from 'flarum/forum/app';
import { attributes, effective, Look, sanitize, stamp } from '../common/look';
import { parseJson, storage } from '../common/safe';

/**
 * The visitor's own Studio choices: user.preferences.skylight for a member,
 * localStorage for a guest (with the attributes the <head> script copies on
 * before first paint).
 */
const KEY = 'skylight';

export function own(): Partial<Look> {
  const user = app.session.user;
  if (user) return sanitize(user.preferences()?.skylight);

  return sanitize(
    parseJson<{ look?: unknown } | null>(
      storage((local) => local.getItem(KEY), null),
      null
    )?.look
  );
}

/** Put the visitor's look on the page (also the Studio's live preview). */
export function show(choices: Partial<Look> = own()): Look {
  const look = effective(choices);
  stamp(look, 'glass' in choices);
  return look;
}

export async function save(choices: Partial<Look>): Promise<void> {
  const clean = sanitize(choices);
  const user = app.session.user;

  if (user) {
    await user.savePreferences({ skylight: clean });
    return;
  }

  if (!Object.keys(clean).length) {
    storage((local) => local.removeItem(KEY), undefined);
    return;
  }

  const { a, s } = attributes(effective(clean));
  if ('glass' in clean) a['data-sky-glass-set'] = '1';
  storage((local) => local.setItem(KEY, JSON.stringify({ look: clean, a, s })), undefined);
}
