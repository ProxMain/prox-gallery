import { ChevronLeft, ChevronRight } from "lucide-react";

import { cn } from "@/lib/utils";

export function CollectionPagination({
  className = "",
  itemLabel,
  totalItems,
  currentPage,
  pageSize,
  onPageSizeChange,
  onPreviousPage,
  onNextPage,
  totalPages
}) {
  const startItem = totalItems === 0 ? 0 : (currentPage - 1) * pageSize + 1;
  const endItem = totalItems === 0 ? 0 : Math.min(totalItems, currentPage * pageSize);

  return (
    <div className={cn("flex flex-col gap-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-3 md:flex-row md:items-center md:justify-between", className)}>
      <div className="flex flex-wrap items-center gap-3 text-sm text-slate-700">
        <label className="flex items-center gap-2">
          <span>Show</span>
          <select
            value={pageSize}
            onChange={(event) => onPageSizeChange(Number(event.target.value))}
            className="h-9 rounded-md border border-slate-300 bg-white px-2 text-sm text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
          >
            {[12, 24, 48, 96].map((option) => (
              <option key={option} value={option}>
                {option}
              </option>
            ))}
          </select>
          <span>{itemLabel} per page</span>
        </label>
        <span className="text-slate-500">
          {startItem}-{endItem} of {totalItems}
        </span>
      </div>
      <div className="flex items-center gap-2">
        <button
          type="button"
          onClick={onPreviousPage}
          disabled={currentPage <= 1}
          className="inline-flex h-9 items-center gap-1 rounded-md border border-slate-200 bg-white px-3 text-sm font-medium text-slate-800 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50"
        >
          <ChevronLeft className="h-4 w-4" />
          <span>Previous</span>
        </button>
        <span className="min-w-[88px] text-center text-sm text-slate-600">
          Page {totalPages === 0 ? 0 : currentPage} of {totalPages}
        </span>
        <button
          type="button"
          onClick={onNextPage}
          disabled={currentPage >= totalPages}
          className="inline-flex h-9 items-center gap-1 rounded-md border border-slate-200 bg-white px-3 text-sm font-medium text-slate-800 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50"
        >
          <span>Next</span>
          <ChevronRight className="h-4 w-4" />
        </button>
      </div>
    </div>
  );
}
