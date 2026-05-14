import { describe, expect, it, vi, afterEach } from 'vitest';
import { endOfMonthYmd, formatMinutes, formatMonthInTokyo, todayDateInTokyo } from './attendanceDates';

describe('endOfMonthYmd', () => {
  it('returns last day of May', () => {
    expect(endOfMonthYmd('2026-05')).toBe('2026-05-31');
  });

  it('returns Feb 29 in leap year 2024', () => {
    expect(endOfMonthYmd('2024-02')).toBe('2024-02-29');
  });

  it('returns Feb 28 in non-leap year 2025', () => {
    expect(endOfMonthYmd('2025-02')).toBe('2025-02-28');
  });
});

describe('todayDateInTokyo', () => {
  afterEach(() => {
    vi.useRealTimers();
  });

  it('formats fixed instant in Asia/Tokyo', () => {
    vi.useFakeTimers();
    vi.setSystemTime(new Date('2026-03-31T15:00:00.000Z'));
    expect(todayDateInTokyo()).toBe('2026-04-01');
  });
});

describe('formatMonthInTokyo', () => {
  it('returns YYYY-MM for given date in Tokyo', () => {
    expect(formatMonthInTokyo(new Date('2026-01-15T12:00:00.000Z'))).toBe('2026-01');
  });
});

describe('formatMinutes', () => {
  it('formats hours and minutes', () => {
    expect(formatMinutes(125)).toBe('2時間5分');
  });
});
