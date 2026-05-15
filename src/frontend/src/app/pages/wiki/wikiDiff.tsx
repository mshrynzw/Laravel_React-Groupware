import { useMemo } from 'react';
import { diffLines } from 'diff';

type WikiDiffViewProps = {
  before: string;
  after: string;
};

export function WikiDiffView({ before, after }: WikiDiffViewProps) {
  const parts = useMemo(() => diffLines(before, after), [before, after]);

  return (
    <div className="rounded-lg border border-border bg-muted/30 text-xs font-mono overflow-x-auto max-h-[min(480px,60vh)] overflow-y-auto">
      <div className="p-3 whitespace-pre-wrap break-words">
        {parts.map((part, i) => {
          const cls = part.added
            ? 'bg-emerald-500/20 text-foreground'
            : part.removed
              ? 'bg-rose-500/20 text-foreground'
              : 'text-muted-foreground';
          return (
            <span key={i} className={cls}>
              {part.value}
            </span>
          );
        })}
      </div>
    </div>
  );
}
