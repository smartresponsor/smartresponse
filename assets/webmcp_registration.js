// Browser WebMCP registration smoke test. No HTTP calls or authorization changes.
export function registerWebMcpSmokeTool() {
    const context = document.modelContext;
    if (!context || typeof context.registerTool !== 'function') {
        return null;
    }

    const controller = new AbortController();
    context.registerTool({
        name: 'agentizingBrowserSmoke',
        description: 'Confirm that the current page supports WebMCP registration.',
        inputSchema: { type: 'object', properties: {}, additionalProperties: false },
        execute: async () => ({ content: [{ type: 'text', text: 'WebMCP registration available' }] }),
    }, { signal: controller.signal });

    window.addEventListener('pagehide', () => controller.abort(), { once: true });
    return controller;
}
