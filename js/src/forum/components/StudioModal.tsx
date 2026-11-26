import app from 'flarum/forum/app';
import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';
import Icon from 'flarum/common/components/Icon';
import Stream from 'flarum/common/utils/Stream';
import LookControls, { Segmented } from '../../common/components/LookControls';
import { CURATED, decode, effective, encode, Look, LookKey, openKeys, payload } from '../../common/look';
import { own, save, show } from '../own';
import { currentView, reloadIfNeeded } from '../listViews';
import { storage } from '../../common/safe';

const t = (key: string, params?: any) => app.translator.trans(`ernestdefoe-skylight.forum.studio.${key}`, params);

/**
 * The member Studio. Every change shows on the page at once (the page itself
 * is the preview); Done keeps it, closing any other way puts the old look
 * back. Loaded on first open, so it costs nothing until then.
 */
export default class StudioModal extends Modal<IInternalModalAttrs> {
  draft!: Partial<Look>;
  saved!: Partial<Look>;
  savedScheme!: string;
  done = false;
  code = Stream('');
  codeError = false;

  oninit(vnode: any) {
    super.oninit(vnode);
    this.saved = own();
    this.draft = { ...this.saved };
    this.savedScheme = String(app.colorScheme || 'auto');
  }

  className() {
    return 'Skylight-studio Modal--small';
  }

  title() {
    return [<Icon name="fas fa-sliders-h" className="Skylight-studioIcon" />, ' ', t('title')];
  }

  content() {
    const p = payload();
    const look = effective(this.draft, p);

    return (
      <div className="Modal-body Skylight-studioBody">
        <p className="Skylight-studioLead">{t('lead')}</p>
        <LookControls
          look={look}
          sections={p.sections}
          presets={p.presets}
          backgrounds={p.backgrounds}
          before={app.allowUserColorScheme && this.mode()}
          onchange={(key: LookKey, value: any) => this.set({ [key]: value })}
        />
        {p.sections.includes('share') && this.share(look)}
        <div className="Skylight-studioFoot">
          <Button className="Button" icon="fas fa-random" onclick={() => this.shuffle()}>
            {t('shuffle')}
          </Button>
          <Button className="Button" onclick={() => this.reset()}>
            {t('reset')}
          </Button>
          <Button className="Button Button--primary" loading={this.loading} onclick={() => this.finish()}>
            {t('done')}
          </Button>
        </div>
      </div>
    );
  }

  mode() {
    const scheme = String(app.colorScheme || 'auto').replace(/-hc$/, '');
    const options: [string, any][] = [
      ['light', [<Icon name="far fa-sun" />, ' ', t('mode.light')]],
      ['dark', [<Icon name="far fa-moon" />, ' ', t('mode.dark')]],
      ['auto', [<Icon name="fas fa-adjust" />, ' ', t('mode.auto')]],
    ];

    return (
      <fieldset className="Skylight-section Skylight-section--mode">
        <legend>{t('mode.label')}</legend>
        {Segmented(t('mode.label'), scheme, options, (v) => app.setColorScheme(v))}
      </fieldset>
    );
  }

  share(look: Look) {
    return (
      <fieldset className="Skylight-section Skylight-section--share">
        <legend>{t('share.label')}</legend>
        <div className="Skylight-share">
          <code>{encode(look)}</code>
          <Button className="Button" icon="far fa-copy" onclick={() => this.copy(encode(look))}>
            {t('share.copy')}
          </Button>
        </div>
        <div className="Skylight-share">
          <input
            className="FormControl"
            placeholder="SKY-…"
            aria-label={t('share.paste_label') as any}
            bidi={this.code}
            aria-invalid={this.codeError ? 'true' : undefined}
          />
          <Button className="Button" onclick={() => this.apply()}>
            {t('share.apply')}
          </Button>
        </div>
        {this.codeError && <p className="Skylight-note Skylight-note--error">{t('share.invalid')}</p>}
      </fieldset>
    );
  }

  copy(code: string) {
    // Without clipboard access (an insecure origin, or the visitor said no) the code stays on screen to copy by hand.
    navigator.clipboard?.writeText(code).then(
      () => app.alerts.show({ type: 'success' }, t('share.copied')),
      () => app.alerts.show({ type: 'error' }, t('share.copy_failed'))
    );
  }

  set(changes: Partial<Look>) {
    // Only what the member may change, so a locked section stays the forum's.
    const open = openKeys(payload().sections);
    for (const [k, v] of Object.entries(changes)) if (open.includes(k as LookKey)) (this.draft as any)[k] = v;
    this.preview(this.draft);
  }

  /** Show a look on the page; the list reloads if Cards came or went. */
  preview(choices: Partial<Look>) {
    const before = currentView();
    show(choices);
    reloadIfNeeded(before);
  }

  shuffle() {
    const pick = CURATED[Math.floor(Math.random() * CURATED.length)];
    this.set(pick);
  }

  reset() {
    this.draft = {};
    this.preview(this.draft);
  }

  apply() {
    const look = decode(this.code());
    this.codeError = !look;
    if (look) {
      this.set(look);
      this.code('');
    }
  }

  async finish() {
    this.loading = true;
    try {
      await save(this.draft);
      if (app.session.user && String(app.colorScheme) !== this.savedScheme) {
        await app.session.user.savePreferences({ colorScheme: app.colorScheme });
      } else if (!app.session.user) {
        // Where core keeps a guest's scheme.
        storage((local, session) => session.setItem('colorScheme', String(app.colorScheme)), undefined);
      }
      this.done = true;
      this.hide();
    } finally {
      this.loading = false;
      m.redraw();
    }
  }

  onremove(vnode: any) {
    super.onremove(vnode);
    if (!this.done) {
      this.preview(this.saved);
      app.setColorScheme(this.savedScheme);
    }
  }
}
