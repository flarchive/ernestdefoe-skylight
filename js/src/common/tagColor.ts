import { payload, Payload } from './look';

/**
 * The color a discussion takes from its tags. A list row uses its first colored
 * tag; a discussion page prefers its first top-level one (`topLevelFirst`), as
 * in 1.0. When its tags carry more than one color and the admin chose a color
 * for that case, that color instead, so no single tag stands for all of them.
 */
export function tagColor(tags: any[] | false | undefined, topLevelFirst = false, p: Payload = payload()): string | null {
  const colored = (tags || []).filter((tag: any) => tag && tag.color());
  if (!colored.length) return null;

  const distinct = new Set(colored.map((tag: any) => String(tag.color()).toLowerCase()));
  if (p.multiTagColor && distinct.size > 1) return p.multiTagColor;

  return ((topLevelFirst && colored.find((tag: any) => !tag.parent?.())) || colored[0]).color();
}
