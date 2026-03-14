import { ArrowDownAZ, Filter } from "lucide-react";

export function MediaFilesFilters({
  selectedCategory,
  onSelectedCategoryChange,
  categoryOptions,
  dateSort,
  onDateSortChange
}) {
  return (
    <div className="mb-4 grid gap-3 rounded-md border border-slate-200 bg-slate-50 p-3 md:grid-cols-2">
      <label className="space-y-1">
        <span className="flex items-center gap-1 text-xs font-medium text-slate-600">
          <Filter className="h-3.5 w-3.5" />
          Category
        </span>
        <select
          value={selectedCategory}
          onChange={(event) => onSelectedCategoryChange(event.target.value)}
          className="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
        >
          <option value="all">All categories</option>
          {categoryOptions.map((name) => (
            <option key={name} value={name}>
              {name}
            </option>
          ))}
        </select>
      </label>

      <label className="space-y-1">
        <span className="flex items-center gap-1 text-xs font-medium text-slate-600">
          <ArrowDownAZ className="h-3.5 w-3.5" />
          Sort by date
        </span>
        <select
          value={dateSort}
          onChange={(event) => onDateSortChange(event.target.value)}
          className="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
        >
          <option value="date_desc">Newest first</option>
          <option value="date_asc">Oldest first</option>
        </select>
      </label>
    </div>
  );
}
