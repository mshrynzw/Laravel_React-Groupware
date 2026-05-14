import { describe, expect, it } from 'vitest';
import { formatWeekTitle, monthRangeIso, weekRangeIso } from './scheduleCalendar';

describe('scheduleCalendar', () => {
  it('monthRangeIso: 2026年5月は 5/1〜5/31', () => {
    const d = new Date(2026, 4, 14);
    expect(monthRangeIso(d)).toEqual({ from: '2026-05-01', to: '2026-05-31' });
  });

  it('weekRangeIso: 木曜アンカーはその週の日〜土（7日間）', () => {
    const thu = new Date(2026, 4, 14);
    expect(weekRangeIso(thu)).toEqual({ from: '2026-05-10', to: '2026-05-16' });
  });

  it('weekRangeIso: 日曜アンカーは同日が週の開始', () => {
    const sun = new Date(2026, 4, 10);
    expect(weekRangeIso(sun)).toEqual({ from: '2026-05-10', to: '2026-05-16' });
  });

  it('formatWeekTitle: 年をまたがない週のタイトルが生成できる', () => {
    const d = new Date(2026, 4, 14);
    expect(formatWeekTitle(d)).toMatch(/2026/);
    expect(formatWeekTitle(d)).toMatch(/5/);
  });
});
