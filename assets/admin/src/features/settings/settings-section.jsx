import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";

export function SettingsSection({ title, description, config }) {
  return (
    <>
      <section className="space-y-2">
        <h1 className="text-3xl font-semibold tracking-tight text-slate-900">Prox Gallery {title}</h1>
        <p className="text-sm text-slate-600">{description}</p>
      </section>
      <Card>
        <CardHeader>
          <CardTitle>Settings Placeholder</CardTitle>
          <CardDescription>This area is reserved for settings forms and configuration controls.</CardDescription>
        </CardHeader>
        <CardContent>
          <p className="mb-4 text-sm text-slate-600">Add your settings controls here later.</p>
          <pre className="overflow-auto rounded-lg bg-slate-950 p-4 text-xs text-slate-100">
            {JSON.stringify(config, null, 2)}
          </pre>
        </CardContent>
      </Card>
    </>
  );
}
