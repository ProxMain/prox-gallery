import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";

export function GalleriesSection({ title, description }) {
  return (
    <>
      <section className="space-y-2">
        <h1 className="text-3xl font-semibold tracking-tight text-slate-900">Prox Gallery {title}</h1>
        <p className="text-sm text-slate-600">{description}</p>
      </section>
      <Card>
        <CardHeader>
          <CardTitle>Galleries Placeholder</CardTitle>
          <CardDescription>This area is reserved for gallery list, creation, and organization tools.</CardDescription>
        </CardHeader>
        <CardContent>
          <p className="text-sm text-slate-600">Add your galleries UI here later.</p>
        </CardContent>
      </Card>
    </>
  );
}
