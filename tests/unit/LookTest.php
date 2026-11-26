<?php

/*
 * This file is part of ernestdefoe/skylight.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\Skylight\Tests\unit;

use ErnestDefoe\Skylight\Color;
use ErnestDefoe\Skylight\Content\Stamp;
use ErnestDefoe\Skylight\Look;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use PHPUnit\Framework\TestCase;

class LookTest extends TestCase
{
    public function test_sanitize_keeps_only_known_keys_and_values(): void
    {
        $look = Look::sanitize([
            'accent' => '#ABC',
            'bg' => 'dusk',
            'glass' => 'frosted',          // not a choice
            'blur' => 99,                  // clamped
            'scale' => '85',               // clamped
            'view' => 'cards',
            'rt' => 1,
            'evil' => '"><script>',        // unknown key
            'font' => '"><script>',        // invalid value
        ]);

        $this->assertSame(['accent' => '#aabbcc', 'bg' => 'dusk', 'blur' => 20, 'scale' => 90, 'view' => 'cards', 'rt' => true], $look);
    }

    public function test_sanitize_accepts_json_and_rejects_garbage(): void
    {
        $this->assertSame(['radius' => 'round'], Look::sanitize('{"radius":"round"}'));
        $this->assertSame([], Look::sanitize('not json'));
        $this->assertSame([], Look::sanitize(null));
        $this->assertSame([], Look::sanitize(['accent' => 'red; background:url(x)']));
    }

    public function test_member_choices_only_apply_in_open_sections(): void
    {
        $look = new Look($this->settings(['ernestdefoe-skylight.sections' => '["accent"]', 'ernestdefoe-skylight.defaults' => '{"bg":"mist"}']));
        $member = $this->user(['accent' => '#6a4fc7', 'bg' => 'dusk', 'rt' => true], canUseStudio: true);

        ['look' => $result, 'own' => $own] = $look->for($member);

        $this->assertSame('#6a4fc7', $result['accent']);   // open section
        $this->assertSame('mist', $result['bg']);          // locked: the admin's default
        $this->assertTrue($result['rt']);                  // readability is always open
        $this->assertSame(['accent', 'rt'], $own);
    }

    public function test_without_the_permission_a_member_sees_the_forum_look(): void
    {
        $look = new Look($this->settings([]));
        $member = $this->user(['accent' => '#6a4fc7'], canUseStudio: false);

        $this->assertSame(Look::BUILT_IN['accent'], $look->for($member)['look']['accent']);
    }

    public function test_tag_placement_and_night_tone_are_allow_listed_and_follow_their_sections(): void
    {
        $this->assertSame(['tags' => 'below', 'night' => 'black'], Look::sanitize(['tags' => 'below', 'night' => 'black']));
        $this->assertSame([], Look::sanitize(['tags' => 'above', 'night' => 'purple']));

        // Tags belong to the list view's section, the night tone to the background's.
        $look = new Look($this->settings(['ernestdefoe-skylight.sections' => '["view"]']));
        $member = $this->user(['tags' => 'below', 'night' => 'black'], canUseStudio: true);
        ['look' => $result] = $look->for($member);

        $this->assertSame('below', $result['tags']);
        $this->assertSame('blue', $result['night']);   // background section is closed
        $this->assertSame('below', Stamp::attributes($result)['data-sky-tags']);
        $this->assertSame('blue', Stamp::attributes($result)['data-sky-night']);
    }

    public function test_stamp_attributes_and_style_carry_only_clean_values(): void
    {
        $look = array_merge(Look::BUILT_IN, Look::sanitize(['accent' => '#ffee00', 'view' => 'compact']));

        $this->assertSame('compact', Stamp::attributes($look)['data-sky-view']);
        $this->assertMatchesRegularExpression('/^(--sky-[a-z-]+:[#0-9a-z.]+;)+$/', Stamp::style($look));
    }

    public function test_accents_reach_readable_contrast_in_both_schemes(): void
    {
        foreach (['#ffee00', '#127c86', '#000000', '#ffffff', '#6a4fc7'] as $hex) {
            $a = Color::accents($hex);

            $this->assertGreaterThanOrEqual(4.5, Color::contrast($a['light'], Color::LIGHT_PANE), $hex.' light');
            $this->assertGreaterThanOrEqual(4.5, Color::contrast($a['dark'], Color::DARK_PANE), $hex.' dark');
            $this->assertGreaterThanOrEqual(4.5, Color::contrast($a['inkLight'], $a['light']), $hex.' ink on light');
        }

        // An accent that already reads well is left alone.
        $this->assertSame('#127c86', Color::accents('#127c86')['light']);
    }

    /** @param array<string, string> $values */
    private function settings(array $values): SettingsRepositoryInterface
    {
        $settings = $this->createStub(SettingsRepositoryInterface::class);
        $settings->method('get')->willReturnCallback(fn (string $key) => $values[$key] ?? null);

        return $settings;
    }

    /** @param array<string, mixed> $choices */
    private function user(array $choices, bool $canUseStudio): User
    {
        $user = $this->createStub(User::class);
        $user->method('isGuest')->willReturn(false);
        $user->method('hasPermission')->willReturn($canUseStudio);
        $user->method('getPreference')->willReturn($choices);

        return $user;
    }
}
