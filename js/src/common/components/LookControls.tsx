import app from 'flarum/common/app';
import Component, { ComponentAttrs } from 'flarum/common/Component';
import Switch from 'flarum/common/components/Switch';
import Icon from 'flarum/common/components/Icon';
import type Mithril from 'mithril';
import { accents, hex } from '../color';
import { CHOICES, Look, LookKey, PRESETS } from '../look';

export interface LookControlsAttrs extends ComponentAttrs {
  look: Look;
  /** Called with one changed key at a time. */
  onchange: (key: LookKey, value: any) => void;
  /** Studio sections to show (readability is shown when `readability` is true). */
  sections: string[];
  readability?: boolean;
  presets: string[];
  /** The presets take the built-in six's place instead of joining them. */
  presetsReplace?: boolean;
  backgrounds: Record<string, string>;
  /** Shown above the sections (the Studio puts Mode here). */
  before?: Mithril.Children;
}

const t = (key: string, params?: any) => app.translator.trans(`ernestdefoe-skylight.lib.${key}`, params);

/** A row of choices that behaves as one radio group. */
export function Segmented(label: Mithril.Children, value: string, options: [string, Mithril.Children][], onchange: (v: string) => void) {
  return (
    <div className="Skylight-seg" role="radiogroup" aria-label={typeof label === 'string' ? label : undefined}>
      {options.map(([v, text]) => (
        <button
          type="button"
          role="radio"
          aria-checked={v === value ? 'true' : 'false'}
          className={v === value ? 'on' : ''}
          onclick={() => onchange(v)}
        >
          {text}
        </button>
      ))}
    </div>
  );
}

/**
 * Every Studio control. Used by the member Studio and by the admin page for
 * the forum's defaults, so the two can never drift apart.
 */
export default class LookControls extends Component<LookControlsAttrs> {
  view() {
    const { look, sections } = this.attrs;
    const has = (s: string) => sections.includes(s);

    return (
      <div className="Skylight-controls">
        {this.attrs.before}
        {has('accent') && this.section('accent', this.accent())}
        {has('view') && this.section('view', this.listView())}
        {has('background') && this.section('background', [this.backgrounds(), this.row('night', this.choice('night'))])}
        {has('glass') &&
          this.section(
            'glass',
            <div>
              {this.choice('glass')}
              {look.glass === 'glass' && this.range('blur', 4, 20, 1, `${look.blur} px`)}
            </div>
          )}
        {(has('corners') || has('density')) &&
          this.section(
            'shape',
            <div>
              {has('corners') && this.row('radius', this.choice('radius'))}
              {has('density') && this.row('density', this.choice('density'))}
            </div>
          )}
        {(has('text') || has('typeface') || has('motion')) &&
          this.section(
            'text',
            <div>
              {has('text') && this.range('scale', 90, 120, 5, `${look.scale} %`)}
              {has('typeface') && this.row('font', this.choice('font'))}
              {has('motion') && this.row('motion', this.choice('motion'))}
            </div>
          )}
        {this.attrs.readability !== false &&
          this.section(
            'readability',
            <div className="Skylight-toggles">
              {this.toggle('rt')}
              {this.toggle('hc')}
            </div>
          )}
      </div>
    );
  }

  /** Discussion list: the view, then where its tags go. */
  listView() {
    return (
      <div>
        {this.choice('view', { list: 'fas fa-list', cards: 'fas fa-th-large', compact: 'fas fa-bars' })}
        {this.row('tags', this.choice('tags'))}
      </div>
    );
  }

  section(key: string, body: Mithril.Children) {
    return (
      <fieldset className={`Skylight-section Skylight-section--${key}`}>
        <legend>{t(`sections.${key}`)}</legend>
        {body}
      </fieldset>
    );
  }

  row(key: LookKey, control: Mithril.Children) {
    return (
      <div className="Skylight-row">
        <span className="Skylight-rowLabel">{t(`labels.${key}`)}</span>
        {control}
      </div>
    );
  }

  choice(key: keyof typeof CHOICES, icons: Record<string, string> = {}) {
    const options = (CHOICES[key] as readonly string[]).map(
      (v) => [v, [icons[v] && <Icon name={icons[v]} />, ' ', t(`choices.${key}.${v}`)]] as [string, Mithril.Children]
    );

    return Segmented(t(`labels.${key}`), String(this.attrs.look[key]), options, (v) => this.attrs.onchange(key, v));
  }

  range(key: 'blur' | 'scale', min: number, max: number, step: number, readout: string) {
    const id = `skylight-${key}`;
    return (
      <div className="Skylight-row">
        <label className="Skylight-rowLabel" for={id}>
          {t(`labels.${key}`)}
        </label>
        <input
          id={id}
          type="range"
          className="Skylight-range"
          min={min}
          max={max}
          step={step}
          value={this.attrs.look[key]}
          style={{ '--v': `${((Number(this.attrs.look[key]) - min) / (max - min)) * 100}%` }}
          oninput={(e: InputEvent) => this.attrs.onchange(key, Number((e.target as HTMLInputElement).value))}
        />
        <output for={id}>{readout}</output>
      </div>
    );
  }

  toggle(key: 'rt' | 'hc') {
    return (
      <Switch state={this.attrs.look[key]} onchange={(v: boolean) => this.attrs.onchange(key, v)}>
        <span className="Skylight-toggleText">
          {t(`labels.${key}`)}
          <small>{t(`help.${key}`)}</small>
        </span>
      </Switch>
    );
  }

  accent() {
    const current = this.attrs.look.accent;
    const replace = this.attrs.presetsReplace && this.attrs.presets.length > 0;
    const swatches: [string, string][] = replace ? [] : Object.entries(PRESETS).map(([id, h]) => [h, t(`accents.${id}`) as unknown as string]);
    this.attrs.presets.forEach((h) => swatches.push([h, h]));
    const known = swatches.some(([h]) => h === current);
    const nudged = accents(current).nudged;

    return (
      <div>
        <div className="Skylight-swatches" role="radiogroup" aria-label={t('sections.accent') as any}>
          {swatches.map(([h, name]) => (
            <button
              type="button"
              role="radio"
              aria-checked={h === current ? 'true' : 'false'}
              aria-label={name}
              title={name}
              className={'Skylight-swatch' + (h === current ? ' on' : '')}
              style={{ '--swatch': h }}
              onclick={() => this.attrs.onchange('accent', h)}
            />
          ))}
          <label className={'Skylight-swatch Skylight-swatch--custom' + (!known ? ' on' : '')} title={t('labels.custom_accent') as any}>
            <input
              type="color"
              value={current}
              aria-label={t('labels.custom_accent') as any}
              oninput={(e: InputEvent) => {
                const h = hex((e.target as HTMLInputElement).value);
                if (h) this.attrs.onchange('accent', h);
              }}
            />
          </label>
        </div>
        {nudged && <p className="Skylight-note">{t('help.nudged')}</p>}
      </div>
    );
  }

  backgrounds() {
    const current = this.attrs.look.bg;
    const tiles: [string, Mithril.Children, string?][] = (['daylight', 'mist', 'dusk', 'plain'] as const).map((v) => [v, t(`choices.bg.${v}`)]);
    Object.entries(this.attrs.backgrounds).forEach(([slot, url], i) => tiles.push([slot, t('choices.bg.image', { number: i + 1 }), url]));

    return (
      <div className="Skylight-bgs" role="radiogroup" aria-label={t('sections.background') as any}>
        {tiles.map(([v, name, url]) => (
          <button
            type="button"
            role="radio"
            aria-checked={v === current ? 'true' : 'false'}
            className={`Skylight-bg Skylight-bg--${url ? 'img' : v}` + (v === current ? ' on' : '')}
            style={url ? { backgroundImage: `url("${url.replace(/"/g, '%22')}")` } : undefined}
            onclick={() => this.attrs.onchange('bg', v)}
          >
            <span>{name}</span>
          </button>
        ))}
      </div>
    );
  }
}
