export type AjaxActionDefinition = {
  action: string;
  nonce: string;
};

export type ActionControllerConfig = {
  ajax_url: string;
};

export abstract class AbstractActionController<TDefinitions extends Record<string, AjaxActionDefinition>> {
  protected constructor(
    protected readonly config: ActionControllerConfig,
    protected readonly definitions: TDefinitions
  ) {}

  protected async dispatch<TResponse>(
    key: keyof TDefinitions,
    payload: Record<string, string | number | boolean | null> = {}
  ): Promise<TResponse> {
    const definition = this.definitions[key as string];

    if (!definition) {
      throw new Error(`Unknown action key: ${String(key)}`);
    }

    const body = new URLSearchParams();
    body.set("action", definition.action);
    body.set("_ajax_nonce", definition.nonce);

    Object.entries(payload).forEach(([field, value]) => {
      if (value === null) {
        return;
      }

      body.set(field, String(value));
    });

    const response = await fetch(this.config.ajax_url, {
      method: "POST",
      credentials: "same-origin",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
      },
      body: body.toString()
    });

    if (!response.ok) {
      let message = `Action request failed with HTTP ${response.status}.`;

      try {
        const errorJson = (await response.json()) as {
          data?: { message?: string };
        };

        if (
          typeof errorJson === "object" &&
          errorJson !== null &&
          errorJson.data &&
          typeof errorJson.data.message === "string" &&
          errorJson.data.message !== ""
        ) {
          message = errorJson.data.message;
        }
      } catch {
        // Keep fallback HTTP message.
      }

      throw new Error(message);
    }

    const json = (await response.json()) as {
      success: boolean;
      data?: TResponse | { message?: string };
    };

    if (!json.success) {
      const message =
        typeof json.data === "object" && json.data !== null && "message" in json.data
          ? String(json.data.message ?? "Action failed.")
          : "Action failed.";

      throw new Error(message);
    }

    return (json.data ?? {}) as TResponse;
  }
}
