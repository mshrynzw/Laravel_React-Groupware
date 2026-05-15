export type WikiTreeNode = {
  id: number;
  slug: string;
  title: string;
  children: WikiTreeNode[];
};

export function flattenWikiTree(nodes: WikiTreeNode[], depth = 0): { id: number; slug: string; title: string; depth: number }[] {
  const out: { id: number; slug: string; title: string; depth: number }[] = [];
  for (const n of nodes) {
    out.push({ id: n.id, slug: n.slug, title: n.title, depth });
    out.push(...flattenWikiTree(n.children, depth + 1));
  }
  return out;
}

export function findWikiTreeNode(nodes: WikiTreeNode[], id: number): WikiTreeNode | null {
  for (const n of nodes) {
    if (n.id === id) return n;
    const c = findWikiTreeNode(n.children, id);
    if (c) return c;
  }
  return null;
}

export function collectWikiSubtreeIds(node: WikiTreeNode): number[] {
  return [node.id, ...node.children.flatMap((ch) => collectWikiSubtreeIds(ch))];
}
