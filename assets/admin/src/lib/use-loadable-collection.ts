import { useCallback, useEffect, useMemo, useRef, useState } from "react";

type Options<TController, TItem> = {
  missingMessage: string;
  loadErrorMessage: string;
  controller: TController | null;
  loadItems: (controller: TController) => Promise<TItem[]>;
};

/** Owns collection loading, caching and request deduplication. Callbacks stay stable. */
export function useLoadableCollection<TController, TItem>({
  controller,
  loadItems,
  missingMessage,
  loadErrorMessage,
}: Options<TController, TItem>) {
  const [state, setState] = useState({
    items: [] as TItem[],
    isLoading: false,
    error: "",
    hasLoaded: false,
  });
  const loader = useRef(loadItems);
  loader.current = loadItems;
  const cache = useMemo(
    () => ({
      items: [] as TItem[],
      hasLoaded: false,
      active: true,
      pending: null as Promise<TItem[]> | null,
    }),
    [controller],
  );

  useEffect(() => {
    cache.active = true;
    setState({ items: [], isLoading: false, error: "", hasLoaded: false });
    return () => {
      cache.active = false;
    };
  }, [cache]);

  const load = useCallback(
    async ({ force = false } = {}): Promise<TItem[]> => {
      if (!controller) {
        setState((current) => ({ ...current, error: missingMessage }));
        return [];
      }
      if (cache.pending) {
        await cache.pending;
        if (!force) return cache.items;
      }
      if (cache.pending) return cache.pending;
      if (!force && cache.hasLoaded) return cache.items;

      setState((current) => ({ ...current, isLoading: true, error: "" }));
      cache.pending = (async () => {
        try {
          const items = await loader.current(controller);
          cache.items = Array.isArray(items) ? items : [];
          cache.hasLoaded = true;
          if (cache.active)
            setState({
              items: cache.items,
              isLoading: false,
              error: "",
              hasLoaded: true,
            });
          return cache.items;
        } catch (error) {
          if (cache.active)
            setState((current) => ({
              ...current,
              isLoading: false,
              error: error instanceof Error ? error.message : loadErrorMessage,
            }));
          return [];
        } finally {
          cache.pending = null;
        }
      })();
      return cache.pending;
    },
    [cache, controller, missingMessage, loadErrorMessage],
  );

  const reload = useCallback(() => load({ force: true }), [load]);
  return { ...state, load, reload };
}
