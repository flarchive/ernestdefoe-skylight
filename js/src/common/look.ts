import app from 'flarum/common/app';
import { accents, hex } from './color';
import { parseJson } from './safe';

/**
 * A "look": every Studio choice. Mirrors src/Look.php (keep the two in step).
 */
export interface Look {
  accent: string;
  bg: string;
  glass: string;
  blur: number;
  radius: string;
  density: string;
  scale: number;
  font: string;
  motion: string;
  view: string;
  tags: string;
  night: string;
  rt: boolean;
  hc: boolean;
}

export type LookKey = keyof Look;

export const SECTIONS: Record<string, LookKey[]> = {
  accent: ['accent'],
  view: ['view', 'tags'],
  background: ['bg', 'night'],
  glass: ['glass', 'blur'],
  corners: ['radius'],
  density: ['density'],
  text: ['scale'],
  typeface: ['font'],
  motion: ['motion'],
  share: [],
};

export const READABILITY: LookKey[] = ['rt', 'hc'];

export const CHOICES = {
  bg: ['daylight', 'mist', 'dusk', 'plain', 'img1', 'img2', 'img3', 'img4'],
  glass: ['glass', 'solid'],
  radius: ['sharp', 'soft', 'round'],
  density: ['comfortable', 'compact'],
  font: ['system', 'inter', 'serif'],
  motion: ['full', 'reduced', 'off'],
  view: ['list', 'cards', 'compact'],
  tags: ['beside', 'below'],
  night: ['blue', 'black'],
} as const;

export const BUILT_IN: Look = {
  accent: '#127c86',
  bg: 'daylight',
  glass: 'glass',
  blur: 14,
  radius: 'soft',
  density: 'comfortable',
  scale: 100,
  font: 'system',
  motion: 'full',
  view: 'list',
  tags: 'beside',
  night: 'blue',
  rt: false,
  hc: false,
};

/** The six built-in accents, named in the locale (skylight.lib.accents.*). */
export const PRESETS: Record<string, string> = {
  dawn: '#c2621a',
  lagoon: '#127c86',
  fern: '#2f7d3b',
  coral: '#c4434f',
  iris: '#6a4fc7',
  slate: '#4a5a70',
};

export interface Payload {
  defaults: Look;
  sections: string[];
  canUseStudio: boolean;
  tagAccent: boolean;
  presets: string[];
  /** The admin's presets take the built-in six's place (else they are added). */
  presetsReplace: boolean;
  /** One color for a discussion with several colored tags (null = off). */
  multiTagColor: string | null;
  backgrounds: Record<string, string>;
  blockedByBespoke?: boolean;
}

export function payload(): Payload {
  const p = (app.forum.attribute('skylight') as Payload | undefined) ?? ({} as Payload);
  return {
    defaults: { ...BUILT_IN, ...(p.defaults || {}) },
    sections: p.sections || Object.keys(SECTIONS),
    canUseStudio: !!p.canUseStudio,
    tagAccent: !!p.tagAccent,
    presets: p.presets || [],
    presetsReplace: !!p.presetsReplace,
    multiTagColor: hex(p.multiTagColor),
    // The PHP side sends {} for "none", which JSON may turn into [].
    backgrounds: Array.isArray(p.backgrounds) ? {} : p.backgrounds || {},
    blockedByBespoke: p.blockedByBespoke,
  };
}

/** Keep only known keys with valid values. */
export function sanitize(value: any): Partial<Look> {
  const look: Partial<Look> = {};
  if (!value || typeof value !== 'object') return look;

  for (const key of Object.keys(value) as LookKey[]) {
    const item = value[key];
    if (key in CHOICES) {
      if ((CHOICES as any)[key].includes(item)) (look as any)[key] = item;
    } else if (key === 'accent') {
      const h = hex(item);
      if (h) look.accent = h;
    } else if (key === 'blur' || key === 'scale') {
      const n = Number(item);
      if (isFinite(n)) look[key] = key === 'blur' ? Math.max(4, Math.min(20, Math.round(n))) : Math.max(90, Math.min(120, Math.round(n)));
    } else if (READABILITY.includes(key)) {
      (look as any)[key] = !!item;
    }
  }

  return look;
}

/** Look keys members may set: the open sections plus readability. */
export function openKeys(sections: string[]): LookKey[] {
  return sections.reduce((keys, section) => keys.concat(SECTIONS[section] || []), [...READABILITY]);
}

/** The forum's look with this member's open choices on top. */
export function effective(own: Partial<Look>, p: Payload = payload()): Look {
  const look = { ...p.defaults };
  const open = openKeys(p.sections);
  for (const [key, value] of Object.entries(sanitize(own)) as [LookKey, never][]) {
    if (open.includes(key)) look[key] = value;
  }
  return look;
}

/** Attributes and style for <html>; the same as Content\Stamp writes on the server. */
export function attributes(look: Look, p: Payload = payload()): { a: Record<string, string>; s: string } {
  const c = accents(look.accent);
  const img = look.bg.startsWith('img') && p.backgrounds[look.bg];
  return {
    a: {
      'data-sky': 'on',
      'data-sky-bg': look.bg,
      'data-sky-glass': look.glass,
      'data-sky-radius': look.radius,
      'data-sky-density': look.density,
      'data-sky-font': look.font,
      'data-sky-motion': look.motion,
      'data-sky-view': look.view,
      'data-sky-tags': look.tags,
      'data-sky-night': look.night,
      'data-sky-rt': look.rt ? 'on' : 'off',
      'data-sky-hc': look.hc ? 'on' : 'off',
    },
    s:
      `--sky-accent:${c.light};--sky-accent-dark:${c.dark};--sky-ink:${c.inkLight};--sky-ink-dark:${c.inkDark};` +
      `--sky-blur:${look.blur}px;--sky-scale:${look.scale / 100};` +
      (img ? `--sky-bg-img:url("${String(img).replace(/"/g, '%22')}");` : ''),
  };
}

const VARS = ['--sky-accent', '--sky-accent-dark', '--sky-ink', '--sky-ink-dark', '--sky-blur', '--sky-scale', '--sky-bg-img'];

/** Put a look on the page. Only while Skylight is the visitor's theme (the server stamped data-sky). */
export function stamp(look: Look, explicitGlass = false): void {
  const html = document.documentElement;
  if (!html.hasAttribute('data-sky')) return;

  const { a, s } = attributes(look);
  for (const [name, value] of Object.entries(a)) html.setAttribute(name, value);
  explicitGlass ? html.setAttribute('data-sky-glass-set', '1') : html.removeAttribute('data-sky-glass-set');

  // A low-memory phone keeps the solid fallback until the member picks glass themselves.
  if (html.hasAttribute('data-sky-auto-solid') && !explicitGlass) html.setAttribute('data-sky-glass', 'solid');

  VARS.forEach((v) => html.style.removeProperty(v));
  s.split(';')
    .filter(Boolean)
    .forEach((decl) => {
      const i = decl.indexOf(':');
      html.style.setProperty(decl.slice(0, i), decl.slice(i + 1));
    });
}

/* ---- Share codes: SKY- + accent + one character per choice. ---- */

// New choices go on the END, so codes made before them still read (1.0 codes
// have seven choice characters; the two missing ones keep the current look).
const ORDER = ['bg', 'glass', 'radius', 'density', 'font', 'motion', 'view', 'night', 'tags'] as const;

export function encode(look: Look): string {
  return (
    'SKY-' +
    look.accent.slice(1) +
    ORDER.map((k) => (CHOICES[k] as readonly string[]).indexOf(look[k]).toString(36)).join('') +
    look.blur.toString(36) +
    (look.scale - 90).toString(36) +
    (look.rt ? 1 : 0) +
    (look.hc ? 1 : 0)
  ).toUpperCase();
}

export function decode(code: string): Partial<Look> | null {
  const m = /^SKY-([0-9A-F]{6})([0-9A-Z]{7}(?:[0-9A-Z]{2})?)([0-9A-Z])([0-9A-Z])([01])([01])$/i.exec(code.trim());
  if (!m) return null;
  const look: any = { accent: '#' + m[1] };
  ORDER.forEach((k, i) => i < m[2].length && (look[k] = (CHOICES[k] as readonly string[])[parseInt(m[2][i], 36)]));
  look.blur = parseInt(m[3], 36);
  look.scale = parseInt(m[4], 36) + 90;
  look.rt = m[5] === '1';
  look.hc = m[6] === '1';
  return sanitize(look);
}

/** Ten curated looks for Shuffle. */
export const CURATED: Partial<Look>[] = [
  { accent: '#127c86', bg: 'daylight', glass: 'glass', radius: 'soft', font: 'system' },
  { accent: '#c2621a', bg: 'daylight', glass: 'glass', radius: 'round', font: 'serif' },
  { accent: '#6a4fc7', bg: 'mist', glass: 'glass', radius: 'round', font: 'inter' },
  { accent: '#2f7d3b', bg: 'mist', glass: 'solid', radius: 'soft', font: 'system' },
  { accent: '#c4434f', bg: 'dusk', glass: 'glass', radius: 'soft', font: 'inter' },
  { accent: '#4a5a70', bg: 'plain', glass: 'solid', radius: 'sharp', font: 'system' },
  { accent: '#0f6fb8', bg: 'daylight', glass: 'glass', radius: 'sharp', font: 'inter' },
  { accent: '#9a4fb0', bg: 'dusk', glass: 'glass', radius: 'round', font: 'serif' },
  { accent: '#b0821a', bg: 'mist', glass: 'glass', radius: 'soft', font: 'serif' },
  { accent: '#1f8a6f', bg: 'plain', glass: 'glass', radius: 'round', font: 'system' },
];

/** A list of hex colors from a JSON array or a comma-separated string. */
export function hexList(value: unknown): string[] {
  if (typeof value !== 'string') return [];
  const parsed = parseJson<unknown>(value, null);
  const items: unknown[] = Array.isArray(parsed) ? parsed : value.trim().startsWith('[') ? [] : value.split(/[\s,]+/);
  return items.map((v) => hex(v)).filter((v): v is string => !!v);
}
