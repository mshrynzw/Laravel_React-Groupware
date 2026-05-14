/** 勤怠画面・ダッシュボードで共有する日付（Asia/Tokyo）ユーティリティ */

export function formatMonthInTokyo(d: Date): string {
  return new Intl.DateTimeFormat('en-CA', {
    timeZone: 'Asia/Tokyo',
    year: 'numeric',
    month: '2-digit',
  }).format(d);
}

export function todayDateInTokyo(date: Date = new Date()): string {
  return new Intl.DateTimeFormat('en-CA', {
    timeZone: 'Asia/Tokyo',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  }).format(date);
}

/** `YYYY-MM` 形式の月から、その月最終日の `YYYY-MM-DD` を返す（ローカル暦の月終端計算） */
export function endOfMonthYmd(monthYyyyMm: string): string {
  const [ys, ms] = monthYyyyMm.split('-');
  const y = Number(ys);
  const mo = Number(ms);
  const last = new Date(y, mo, 0);
  const dd = String(last.getDate()).padStart(2, '0');
  return `${y}-${String(mo).padStart(2, '0')}-${dd}`;
}

export function formatMinutes(total: number): string {
  const h = Math.floor(total / 60);
  const m = total % 60;
  return `${h}時間${m}分`;
}
