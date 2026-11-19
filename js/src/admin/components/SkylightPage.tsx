import app from 'flarum/admin/app';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import Form from 'flarum/common/components/Form';
import Switch from 'flarum/common/components/Switch';
import Alert from 'flarum/common/components/Alert';
import UploadImageButton from 'flarum/common/components/UploadImageButton';
import type Mithril from 'mithril';
import LookControls from '../../common/components/LookControls';
import { accents, contrast } from '../../common/color';
import { BUILT_IN, hexList, Look, LookKey, payload, sanitize, SECTIONS } from '../../common/look';
import { parseJson } from '../../common/safe';

const t = (key: string, params?: any) => app.translator.trans(`ernestdefoe-skylight.admin.${key}`, params);
const SLOTS = ['img1', 'img2', 'img3', 'img4'];
const KEYS = {
  defaults: 'ernestdefoe-skylight.defaults',
  sections: 'ernestdefoe-skylight.sections',
  presets: 'ernestdefoe-skylight.presets',
};

/**
 * Skylight's settings: the forum's defaults (the same controls members get
 * with Personalize), which sections members may change, the extra accent
 * presets, the backgrounds and the two switches.
 */
export default class SkylightPage extends ExtensionPage {
  content(vnode: Mithril.VnodeDOM<any, this>) {
    return (
      <div className="ExtensionPage-settings SkylightAdmin">
        <div className="container">
          {payload().blockedByBespoke && (
            <Alert type="warning" dismissible={false} className="SkylightAdmin-alert">
              {t('bespoke_notice')}
            </Alert>
          )}
          <Form>
            <div className="SkylightAdmin-grid">
              {this.defaultsCard()}
              <div className="SkylightAdmin-column">
                {this.sectionsCard()}
                {this.presetsCard()}
                {this.backgroundsCard()}
                {this.card('more.title', null, [
                  this.switch('ernestdefoe-skylight.tag_accent', 'more.tag_accent'),
                  this.switch('ernestdefoe-skylight.auto_solid', 'more.auto_solid'),
                ])}
              </div>
            </div>
            <div className="Form-group Form-controls">{this.submitButton()}</div>
          </Form>
        </div>
      </div>
    );
  }

  card(title: string, help: string | null, body: Mithril.Children) {
    return (
      <section className="SkylightAdmin-card">
        <h3>{t(title)}</h3>
        {help && <p className="helpText">{t(help)}</p>}
        {body}
      </section>
    );
  }

  defaultsCard() {
    const defaults = this.defaults();

    return this.card('defaults.title', 'defaults.help', [
      <LookControls
        look={defaults}
        sections={Object.keys(SECTIONS)}
        readability={false}
        presets={hexList(this.setting(KEYS.presets)())}
        backgrounds={payload().backgrounds}
        onchange={(key: LookKey, value: any) => this.setDefault(key, value)}
      />,
      this.contrastReadout(defaults),
    ]);
  }

  sectionsCard() {
    const open = this.openSections();

    return this.card('studio.title', 'studio.help', [
      <div className="SkylightAdmin-checks">
        {Object.keys(SECTIONS).map((section) => (
          <Switch state={open.includes(section)} onchange={(on: boolean) => this.toggleSection(section, on)}>
            {app.translator.trans(`ernestdefoe-skylight.lib.sections.${section}`)}
          </Switch>
        ))}
      </div>,
      <p className="helpText">{t('studio.readability_always')}</p>,
      <p className="helpText">{t('studio.permission')}</p>,
    ]);
  }

  presetsCard() {
    const presets = this.setting(KEYS.presets);

    return this.card(
      'presets.title',
      'presets.help',
      <input
        className="FormControl"
        placeholder="#1f8a6f, #9a4fb0"
        value={hexList(presets()).join(', ')}
        onchange={(e: Event) => presets(JSON.stringify(hexList((e.target as HTMLInputElement).value).slice(0, 6)))}
      />
    );
  }

  backgroundsCard() {
    const urls = payload().backgrounds;

    return this.card(
      'backgrounds.title',
      'backgrounds.help',
      <div className="SkylightAdmin-uploads">
        {SLOTS.map((slot) => (
          <UploadImageButton
            name="skylight-bg"
            routePath={`skylight/backgrounds/${slot}`}
            value={app.data.settings[`ernestdefoe-skylight.bg_${slot}`]}
            url={urls[slot]}
          />
        ))}
      </div>
    );
  }

  switch(key: string, label: string) {
    const value = this.setting(key);
    return (
      <Switch state={!!Number(value()) || value() === 'true'} onchange={(on: boolean) => value(on ? '1' : '0')}>
        <span>
          {t(label)}
          <small className="helpText">{t(`${label}_help`)}</small>
        </span>
      </Switch>
    );
  }

  defaults(): Look {
    return { ...BUILT_IN, ...sanitize(parseJson(this.setting(KEYS.defaults)(), {})) };
  }

  setDefault(key: LookKey, value: any) {
    // Store only what differs from the built-in look.
    const diff: Record<string, unknown> = {};
    for (const [k, v] of Object.entries(sanitize({ ...this.defaults(), [key]: value }))) if ((BUILT_IN as any)[k] !== v) diff[k] = v;
    this.setting(KEYS.defaults)(JSON.stringify(diff));
  }

  openSections(): string[] {
    const list = parseJson<unknown>(this.setting(KEYS.sections)(), null);
    return Array.isArray(list) ? list : Object.keys(SECTIONS);
  }

  toggleSection(section: string, on: boolean) {
    const list = this.openSections().filter((s) => s !== section);
    if (on) list.push(section);
    this.setting(KEYS.sections)(JSON.stringify(Object.keys(SECTIONS).filter((s) => list.includes(s))));
  }

  contrastReadout(look: Look) {
    const c = accents(look.accent);
    const ratio = (n: number) => n.toFixed(1) + ' : 1';
    return (
      <p className="SkylightAdmin-contrast">
        <span>{t('defaults.contrast_light', { ratio: ratio(contrast(c.light, '#f4f7fa')) })}</span>
        <span>{t('defaults.contrast_dark', { ratio: ratio(contrast(c.dark, '#161c27')) })}</span>
        {c.nudged && <span className="nudged">{app.translator.trans('ernestdefoe-skylight.lib.help.nudged')}</span>}
      </p>
    );
  }
}
