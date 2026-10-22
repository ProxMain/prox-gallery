import { useMemo, useState } from "react";
import { FolderOpen, LayoutGrid, List, RefreshCw, Search } from "lucide-react";
import { Button } from "@/components/ui/button";
import { CollectionPagination } from "@/components/ui/collection-pagination";
import { GalleryCard } from "./gallery-card";

export function GalleriesLibraryCard({
  galleries,
  templates,
  isLoading,
  error,
  onReloadGalleries,
  onCreate,
  ...actions
}) {
  const [query, setQuery] = useState("");
  const [template, setTemplate] = useState("");
  const [sort, setSort] = useState("newest");
  const [view, setView] = useState("grid");
  const [page, setPage] = useState(1);
  const [pageSize, setPageSize] = useState(12);
  const visible = useMemo(
    () =>
      galleries
        .filter(
          (gallery) =>
            `${gallery.name} ${gallery.description}`
              .toLowerCase()
              .includes(query.trim().toLowerCase()) &&
            (!template || gallery.template === template),
        )
        .sort((left, right) =>
          sort === "name"
            ? left.name.localeCompare(right.name)
            : String(right.created_at).localeCompare(String(left.created_at)),
        ),
    [galleries, query, template, sort],
  );
  const pages = Math.max(1, Math.ceil(visible.length / pageSize));
  const currentPage = Math.min(page, pages);
  const update = (setter) => (event) => {
    setter(event.target.value);
    setPage(1);
  };

  return (
    <div aria-busy={isLoading}>
      <div className="pg-library-toolbar">
        <label className="pg-search">
          <Search size={17} aria-hidden="true" />
          <input
            type="search"
            aria-label="Search galleries"
            placeholder="Find a collection…"
            value={query}
            onChange={update(setQuery)}
          />
        </label>
        <select
          aria-label="Filter by layout"
          value={template}
          onChange={update(setTemplate)}
        >
          <option value="">All layouts</option>
          {templates.map((item) => (
            <option key={item.slug} value={item.slug}>
              {item.label}
            </option>
          ))}
        </select>
        <select
          aria-label="Sort galleries"
          value={sort}
          onChange={update(setSort)}
        >
          <option value="newest">Newest first</option>
          <option value="name">Name A–Z</option>
        </select>
        <div className="pg-view-switch">
          <button
            type="button"
            onClick={() => setView("grid")}
            aria-label="Grid view"
            aria-pressed={view === "grid"}
          >
            <LayoutGrid size={17} />
          </button>
          <button
            type="button"
            onClick={() => setView("list")}
            aria-label="List view"
            aria-pressed={view === "list"}
          >
            <List size={17} />
          </button>
        </div>
        <button
          type="button"
          className="pg-icon-button"
          onClick={onReloadGalleries}
          disabled={isLoading}
          aria-label="Refresh galleries"
        >
          <RefreshCw size={17} className={isLoading ? "animate-spin" : ""} />
        </button>
      </div>
      {error ? (
        <p className="pg-notice pg-error" role="alert">
          {error}{" "}
          <button type="button" onClick={onReloadGalleries}>
            Try again
          </button>
        </p>
      ) : null}
      {isLoading && !galleries.length ? (
        <div
          className="pg-gallery-grid"
          role="status"
          aria-label="Loading galleries"
        >
          {[1, 2, 3].map((key) => (
            <div key={key} className="pg-skeleton" />
          ))}
        </div>
      ) : !visible.length && !error ? (
        <div className="pg-empty">
          <FolderOpen size={40} strokeWidth={1} />
          <h2>
            {galleries.length
              ? "No matching collections"
              : "Your first collection starts here"}
          </h2>
          <p>
            {galleries.length
              ? "Try another search or layout."
              : "Bring a few images together and give them a home."}
          </p>
          <Button onClick={onCreate}>Create gallery</Button>
        </div>
      ) : (
        <div
          className={`pg-gallery-grid ${view === "list" ? "pg-gallery-list" : ""}`}
        >
          {visible
            .slice((currentPage - 1) * pageSize, currentPage * pageSize)
            .map((gallery) => (
              <GalleryCard key={gallery.id} gallery={gallery} {...actions} />
            ))}
          {!query && !template && currentPage === pages ? (
            <button
              type="button"
              className="pg-new-collection"
              onClick={onCreate}
            >
              <span>+</span>
              <strong>A new perspective</strong>
              <small>Create another collection</small>
            </button>
          ) : null}
        </div>
      )}
      {visible.length ? (
        <CollectionPagination
          itemLabel="collections"
          totalItems={visible.length}
          currentPage={currentPage}
          pageSize={pageSize}
          pageSizeOptions={[12, 24, 48]}
          onPageSizeChange={(size) => {
            setPageSize(size);
            setPage(1);
          }}
          onPreviousPage={() => setPage(currentPage - 1)}
          onNextPage={() => setPage(currentPage + 1)}
          totalPages={pages}
        />
      ) : null}
    </div>
  );
}
