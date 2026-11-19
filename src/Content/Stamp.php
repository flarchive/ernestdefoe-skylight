<?php

/*
 * This file is part of ernestdefoe/skylight.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\Skylight\Content;

use ErnestDefoe\Skylight\ActiveTheme;
use ErnestDefoe\Skylight\Backgrounds;
use ErnestDefoe\Skylight\Color;
use ErnestDefoe\Skylight\Look;
use Flarum\Frontend\Document;
use Flarum\Http\RequestUtil;
use Flarum\Settings\SettingsRepositoryInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Themes the page on the server, before anything paints: the visitor's look
 * goes onto <html> as data-sky-* attributes and a few custom properties, read
 * from user.preferences that the request already loaded (no query).
 *
 * The one script (well under 1 KB) runs before the stylesheet and does what
 * the server can't know: a guest's look saved in this browser, and the solid
 * fallback on low-memory phones or Save-Data.
 */
final class Stamp
{
    public function __construct(
        protected ActiveTheme $theme,
        protected Look $look,
        protected Backgrounds $backgrounds,
        protected SettingsRepositoryInterface $settings,
    ) {
    }

    public function __invoke(Document $document, ServerRequestInterface $request): void
    {
        if (! $this->theme->for($request)) {
            return;
        }

        $actor = RequestUtil::getActor($request);
        ['look' => $look, 'own' => $own] = $this->look->for($actor);

        foreach (self::attributes($look) as $name => $value) {
            $document->extraAttributes[$name] = $value;
        }

        $style = self::style($look);
        if (str_starts_with((string) $look['bg'], 'img') && ($url = $this->backgrounds->url((string) $look['bg']))) {
            $style .= '--sky-bg-img:url("'.$url.'");';
        }
        $document->extraAttributes['style'] = $style;

        if (in_array('glass', $own, true)) {
            $document->extraAttributes['data-sky-glass-set'] = '1';
        }

        $guest = $actor->isGuest() && $this->look->canUseStudio($actor);
        $autoSolid = (bool) $this->settings->get('ernestdefoe-skylight.auto_solid');

        if ($guest || $autoSolid) {
            $document->preHead[] = '<script>'.self::script($guest, $autoSolid).'</script>';
        }
    }

    /**
     * @param array<string, string|int|bool> $look
     * @return array<string, string>
     */
    public static function attributes(array $look): array
    {
        return [
            'data-sky' => 'on',
            'data-sky-bg' => (string) $look['bg'],
            'data-sky-glass' => (string) $look['glass'],
            'data-sky-radius' => (string) $look['radius'],
            'data-sky-density' => (string) $look['density'],
            'data-sky-font' => (string) $look['font'],
            'data-sky-motion' => (string) $look['motion'],
            'data-sky-view' => (string) $look['view'],
            'data-sky-rt' => $look['rt'] ? 'on' : 'off',
            'data-sky-hc' => $look['hc'] ? 'on' : 'off',
        ];
    }

    /** @param array<string, string|int|bool> $look */
    public static function style(array $look): string
    {
        $accents = Color::accents((string) $look['accent']);

        return '--sky-accent:'.$accents['light'].';--sky-accent-dark:'.$accents['dark']
            .';--sky-ink:'.$accents['inkLight'].';--sky-ink-dark:'.$accents['inkDark']
            .';--sky-blur:'.(int) $look['blur'].'px;--sky-scale:'.((int) $look['scale'] / 100).';';
    }

    /**
     * A guest's look is kept in localStorage as the attributes and style the
     * Studio already worked out, so this only copies them on.
     */
    private static function script(bool $guest, bool $autoSolid): string
    {
        $js = '(function(h){';

        if ($guest) {
            $js .= 'try{var o=JSON.parse(localStorage.getItem("skylight")||"null");'
                .'if(o){for(var k in o.a)k.indexOf("data-sky")||h.setAttribute(k,o.a[k]);if(o.s)h.style.cssText+=o.s}}catch(e){o=null}';
        }

        if ($autoSolid) {
            $js .= 'var n=navigator;if(!h.hasAttribute("data-sky-glass-set")&&((n.deviceMemory||8)<4||(n.connection&&n.connection.saveData)))'
                .'h.setAttribute("data-sky-glass","solid"),h.setAttribute("data-sky-auto-solid","1")';
        }

        return $js.'})(document.documentElement)';
    }
}
