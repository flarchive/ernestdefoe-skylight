/**
 * Accents that stay readable. Mirrors src/Color.php (keep the two in step):
 * the accent is nudged until it reaches 4.5:1 as text on Skylight's panes,
 * darker in the light scheme and lighter in the dark one.
 */
const LIGHT_PANE = '#f4f7fa';
const DARK_PANE = '#161c27';
const INK = '#0b1220';

export function hex(value: unknown): string | null {
  if (typeof value !== 'string') return null;
  const v = value.trim().toLowerCase().replace(/^#/, '');
  if (/^[0-9a-f]{3}$/.test(v)) return '#' + v.replace(/./g, (c) => c + c);
  return /^[0-9a-f]{6}$/.test(v) ? '#' + v : null;
}

function rgb(h: string): number[] {
  return [1, 3, 5].map((i) => parseInt(h.slice(i, i + 2), 16));
}

function luminance(h: string): number {
  const [r, g, b] = rgb(h).map((c) => {
    c /= 255;
    return c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4;
  });
  return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

export function contrast(a: string, b: string): number {
  const la = luminance(a);
  const lb = luminance(b);
  return (Math.max(la, lb) + 0.05) / (Math.min(la, lb) + 0.05);
}

function mix(a: string, b: string, amount: number): string {
  const ca = rgb(a);
  const cb = rgb(b);
  return (
    '#' +
    ca
      .map((c, i) =>
        Math.round(c + (cb[i] - c) * amount)
          .toString(16)
          .padStart(2, '0')
      )
      .join('')
  );
}

function reach(color: string, on: string, toward: string): string {
  for (let i = 0; i <= 25; i++) {
    const mixed = mix(color, toward, i * 0.04);
    if (contrast(mixed, on) >= 4.5) return mixed;
  }
  return toward;
}

function ink(background: string): string {
  return contrast('#ffffff', background) >= contrast(INK, background) ? '#ffffff' : INK;
}

export function accents(accent: string) {
  const base = hex(accent) ?? '#127c86';
  const light = reach(base, LIGHT_PANE, '#000000');
  const dark = reach(base, DARK_PANE, '#ffffff');
  return { light, dark, inkLight: ink(light), inkDark: ink(dark), nudged: light !== base };
}
