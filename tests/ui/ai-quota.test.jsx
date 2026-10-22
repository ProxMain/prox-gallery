import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { expect, it, vi, afterEach } from 'vitest';
import { ImageAiPanel } from '@/features/media-manager/components/image-ai-panel';
import { OpenAiActionController } from '@/modules/openai/controllers/openai-action-controller';

afterEach(() => vi.unstubAllGlobals());

const configuration = (remaining) => ({
  model: 'test-model', languages: ['English'],
  prompt_templates: [{ key: 'factual', label: 'Factual', prompt: 'Describe it' }],
  usage: { user_limit: 1, site_limit: 5, user_remaining: remaining, site_remaining: 4 },
});

it('refreshes quota after a failed provider attempt and disables further generation', async () => {
  const controller = {
    getGenerationConfig: vi.fn()
      .mockResolvedValueOnce({ config: configuration(1) })
      .mockResolvedValue({ config: configuration(0) }),
    generateStory: vi.fn().mockRejectedValue(new Error('Provider timeout')),
  };
  render(<ImageAiPanel image={{ id: 7 }} controller={controller} onApplied={vi.fn()} />);
  const generate = await screen.findByRole('button', { name: 'Generate preview' });
  fireEvent.click(generate);
  await screen.findByText('Provider timeout');
  await waitFor(() => expect(generate.disabled).toBe(true));
  expect(screen.getByText(/Today: 0 of 1/)).toBeTruthy();
  expect(controller.generateStory).toHaveBeenCalledTimes(1);
});

it('sends edited limits through the settings transport', async () => {
  const fetch = vi.fn().mockResolvedValue({ ok: true, json: async () => ({ success: true, data: { settings: {} } }) });
  vi.stubGlobal('fetch', fetch);
  const controller = new OpenAiActionController({ ajax_url: '/wp-admin/admin-ajax.php' }, { settings_update: { action: 'settings', nonce: 'nonce' } });
  await controller.updateSettings({ api_key: '', model: 'test', user_daily_limit: 0, site_daily_limit: 12, languages: ['English'], prompt_templates: [] });
  const body = new URLSearchParams(fetch.mock.calls[0][1].body);
  expect(body.get('user_daily_limit')).toBe('0');
  expect(body.get('site_daily_limit')).toBe('12');
});
