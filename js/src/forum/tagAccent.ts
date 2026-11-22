import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import Page from 'flarum/common/components/Page';
import IndexPage from 'flarum/forum/components/IndexPage';
import DiscussionPage from 'flarum/forum/components/DiscussionPage';
import { accents } from '../common/color';
import { payload } from '../common/look';

/**
 * Inside a tag (its list, or a discussion in it), buttons, links and focus
 * rings take the tag's color, nudged until it reads well in both schemes.
 * Leaves the page's accent alone everywhere else.
 */
function tagOf(page: any): any {
  const discussion = app.current.get('discussion');
  if (page instanceof DiscussionPage && discussion) {
    const tags = (discussion as any).tags?.() || [];
    return tags.find((tag: any) => tag && tag.color() && !tag.parent?.()) || tags.find((tag: any) => tag && tag.color());
  }
  if (page instanceof IndexPage) return (app as any).currentTag?.();
  return null;
}

function sync(page: any) {
  const html = document.documentElement;
  if (!html.hasAttribute('data-sky')) return;

  const color = payload().tagAccent ? tagOf(page)?.color?.() : null;
  const current = html.getAttribute('data-sky-tag-color');
  if ((color || null) === current) return;

  if (!color) {
    html.removeAttribute('data-sky-tag-color');
    ['--sky-tag-accent', '--sky-tag-accent-dark', '--sky-tag-ink', '--sky-tag-ink-dark'].forEach((v) => html.style.removeProperty(v));
    return;
  }

  const c = accents(color);
  html.setAttribute('data-sky-tag-color', color);
  html.style.setProperty('--sky-tag-accent', c.light);
  html.style.setProperty('--sky-tag-accent-dark', c.dark);
  html.style.setProperty('--sky-tag-ink', c.inkLight);
  html.style.setProperty('--sky-tag-ink-dark', c.inkDark);
}

export default function tagAccent() {
  if (!('flarum-tags' in (flarum as any).extensions)) return;

  extend(Page.prototype, 'oncreate', function () {
    sync(this);
  });
  extend(Page.prototype, 'onupdate', function () {
    sync(this);
  });
}
