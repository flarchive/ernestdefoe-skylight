import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import IndexPage from 'flarum/forum/components/IndexPage';
import DiscussionListItem from 'flarum/forum/components/DiscussionListItem';
import DiscussionListState from 'flarum/forum/states/DiscussionListState';
import Tooltip from 'flarum/common/components/Tooltip';
import Button from 'flarum/common/components/Button';
import { payload } from '../common/look';
import { own, save, show } from './own';

const t = (key: string) => app.translator.trans(`ernestdefoe-skylight.forum.views.${key}`);

/** The view on the page right now (the server stamped it; the Studio changes it). */
export function currentView(): string {
  return document.documentElement.getAttribute('data-sky-view') || 'list';
}

/**
 * Switch the list's layout. Cards are the one view that needs more data (the
 * excerpt), so moving into or out of Cards reloads the list once.
 */
export function setView(view: string): void {
  const before = currentView();
  const choices = { ...own(), view };
  show(choices);
  save(choices);
  reloadIfNeeded(before);
}

/** After the view changed from `before`: reload the list if Cards came or went. */
export function reloadIfNeeded(before: string): void {
  if ((before === 'cards') !== (currentView() === 'cards') && app.discussions?.hasItems()) app.discussions.refresh();
}

/** The toolbar switch: three icons beside Refresh. */
function viewSwitch(): JSX.Element {
  const view = currentView();
  const icons: Record<string, string> = { list: 'fas fa-list', cards: 'fas fa-th-large', compact: 'fas fa-bars' };

  return (
    <div className="Skylight-viewSwitch" role="radiogroup" aria-label={t('label') as any}>
      {Object.entries(icons).map(([v, i]) => (
        <Tooltip text={t(v)}>
          <Button
            className={'Button Button--icon' + (v === view ? ' on' : '')}
            icon={i}
            role="radio"
            aria-checked={v === view ? 'true' : 'false'}
            aria-label={t(v) as any}
            onclick={() => setView(v)}
          />
        </Tooltip>
      ))}
    </div>
  );
}

function extendList() {
  // filter[skylight]=cards asks the API for each discussion's excerpt (one query per page).
  extend(DiscussionListState.prototype, 'requestParams', function (params: any) {
    if (document.documentElement.hasAttribute('data-sky') && currentView() === 'cards')
      params.filter = { ...(params.filter || {}), skylight: 'cards' };
  });

  // A guest on Cards: the list preloaded into the page was built without the
  // guest's browser-held choice, so it has no excerpts. Fetch it once more.
  extend(IndexPage.prototype, 'oncreate', function () {
    const first = app.discussions?.getPages()?.[0]?.items?.[0];
    if (currentView() === 'cards' && first && first.attribute('skylightExcerpt') === undefined) app.discussions.refresh();
  });

  extend(IndexPage.prototype, 'actionItems', function (items) {
    const p = payload();
    if (document.documentElement.hasAttribute('data-sky') && p.canUseStudio && p.sections.includes('view'))
      items.add('skylightView', viewSwitch(), 20);
  });
}

function extendRows() {
  // Each row carries its tag's color (the card's top edge) and, on Cards, its excerpt.
  extend(DiscussionListItem.prototype, 'elementAttrs', function (attrs: any) {
    const tags = (this.attrs.discussion as any).tags?.();
    const color = tags?.find((tag: any) => tag && tag.color())?.color();
    if (color) attrs.style = { ...(attrs.style || {}), '--sky-tag': color };
  });

  extend(DiscussionListItem.prototype, 'infoItems', function (items) {
    const excerpt = this.attrs.discussion.attribute('skylightExcerpt') as string | null;
    if (excerpt && !this.attrs.params.q) items.add('skylightExcerpt', <span className="Skylight-excerpt">{excerpt}</span>, -110);
  });
}

export default function listViews() {
  extendList();
  extendRows();
}
