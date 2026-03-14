import { useCallback, useReducer } from "react";

type State<TItem> = {
  items: TItem[];
  isLoading: boolean;
  error: string;
  hasLoaded: boolean;
};

type Action<TItem> =
  | { type: "load_start" }
  | { type: "load_success"; payload: TItem[] }
  | { type: "load_error"; payload: string };

const initialState = {
  items: [],
  isLoading: false,
  error: "",
  hasLoaded: false
};

function reducer<TItem>(state: State<TItem>, action: Action<TItem>): State<TItem> {
  switch (action.type) {
    case "load_start":
      return {
        ...state,
        isLoading: true,
        error: ""
      };
    case "load_success":
      return {
        ...state,
        isLoading: false,
        error: "",
        items: action.payload,
        hasLoaded: true
      };
    case "load_error":
      return {
        ...state,
        isLoading: false,
        error: action.payload
      };
    default:
      return state;
  }
}

type Options<TController, TItem> = {
  missingMessage: string;
  loadErrorMessage: string;
  controller: TController | null;
  loadItems: (controller: TController) => Promise<TItem[]>;
};

export function useLoadableCollection<TController, TItem>({
  missingMessage,
  loadErrorMessage,
  controller,
  loadItems
}: Options<TController, TItem>) {
  const [state, dispatch] = useReducer(reducer<TItem>, initialState as State<TItem>);

  const load = useCallback(async ({ force = false }: { force?: boolean } = {}) => {
    if (!controller) {
      dispatch({ type: "load_error", payload: missingMessage });
      return [] as TItem[];
    }

    if (state.isLoading) {
      return state.items;
    }

    if (!force && state.hasLoaded) {
      return state.items;
    }

    dispatch({ type: "load_start" });

    try {
      const items = await loadItems(controller);
      const nextItems = Array.isArray(items) ? items : [];
      dispatch({ type: "load_success", payload: nextItems });
      return nextItems;
    } catch (error) {
      const message = error instanceof Error ? error.message : loadErrorMessage;
      dispatch({ type: "load_error", payload: message });
      return [] as TItem[];
    }
  }, [
    controller,
    loadErrorMessage,
    loadItems,
    missingMessage,
    state.hasLoaded,
    state.isLoading,
    state.items
  ]);

  const reload = useCallback(async () => load({ force: true }), [load]);

  return {
    ...state,
    load,
    reload
  };
}
