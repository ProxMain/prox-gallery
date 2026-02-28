import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";

export function MediaManagerHeader({ config }) {
  return (
    <header className="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:flex-row md:items-center md:justify-between">
      <div>
        <h1 className="text-3xl font-semibold tracking-tight text-slate-900">Prox Gallery - Media Manager</h1>
        <p className="mt-2 text-sm text-slate-600">Manage your tracked media library.</p>
      </div>
      <div className="flex flex-wrap items-center gap-2 md:justify-end">
        <Button
          asChild
          variant="outline"
          className="focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2"
        >
          <a href="/wp-admin/upload.php">Upload</a>
        </Button>
        <Badge variant="outline" className="h-9 px-3 text-xs font-medium text-slate-700">
          Environment: Local | Screen: {config.screen || "n/a"}
        </Badge>
      </div>
    </header>
  );
}
