/** YYYY-MM-DD（API の from / to と同一形式、ローカル日付） */
function toYmd(d: Date): string {
  const pad = (n: number) => (n < 10 ? `0${n}` : String(n));
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

export function monthRangeIso(d: Date): { from: string; to: string } {
  const y = d.getFullYear();
  const m = d.getMonth();
  const from = toYmd(new Date(y, m, 1));
  const to = toYmd(new Date(y, m + 1, 0));
  return { from, to };
}

/**
 * 表示アンカー日を含む週（日曜始まり、土曜終わり）の from/to。
 * 月グリッドの曜日並びと揃える。
 */
export function weekRangeIso(anchor: Date): { from: string; to: string } {
  const anchorNorm = new Date(anchor.getFullYear(), anchor.getMonth(), anchor.getDate());
  const dow = anchorNorm.getDay();
  const sunday = new Date(anchorNorm);
  sunday.setDate(anchorNorm.getDate() - dow);
  const saturday = new Date(sunday);
  saturday.setDate(sunday.getDate() + 6);
  return { from: toYmd(sunday), to: toYmd(saturday) };
}

export function formatWeekTitle(anchor: Date, locale = 'ja-JP'): string {
  const { from, to } = weekRangeIso(anchor);
  const a = new Date(from + 'T12:00:00');
  const b = new Date(to + 'T12:00:00');
  const sameYear = a.getFullYear() === b.getFullYear();
  const optsShort: Intl.DateTimeFormatOptions = { month: 'long', day: 'numeric' };
  const optsYear: Intl.DateTimeFormatOptions = { year: 'numeric', ...optsShort };
  if (sameYear) {
    return `${a.toLocaleDateString(locale, { year: 'numeric', month: 'long', day: 'numeric' })} — ${b.toLocaleDateString(locale, optsShort)}`;
  }
  return `${a.toLocaleDateString(locale, optsYear)} — ${b.toLocaleDateString(locale, optsYear)}`;
}
